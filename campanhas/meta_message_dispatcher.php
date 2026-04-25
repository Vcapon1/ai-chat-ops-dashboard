<?php
/**
 * Disparador de mensagens para campanhas usando Meta API
 * Executado via cronjob a cada 10 minutos
 * Envia mensagens usando templates aprovados
 */

require_once '../db_disparador.php';
require_once '../db.php';
require_once '../credits_manager.php';

error_log("=== INICIANDO DISPATCH DE MENSAGENS META API ===");

function enviarMensagemMeta($telefone, $template_name, $template_params, $meta_config) {
    // Formatar número para padrão internacional
    $numero = preg_replace('/[^0-9]/', '', $telefone);
    
    // Se for número brasileiro (11 dígitos) e não começar com 55, adicionar
    if (strlen($numero) == 11 && !str_starts_with($numero, '55')) {
        $numero = '55' . $numero;
    }
    // Se for número de 10 dígitos (celular antigo), adicionar 55 e 9
    elseif (strlen($numero) == 10 && !str_starts_with($numero, '55')) {
        $numero = '55' . substr($numero, 0, 2) . '9' . substr($numero, 2);
    }
    
    $api_url = "https://graph.facebook.com/v18.0/{$meta_config['phone_id']}/messages";
    
    // Montar corpo da mensagem com template
    $message_data = [
        'messaging_product' => 'whatsapp',
        'to' => $numero,
        'type' => 'template',
        'template' => [
            'name' => $template_name,
            'language' => [
                'code' => 'pt_BR'
            ]
        ]
    ];
    
    // Adicionar parâmetros se existirem
    if (!empty($template_params)) {
        $components = [];
        $body_params = [];
        
        foreach ($template_params as $param) {
            $body_params[] = [
                'type' => 'text',
                'text' => $param
            ];
        }
        
        if (!empty($body_params)) {
            $components[] = [
                'type' => 'body',
                'parameters' => $body_params
            ];
            $message_data['template']['components'] = $components;
        }
    }
    
    $headers = [
        'Authorization: Bearer ' . $meta_config['access_token'],
        'Content-Type: application/json'
    ];
    
    error_log("Enviando para: " . $numero . " via Meta API");
    error_log("Template: " . $template_name);
    error_log("Payload: " . json_encode($message_data));
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    if ($curl_error) {
        error_log("CURL Error: " . $curl_error);
        return false;
    }
    
    error_log("Meta API Response HTTP Code: " . $http_code);
    error_log("Meta API Response Body: " . $response);
    
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        // Verificar se a mensagem foi aceita
        if (isset($response_data['messages']) && !empty($response_data['messages'])) {
            return true;
        }
    }
    
    // Log de erros da API
    if ($http_code >= 400) {
        $error_data = json_decode($response, true);
        if (isset($error_data['error'])) {
            error_log("Meta API Error: " . json_encode($error_data['error']));
        }
    }
    
    return false;
}

