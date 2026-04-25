<?php
/**
 * Disparador de mensagens para campanhas
 * Executado via cronjob a cada 10 minutos
 * Envia mensagens a cada 30 segundos
 */

require_once '../db_disparador.php';
require_once '../db.php';
require_once '../credits_manager.php';
require_once 'openai_manager.php';
require_once '../db.php';

error_log("=== INICIANDO DISPATCHE DE MENSAGENS ===");

function enviarMensagemEvolution($telefone, $mensagem, $session_name, $bot_token) {
    // Formatar número corretamente
    $numero = preg_replace('/[^0-9]/', '', $telefone);
    
    // Se for número brasileiro (11 dígitos) e não começar com 55, adicionar
    if (strlen($numero) == 11 && !str_starts_with($numero, '55')) {
        $numero = '55' . $numero;
    }
    // Se for número de 10 dígitos (celular antigo), adicionar 55 e 9
    elseif (strlen($numero) == 10 && !str_starts_with($numero, '55')) {
        $numero = '55' . substr($numero, 0, 2) . '9' . substr($numero, 2);
    }
    
    $api_url = "http://45.239.42.53:8080/message/sendText/{$session_name}";
    
    $dados = [
        'number' => str_replace('@s.whatsapp.net', '', $numero),
        'options' => [
            'delay' => 1200,
            'presence' => 'composing',
            'linkPreview' => false
        ],
        'textMessage' => [
            'text' => $mensagem
        ]
    ];
    
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $bot_token
    ];
    
    error_log("Enviando para: " . $numero . " via instância: " . $session_name);
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
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
    
    error_log("Response HTTP Code: " . $http_code);
    error_log("Response Body: " . $response);
    
    if ($http_code == 200 || $http_code == 201) {
        $response_data = json_decode($response, true);
        // Status PENDING significa que foi enviado com sucesso
        if (isset($response_data['status']) && $response_data['status'] == 'PENDING') {
            return true;
        }
    }
    
    return false;
}

