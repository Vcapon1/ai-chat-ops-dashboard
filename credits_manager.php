<?php
/**
 * Sistema de Gerenciamento de Créditos
 */

/**
 * Obter saldo de créditos da sessão
 */
function obterCreditos(PDO $pdo, int $session_id): int {
    $stmt = $pdo->prepare("SELECT credito FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['credito'] : 0;
}

/**
 * Debitar créditos da sessão
 */
function debitarCreditos(PDO $pdo, int $session_id, string $tipo, int $quantidade, string $descricao = null, int $campanha_id = null, int $lista_id = null): bool {
    try {
        $pdo->beginTransaction();
        
        // Obter saldo atual
        $saldo_atual = obterCreditos($pdo, $session_id);
        
        // Verificar se tem créditos suficientes
        if ($saldo_atual < $quantidade) {
            $pdo->rollBack();
            return false;
        }
        
        $novo_saldo = $saldo_atual - $quantidade;
        
        // Atualizar saldo na sessão
        $stmt = $pdo->prepare("UPDATE sessions SET credito = ? WHERE id = ?");
        $stmt->execute([$novo_saldo, $session_id]);
        
        // Registrar no histórico
        $stmt = $pdo->prepare("
            INSERT INTO credito_historico 
            (session_id, tipo, quantidade, saldo_anterior, saldo_atual, descricao, campanha_id, lista_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$session_id, $tipo, -$quantidade, $saldo_atual, $novo_saldo, $descricao, $campanha_id, $lista_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao debitar créditos: " . $e->getMessage());
        return false;
    }
}

/**
 * Creditar saldo à sessão
 */
function creditarSaldo(PDO $pdo, int $session_id, string $tipo, int $quantidade, string $descricao = null): bool {
    try {
        $pdo->beginTransaction();
        
        // Obter saldo atual
        $saldo_atual = obterCreditos($pdo, $session_id);
        $novo_saldo = $saldo_atual + $quantidade;
        
        // Atualizar saldo na sessão
        $stmt = $pdo->prepare("UPDATE sessions SET credito = ? WHERE id = ?");
        $stmt->execute([$novo_saldo, $session_id]);
        
        // Registrar no histórico
        $stmt = $pdo->prepare("
            INSERT INTO credito_historico 
            (session_id, tipo, quantidade, saldo_anterior, saldo_atual, descricao) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$session_id, $tipo, $quantidade, $saldo_atual, $novo_saldo, $descricao]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro ao creditar saldo: " . $e->getMessage());
        return false;
    }
}

/**
 * Obter histórico de créditos
 */
function obterHistoricoCreditos(PDO $pdo, int $session_id, int $limit = 50): array {
    $stmt = $pdo->prepare("
        SELECT * FROM credito_historico 
        WHERE session_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$session_id, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Verificar se tem créditos suficientes
 */
function temCreditosSuficientes(PDO $pdo, int $session_id, int $quantidade_necessaria): bool {
    $saldo = obterCreditos($pdo, $session_id);
    return $saldo >= $quantidade_necessaria;
}

/**
 * Adicionar telefones à fila de validação do WhatsApp
 */
function adicionarFilaValidacaoWhatsApp(PDO $pdo, int $session_id, int $lista_id, array $telefones): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO validacao_whatsapp_queue (session_id, lista_id, telefone) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($telefones as $telefone) {
            $stmt->execute([$session_id, $lista_id, $telefone]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao adicionar telefones à fila de validação: " . $e->getMessage());
        return false;
    }
}

/**
 * Processar fila de validação do WhatsApp (chamada por cron)
 */
function processarFilaValidacaoWhatsApp(PDO $pdo): void {
    try {
        // Buscar próximos 50 telefones pendentes
        $stmt = $pdo->prepare("
            SELECT * FROM validacao_whatsapp_queue 
            WHERE status = 'pendente' AND tentativas < 3
            ORDER BY created_at ASC 
            LIMIT 50
        ");
        $stmt->execute();
        $telefones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($telefones as $item) {
            // Marcar como processando
            $stmt = $pdo->prepare("UPDATE validacao_whatsapp_queue SET status = 'processando' WHERE id = ?");
            $stmt->execute([$item['id']]);
            
            // Verificar se tem créditos
            if (!temCreditosSuficientes($pdo, $item['session_id'], 1)) {
                // Marcar como erro por falta de créditos
                $stmt = $pdo->prepare("
                    UPDATE validacao_whatsapp_queue 
                    SET status = 'erro', resultado = ? 
                    WHERE id = ?
                ");
                $stmt->execute([json_encode(['erro' => 'Créditos insuficientes']), $item['id']]);
                continue;
            }
            
            // Chamar API de validação do Evolution
            $resultado = validarTelefoneEvolution($item['telefone'], $item['session_id']);
            
            if ($resultado['sucesso']) {
                // Debitar crédito
                debitarCreditos($pdo, $item['session_id'], 'validacao_whatsapp', 1, 
                    "Validação WhatsApp: " . $item['telefone'], null, $item['lista_id']);
                
                // Atualizar status
                $status = $resultado['existe'] ? 'validado' : 'invalido';
                $stmt = $pdo->prepare("
                    UPDATE validacao_whatsapp_queue 
                    SET status = ?, resultado = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$status, json_encode($resultado), $item['id']]);
            } else {
                // Incrementar tentativas
                $stmt = $pdo->prepare("
                    UPDATE validacao_whatsapp_queue 
                    SET status = 'pendente', tentativas = tentativas + 1, resultado = ? 
                    WHERE id = ?
                ");
                $stmt->execute([json_encode($resultado), $item['id']]);
            }
            
            // Delay entre validações
            sleep(1);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar fila de validação WhatsApp: " . $e->getMessage());
    }
}

/**
 * Validar telefone via API Evolution
 */
function validarTelefoneEvolution(string $telefone, int $session_id): array {
    try {
        // Buscar dados da sessão
        global $pdo;
        $stmt = $pdo->prepare("SELECT session_name, bot_token FROM sessions WHERE id = ?");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return ['sucesso' => false, 'erro' => 'Sessão não encontrada'];
        }
        
        // Chamar API do Evolution
        $url = "https://evolution.mariai.com.br/chat/whatsappNumbers/{$session['session_name']}";
        $data = ['numbers' => [$telefone]];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $session['bot_token']
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $response_data = json_decode($response, true);
            if (isset($response_data[0]['exists'])) {
                return [
                    'sucesso' => true,
                    'existe' => $response_data[0]['exists'],
                    'jid' => $response_data[0]['jid'] ?? null
                ];
            }
        }
        
        return ['sucesso' => false, 'erro' => 'Resposta inválida da API'];
        
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}