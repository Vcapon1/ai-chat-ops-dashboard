<?php
/**
 * Gerenciador de mensagens personalizadas via OpenAI
 */

/**
 * Gerar mensagem personalizada via OpenAI
 */
function gerarMensagemPersonalizada(string $nome_cliente, string $contexto_empresa, string $prompt_base, string $openai_key): array {
    try {
        $prompt = "
        Você é um assistente de marketing especializado em mensagens personalizadas para WhatsApp.
        
        Empresa: {$contexto_empresa}
        Cliente: {$nome_cliente}
        
        Baseado no seguinte prompt: {$prompt_base}
        
        Crie uma mensagem personalizada, calorosa e profissional para este cliente específico.
        A mensagem deve:
        - Ser dirigida especificamente ao cliente pelo nome
        - Manter tom profissional mas amigável
        - Ter máximo 300 caracteres
        - Incluir uma call-to-action clara
        - Ser relevante para o contexto da empresa
        
        Retorne apenas a mensagem, sem explicações adicionais.
        ";
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Você é um especialista em marketing digital e criação de mensagens personalizadas para WhatsApp.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 150,
            'temperature' => 0.7
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $response_data = json_decode($response, true);
            if (isset($response_data['choices'][0]['message']['content'])) {
                return [
                    'sucesso' => true,
                    'mensagem' => trim($response_data['choices'][0]['message']['content'])
                ];
            }
        }
        
        return ['sucesso' => false, 'erro' => 'Resposta inválida da OpenAI'];
        
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}

/**
 * Processar campanha com mensagens personalizadas por IA
 */
function processarCampanhaComIA(PDO $pdo_disparador, PDO $pdo, array $campanha): void {
    try {
        error_log("Processando campanha com IA: " . $campanha['nome_campanha']);
        
        // Buscar configurações da sessão
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
        $stmt->execute([$campanha['session_id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session || !$session['openai_key']) {
            error_log("Sessão não encontrada ou chave OpenAI não configurada");
            return;
        }
        
        // Buscar contatos da campanha
        $tabela_envios = "envios_campanha_{$campanha['session_id']}_{$campanha['id']}";
        $stmt = $pdo_disparador->prepare("
            SELECT * FROM `{$tabela_envios}` 
            WHERE enviado = 0 
            ORDER BY id ASC 
            LIMIT 10
        ");
        $stmt->execute();
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($contatos as $contato) {
            // Verificar se tem créditos para IA (2 créditos)
            if (!temCreditosSuficientes($pdo, $campanha['session_id'], 2)) {
                error_log("Créditos insuficientes para IA para sessão: " . $campanha['session_id']);
                break;
            }
            
            // Gerar mensagem personalizada
            $resultado_ia = gerarMensagemPersonalizada(
                $contato['nome'] ?? 'Cliente',
                $session['company_name'] ?? 'Nossa empresa',
                $campanha['mensagem'],
                $session['openai_key']
            );
            
            if ($resultado_ia['sucesso']) {
                // Debitar créditos da IA
                debitarCreditos($pdo, $campanha['session_id'], 'mensagem_ia', 2, 
                    "Mensagem IA para: " . $contato['nome'], $campanha['id']);
                
                // Enviar mensagem personalizada
                $sucesso_envio = enviarMensagemEvolution(
                    $contato['telefone'], 
                    $resultado_ia['mensagem'], 
                    $session['session_name'], 
                    $session['bot_token']
                );
                
                if ($sucesso_envio) {
                    // Debitar créditos do envio (3 créditos)
                    debitarCreditos($pdo, $campanha['session_id'], 'envio_mensagem', 3, 
                        "Envio para: " . $contato['telefone'], $campanha['id']);
                    
                    // Atualizar registro de envio
                    $stmt = $pdo_disparador->prepare("
                        UPDATE `{$tabela_envios}` 
                        SET enviado = 1, data_envio = NOW(), mensagem_personalizada = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$resultado_ia['mensagem'], $contato['id']]);
                    
                    // Atualizar contador da campanha
                    $stmt = $pdo_disparador->prepare("
                        UPDATE campanhas 
                        SET qtd_enviados = qtd_enviados + 1 
                        WHERE id = ?
                    ");
                    $stmt->execute([$campanha['id']]);
                    
                    error_log("Mensagem IA enviada para: " . $contato['telefone']);
                } else {
                    error_log("Falha ao enviar mensagem IA para: " . $contato['telefone']);
                }
            } else {
                error_log("Falha ao gerar mensagem IA: " . $resultado_ia['erro']);
            }
            
            // Delay entre envios
            sleep(30);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao processar campanha com IA: " . $e->getMessage());
    }
}