function processarCampanhasAtivas() {
    global $pdo_disparador, $pdo;
    
    try {
        // Buscar campanhas que devem ser disparadas
        $stmt = $pdo_disparador->prepare("
            SELECT c.*
            FROM dis_campanhas c
            WHERE c.foi_disparada = 0 
            AND c.data_agendada <= NOW()
            AND c.nome_tabela_envio IS NOT NULL
            ORDER BY c.data_agendada ASC
        ");
        $stmt->execute();
        $campanhas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar dados das sessões do banco mariai
        $campanhas = [];
        foreach ($campanhas_raw as $campanha) {
            $stmt_session = $pdo->prepare("SELECT session_name, bot_token FROM sessions WHERE id = ?");
            $stmt_session->execute([$campanha['id_session']]);
            $session_data = $stmt_session->fetch(PDO::FETCH_ASSOC);
            
            if ($session_data) {
                $campanha['session_name'] = $session_data['session_name'];
                $campanha['bot_token'] = $session_data['bot_token'];
                $campanhas[] = $campanha;
            } else {
                error_log("Sessão " . $campanha['id_session'] . " não encontrada para campanha " . $campanha['id']);
            }
        }
        
        if (empty($campanhas)) {
            error_log("Nenhuma campanha ativa encontrada para disparo");
            return;
        }
        
        foreach ($campanhas as $campanha) {
            error_log("Processando campanha: " . $campanha['nome_campanha'] . " (ID: " . $campanha['id'] . ")");
            processarEnviosCampanha($campanha);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar campanhas: " . $e->getMessage());
    }
}

function processarEnviosCampanha($campanha) {
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
            error_log("Campanha " . $campanha['id'] . " marcada como iniciada");
        }
        
        // Buscar contatos pendentes de envio (limitando a quantidade por execução)
        $stmt = $pdo_disparador->prepare("
            SELECT * FROM `{$table_name}` 
            WHERE enviado = 0 AND cancelado = 0 
            ORDER BY id ASC 
            LIMIT 20
        ");
        $stmt->execute();
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contatos)) {
            error_log("Nenhum contato pendente na campanha " . $campanha['id']);
            
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
                error_log("Campanha " . $campanha['id'] . " finalizada - todos os envios concluídos");
            }
            
            return;
        }
        
        $enviados = 0;
        $max_envios_por_execucao = 20; // Máximo 20 envios por execução
        
        foreach ($contatos as $contato) {
            if ($enviados >= $max_envios_por_execucao) {
                error_log("Limite de envios atingido para esta execução");
                break;
            }
            
            $sucesso = enviarMensagemEvolution(
                $contato['telefone'], 
                $contato['mensagem'], 
                $campanha['session_name'], 
                $campanha['bot_token']
            );
            
            if ($sucesso) {
                // Debitar créditos do envio (3 créditos)
                global $pdo;
                debitarCreditos($pdo, $campanha['session_id'], 'envio_mensagem', 3, 
                    "Envio para: " . $contato['telefone'], $campanha['id']);
                
                // Marcar como enviado
                $stmt_update = $pdo_disparador->prepare("
                    UPDATE `{$table_name}` 
                    SET enviado = 1, data_envio = NOW() 
                    WHERE id = ?
                ");
                $stmt_update->execute([$contato['id']]);
                $enviados++;
                
                error_log("Mensagem enviada com sucesso para: " . $contato['telefone']);
            } else {
                error_log("Falha ao enviar mensagem para: " . $contato['telefone']);
            }
            
            // Delay de 30 segundos entre envios
            if ($enviados < $max_envios_por_execucao && $enviados < count($contatos)) {
                error_log("Aguardando 30 segundos antes do próximo envio...");
                sleep(30);
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
            
            error_log("Campanha " . $campanha['id'] . ": {$enviados} mensagens enviadas nesta execução");
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar envios da campanha " . $campanha['id'] . ": " . $e->getMessage());
    }
}

// Função para processar respostas e cancelamentos (webhook)
function processarResposta($telefone, $mensagem_resposta, $session_name) {
    global $pdo_disparador, $pdo;
    
    try {
        // Buscar sessão no banco mariai
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE session_name = ?");
        $stmt->execute([$session_name]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return false;
        }
        
        $id_session = $session['id'];
        
        // Verificar se é mensagem de cancelamento
        $palavras_cancelamento = ['parar', 'pare', 'stop', 'remover', 'sair', 'cancelar', 'descadastrar'];
        $eh_cancelamento = false;
        
        foreach ($palavras_cancelamento as $palavra) {
            if (stripos($mensagem_resposta, $palavra) !== false) {
                $eh_cancelamento = true;
                break;
            }
        }
        
        if ($eh_cancelamento) {
            // Adicionar ao blacklist
            $stmt_blacklist = $pdo_disparador->prepare("
                INSERT IGNORE INTO dis_blacklist_optout (telefone, id_session) 
                VALUES (?, ?)
            ");
            $stmt_blacklist->execute([$telefone, $id_session]);
            
            error_log("Telefone {$telefone} adicionado ao blacklist por solicitação");
        }
        
        // Marcar todas as campanhas ativas deste telefone como respondidas
        $stmt_campanhas = $pdo_disparador->prepare("
            SELECT id, nome_tabela_envio FROM dis_campanhas 
            WHERE id_session = ? AND foi_disparada = 1 AND data_disparo_fim IS NULL
        ");
        $stmt_campanhas->execute([$id_session]);
        $campanhas_ativas = $stmt_campanhas->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($campanhas_ativas as $camp) {
            if ($camp['nome_tabela_envio']) {
                $table_name = $camp['nome_tabela_envio'];
                
                if ($eh_cancelamento) {
                    $stmt_update = $pdo_disparador->prepare("
                        UPDATE `{$table_name}` 
                        SET respondido = 1, cancelado = 1 
                        WHERE telefone = ?
                    ");
                } else {
                    $stmt_update = $pdo_disparador->prepare("
                        UPDATE `{$table_name}` 
                        SET respondido = 1 
                        WHERE telefone = ?
                    ");
                }
                
                $stmt_update->execute([$telefone]);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao processar resposta: " . $e->getMessage());
        return false;
    }
}

// Execução principal
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $inicio = microtime(true);
    
    processarCampanhasAtivas();
    
    $fim = microtime(true);
    $tempo_execucao = round($fim - $inicio, 2);
    
    error_log("=== DISPATCH FINALIZADO EM {$tempo_execucao} SEGUNDOS ===");
} else {
    echo "Este script deve ser executado via linha de comando ou com parâmetro ?run=1";
}
?>