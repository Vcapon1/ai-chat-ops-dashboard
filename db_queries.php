
<?php
function getConversationsCount($pdo, $where_conditions, $params) {
    $count_query = "SELECT COUNT(DISTINCT c.id) as total 
                    FROM clients c 
                    LEFT JOIN (
                        SELECT client_id, MAX(created_at) as last_message
                        FROM mensagens
                        GROUP BY client_id
                    ) m ON m.client_id = c.id 
                    WHERE " . implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function getConversations($pdo, $where_conditions, $params, $items_per_page, $offset) {
    // Removemos os últimos 2 parâmetros que serão usados para paginação
    $query_params = array_merge($params);
    
    $query = "SELECT c.*, m.message as ultima_mensagem, m.created_at as horario, 
                    m.instrucao, m.motivo, m.acao, m.origem,
                    s.session_name, s.bot_token
              FROM clients c 
              LEFT JOIN (
                  SELECT id, client_id, message, created_at, instrucao, motivo, acao, origem,
                         ROW_NUMBER() OVER (PARTITION BY client_id ORDER BY created_at DESC) as rn
                  FROM mensagens
              ) m ON m.client_id = c.id AND m.rn = 1
              LEFT JOIN sessions s ON s.id = c.session_id
              WHERE " . implode(" AND ", $where_conditions) . "
              ORDER BY m.created_at DESC 
              LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($query);
    
    // Vincula os parâmetros da consulta
    foreach ($query_params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    
    // Adiciona os parâmetros de paginação
    $stmt->bindValue(count($query_params) + 1, (int)$items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($query_params) + 2, (int)$offset, PDO::PARAM_INT);
    
    // Debug da consulta
    error_log("SQL Query: " . $query);
    error_log("Parameters: " . json_encode($query_params));
    error_log("Pagination: " . $items_per_page . " offset " . $offset);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateConversationStatus($pdo, $client_id, $new_status, $session_id) {
    $stmt = $pdo->prepare("UPDATE clients SET conversa_ativa = ? WHERE id = ? AND session_id = ?");
    return $stmt->execute([$new_status, $client_id, $session_id]);
}

function sendMessage($pdo, $client_id, $message, $session_id, $instrucao = null, $motivo = null, $acao = null) {
    // Verificar qual tipo de API está configurado
    $stmt = $pdo->prepare("SELECT s.api_type, s.session_name, s.bot_token FROM sessions s WHERE s.id = ?");
    $stmt->execute([$session_id]);
    $session_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session_data) {
        return false;
    }
    
    // Se for Meta API, usar sistema de templates
    if ($session_data['api_type'] === 'meta') {
        require_once 'meta_api_functions.php';
        
        // Extrair template e parâmetros da mensagem
        if (strpos($message, 'TEMPLATE:') === 0) {
            $template_data = substr($message, 9);
            if (strpos($template_data, '|') !== false) {
                list($template_name, $params_str) = explode('|', $template_data, 2);
                $params = !empty($params_str) ? explode(',', $params_str) : [];
            } else {
                $template_name = $template_data;
                $params = [];
            }
        } else {
            // Usar template padrão se não especificado
            $template_name = 'mensagem_generica';
            $params = [$message];
        }
        
        return sendMessageMeta($pdo, $client_id, trim($template_name), array_map('trim', $params), $session_id);
    }
    
    // Usar Evolution API (código original)
    $stmt = $pdo->prepare("SELECT c.client_number FROM clients c WHERE c.id = ? AND c.session_id = ?");
    $stmt->execute([$client_id, $session_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client_data) {
        return false;
    }

    $phone = preg_replace('/[^0-9]/', '', str_replace('@s.whatsapp.net', '', $client_data['client_number']));
    
    if (strlen($phone) <= 12) {
        $phone = "55" . $phone;
    }
    
    $api_url = "http://45.239.42.53:8080/message/sendText/{$session_data['session_name']}";
    $headers = [
        'apikey: ' . $session_data['bot_token'],
        'Content-Type: application/json'
    ];
    
    if (strpos($phone, '@s.whatsapp.net') === false) {
        $phone = $phone . '@s.whatsapp.net';
    }
    
    $post_data = json_encode([
        'number' => $phone,
        'text' => $message
    ]);
    
    error_log("Sending WhatsApp message to: " . $phone);
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($http_code == 200) {
        // Incluir os campos instrucao, motivo e acao na inserção
        $stmt = $pdo->prepare("INSERT INTO mensagens (client_id, message, origem, created_at, instrucao, motivo, acao) VALUES (?, ?, 'ADM', NOW(), ?, ?, ?)");
        $stmt->execute([$client_id, $message, $instrucao, $motivo, $acao]);
        
        $stmt = $pdo->prepare("UPDATE clients SET conversa_ativa = 0 WHERE id = ? AND session_id = ?");
        $stmt->execute([$client_id, $session_id]);
        return true;
    }
    
    return false;
}

/**
 * Cria um novo lead no sistema
 * 
 * @param PDO $pdo Conexão com o banco de dados
 * @param array $leadData Dados do lead
 * @param int $session_id ID da sessão atual
 * @return array Resposta da operação
 */
function createLead($pdo, $leadData, $session_id) {
    try {
        if (empty($leadData['client_number'])) {
            return ['success' => false, 'message' => 'O número de telefone é obrigatório'];
        }
        
        $client_number = preg_replace('/[^0-9]/', '', $leadData['client_number']);
        
        if (strpos($client_number, '@s.whatsapp.net') === false) {
            $client_number .= '@s.whatsapp.net';
        }
        
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE client_number = ? AND session_id = ?");
        $stmt->execute([$client_number, $session_id]);
        
        if ($stmt->rowCount() > 0) {
            $lead_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            return [
                'success' => false, 
                'message' => 'Este lead já existe no sistema',
                'lead_id' => $lead_id
            ];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO clients (
                session_id, 
                client_number, 
                client_name, 
                client_intent, 
                lead_score, 
                last_interaction, 
                interessado, 
                desinteressado,
                conversa_ativa
            ) VALUES (?, ?, ?, ?, 0, NOW(), 0, 0, 1)
        ");
        
        if ($stmt->execute([
            $session_id, 
            $client_number, 
            $leadData['client_name'] ?? '', 
            $leadData['client_intent'] ?? ''
        ])) {
            $lead_id = $pdo->lastInsertId();
            return [
                'success' => true, 
                'message' => 'Lead criado com sucesso',
                'lead_id' => $lead_id
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao inserir lead no banco de dados'];
        }
        
    } catch (PDOException $e) {
        error_log("Erro ao criar lead: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao criar lead: ' . $e->getMessage()];
    }
}
