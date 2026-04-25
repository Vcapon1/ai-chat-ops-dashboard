<?php
/**
 * Exemplo de script para processamento de disparo de campanhas
 * Este arquivo seria executado por um sistema externo (n8n, cron, etc.)
 */

require_once '../db_disparador.php';

function processarDisparoCampanha($id_campanha) {
    global $pdo_disparador;
    
    try {
        // Buscar dados da campanha
        $stmt = $pdo_disparador->prepare("
            SELECT * FROM dis_campanhas 
            WHERE id = ? AND foi_disparada = 0
        ");
        $stmt->execute([$id_campanha]);
        $campanha = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campanha) {
            throw new Exception('Campanha não encontrada ou já foi disparada');
        }
        
        $table_name = $campanha['nome_tabela_envio'];
        if (!$table_name) {
            throw new Exception('Tabela de envios não encontrada');
        }
        
        // Marcar início do disparo
        $stmt = $pdo_disparador->prepare("
            UPDATE dis_campanhas 
            SET data_disparo_inicio = NOW(), foi_disparada = 1 
            WHERE id = ?
        ");
        $stmt->execute([$id_campanha]);
        
        // Buscar contatos pendentes de envio
        $stmt = $pdo_disparador->prepare("
            SELECT * FROM `{$table_name}` 
            WHERE enviado = 0 AND cancelado = 0 
            ORDER BY id ASC
        ");
        $stmt->execute();
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $enviados = 0;
        
        foreach ($contatos as $contato) {
            // Aqui seria feita a integração com WhatsApp/API de envio
            $sucesso_envio = enviarMensagem($contato['telefone'], $contato['mensagem']);
            
            if ($sucesso_envio) {
                // Atualizar status de envio
                $update_stmt = $pdo_disparador->prepare("
                    UPDATE `{$table_name}` 
                    SET enviado = 1, data_envio = NOW() 
                    WHERE id = ?
                ");
                $update_stmt->execute([$contato['id']]);
                $enviados++;
            }
            
            // Delay entre envios (para evitar bloqueio)
            sleep(1);
        }
        
        // Atualizar estatísticas da campanha
        $stmt = $pdo_disparador->prepare("
            UPDATE dis_campanhas 
            SET qtd_enviados = ?, data_disparo_fim = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$enviados, $id_campanha]);
        
        return [
            'success' => true,
            'enviados' => $enviados,
            'total' => count($contatos)
        ];
        
    } catch (Exception $e) {
        error_log("Erro no disparo da campanha {$id_campanha}: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function enviarMensagem($telefone, $mensagem) {
    // Aqui seria implementada a integração real com WhatsApp Business API
    // ou qualquer outro provedor de mensagens
    
    // Simulação de envio (remover em produção)
    error_log("Enviando mensagem para {$telefone}: " . substr($mensagem, 0, 50) . "...");
    
    // Simular sucesso/falha aleatória para teste
    return rand(1, 100) > 5; // 95% de sucesso
}

function processarResponstas($id_campanha, $telefone, $mensagem_resposta) {
    global $pdo_disparador;
    
    try {
        // Buscar dados da campanha
        $stmt = $pdo_disparador->prepare("SELECT nome_tabela_envio FROM dis_campanhas WHERE id = ?");
        $stmt->execute([$id_campanha]);
        $table_name = $stmt->fetchColumn();
        
        if (!$table_name) {
            return false;
        }
        
        // Verificar se é mensagem de cancelamento
        $palavras_cancelamento = ['parar', 'pare', 'stop', 'remover', 'sair', 'cancelar'];
        $eh_cancelamento = false;
        
        foreach ($palavras_cancelamento as $palavra) {
            if (stripos($mensagem_resposta, $palavra) !== false) {
                $eh_cancelamento = true;
                break;
            }
        }
        
        if ($eh_cancelamento) {
            // Marcar como cancelado
            $stmt = $pdo_disparador->prepare("
                UPDATE `{$table_name}` 
                SET cancelado = 1, respondido = 1 
                WHERE telefone = ?
            ");
            $stmt->execute([$telefone]);
            
            // Adicionar ao blacklist global
            require_once 'database_operations.php';
            adicionarBlacklist($pdo_disparador, $campanha['id_session'], $telefone, $id_campanha);
        } else {
            // Marcar apenas como respondido
            $stmt = $pdo_disparador->prepare("
                UPDATE `{$table_name}` 
                SET respondido = 1 
                WHERE telefone = ?
            ");
            $stmt->execute([$telefone]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao processar resposta: " . $e->getMessage());
        return false;
    }
}

// Exemplo de uso via linha de comando ou webhook
if (isset($argv[1])) {
    $id_campanha = $argv[1];
    $resultado = processarDisparoCampanha($id_campanha);
    echo json_encode($resultado);
}