function processarCampanhasAtivasMeta() {
    global $pdo_disparador, $pdo;
    
    try {
        // Buscar campanhas que devem ser disparadas e usam Meta API
        $stmt = $pdo_disparador->prepare("
            SELECT c.*
            FROM dis_campanhas c
            JOIN sessions s ON s.id = c.id_session
            WHERE c.foi_disparada = 0 
            AND c.data_agendada <= NOW()
            AND c.nome_tabela_envio IS NOT NULL
            AND s.api_type = 'meta'
            ORDER BY c.data_agendada ASC
        ");
        $stmt->execute();
        $campanhas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar dados das configurações Meta para cada campanha
        $campanhas = [];
        foreach ($campanhas_raw as $campanha) {
            $stmt_meta = $pdo->prepare("SELECT * FROM integrators_meta WHERE id_session = ?");
            $stmt_meta->execute([$campanha['id_session']]);
            $meta_config = $stmt_meta->fetch(PDO::FETCH_ASSOC);
            
            if ($meta_config) {
                $campanha['meta_config'] = $meta_config;
                $campanhas[] = $campanha;
            } else {
                error_log("Configuração Meta não encontrada para sessão " . $campanha['id_session']);
            }
        }
        
        if (empty($campanhas)) {
            error_log("Nenhuma campanha Meta ativa encontrada para disparo");
            return;
        }
        
        foreach ($campanhas as $campanha) {
            error_log("Processando campanha Meta: " . $campanha['nome_campanha'] . " (ID: " . $campanha['id'] . ")");
            processarEnviosCampanhaMeta($campanha);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar campanhas Meta: " . $e->getMessage());
    }
}

function processarEnviosCampanhaMeta($campanha) {
    global $pdo_disparador;
    
    try {
        $table_name = $campanha['nome_tabela_envio'];
        
        // Verificar se a tabela existe
        $check_table = $pdo_disparador->prepare("SHOW TABLES LIKE '{$table_name}'");
        $check_table->execute();
        
        if (!$check_table->fetch()) {
            error_log("Tabela {$table_name} não encontrada para campanha " . $campanha['id']);
            return;
        }
        
        // Marcar campanha como iniciada se ainda não foi
        if ($campanha['data_disparo_inicio'] == null) {
            $stmt = $pdo_disparador->prepare("
                UPDATE dis_campanhas 
                SET data_disparo_inicio = NOW(), foi_disparada = 1 
                WHERE id = ?
            ");
            $stmt->execute([$campanha['id']]);
            error_log("Campanha Meta " . $campanha['id'] . " marcada como iniciada");
        }
        
        // Buscar contatos pendentes de envio
        $stmt = $pdo_disparador->prepare("
            SELECT * FROM `{$table_name}` 
            WHERE enviado = 0 AND cancelado = 0 
            ORDER BY id ASC 
            LIMIT 10
        ");
        $stmt->execute();
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contatos)) {
            error_log("Nenhum contato pendente na campanha Meta " . $campanha['id']);
            
            // Verificar se todos foram enviados para finalizar campanha
            $stmt_check = $pdo_disparador->prepare("
                SELECT COUNT(*) as pendentes FROM `{$table_name}` 
                WHERE enviado = 0 AND cancelado = 0
            ");
            $stmt_check->execute();
            $pendentes = $stmt_check->fetchColumn();
            
            if ($pendentes == 0) {
                // Finalizar campanha
                $stmt_fim = $pdo_disparador->prepare("
                    UPDATE dis_campanhas 
                    SET data_disparo_fim = NOW() 
                    WHERE id = ?
                ");
                $stmt_fim->execute([$campanha['id']]);
                error_log("Campanha Meta " . $campanha['id'] . " finalizada - todos os envios concluídos");
            }
            
            return;
        }
        
        $enviados = 0;
        $max_envios_por_execucao = 10; // Meta API tem limites mais rígidos
        
        foreach ($contatos as $contato) {
            if ($enviados >= $max_envios_por_execucao) {
                error_log("Limite de envios Meta atingido para esta execução");
                break;
            }
            
            // Extrair template e parâmetros da mensagem
            $template_info = extrairInfoTemplate($contato['mensagem']);
            
            $sucesso = enviarMensagemMeta(
                $contato['telefone'], 
                $template_info['name'],
                $template_info['params'],
                $campanha['meta_config']
            );
            
            if ($sucesso) {
                // Debitar créditos do envio (5 créditos para Meta API)
                global $pdo;
                debitarCreditos($pdo, $campanha['id_session'], 'envio_mensagem_meta', 5, 
                    "Envio Meta para: " . $contato['telefone'], $campanha['id']);
                
                // Marcar como enviado
                $stmt_update = $pdo_disparador->prepare("
                    UPDATE `{$table_name}` 
                    SET enviado = 1, data_envio = NOW() 
                    WHERE id = ?
                ");
                $stmt_update->execute([$contato['id']]);
                $enviados++;
                
                error_log("Mensagem Meta enviada com sucesso para: " . $contato['telefone']);
            } else {
                error_log("Falha ao enviar mensagem Meta para: " . $contato['telefone']);
            }
            
            // Delay maior entre envios para Meta API (60 segundos)
            if ($enviados < $max_envios_por_execucao && $enviados < count($contatos)) {
                error_log("Aguardando 60 segundos antes do próximo envio Meta...");
                sleep(60);
            }
        }
        
        // Atualizar estatísticas da campanha
        if ($enviados > 0) {
            $stmt_stats = $pdo_disparador->prepare("
                UPDATE dis_campanhas 
                SET qtd_enviados = qtd_enviados + ? 
                WHERE id = ?
            ");
            $stmt_stats->execute([$enviados, $campanha['id']]);
            
            error_log("Campanha Meta " . $campanha['id'] . ": {$enviados} mensagens enviadas nesta execução");
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar envios da campanha Meta " . $campanha['id'] . ": " . $e->getMessage());
    }
}

function extrairInfoTemplate($mensagem) {
    // Formato esperado: TEMPLATE:nome_template|param1,param2,param3
    // Ou apenas: TEMPLATE:nome_template (sem parâmetros)
    
    if (strpos($mensagem, 'TEMPLATE:') === 0) {
        $template_data = substr($mensagem, 9); // Remove "TEMPLATE:"
        
        if (strpos($template_data, '|') !== false) {
            list($template_name, $params_str) = explode('|', $template_data, 2);
            $params = !empty($params_str) ? explode(',', $params_str) : [];
        } else {
            $template_name = $template_data;
            $params = [];
        }
        
        return [
            'name' => trim($template_name),
            'params' => array_map('trim', $params)
        ];
    }
    
    // Fallback: usar template padrão
    return [
        'name' => 'mensagem_generica',
        'params' => [$mensagem]
    ];
}

// Execução principal para Meta API
if (php_sapi_name() === 'cli' || isset($_GET['run_meta'])) {
    $inicio = microtime(true);
    
    processarCampanhasAtivasMeta();
    
    $fim = microtime(true);
    $tempo_execucao = round($fim - $inicio, 2);
    
    error_log("=== DISPATCH META FINALIZADO EM {$tempo_execucao} SEGUNDOS ===");
} else {
    echo "Este script deve ser executado via linha de comando ou com parâmetro ?run_meta=1";
}
?>