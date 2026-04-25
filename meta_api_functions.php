<?php
/**
 * Funções auxiliares para Meta API (WhatsApp Business)
 */

function sendMessageMeta($pdo, $client_id, $template_name, $template_params, $session_id) {
    // Buscar dados do cliente
    $stmt = $pdo->prepare("SELECT c.client_name, c.client_number FROM clients c WHERE c.id = ? AND c.session_id = ?");
    $stmt->execute([$client_id, $session_id]);
    $client_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client_data) {
        error_log("Cliente não encontrado: {$client_id}");
        return false;
    }
    
    // Buscar configurações Meta API
    $stmt = $pdo->prepare("SELECT * FROM integrators_meta WHERE id_session = ?");
    $stmt->execute([$session_id]);
    $meta_config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$meta_config) {
        error_log("Configuração Meta API não encontrada para sessão: {$session_id}");
        return false;
    }
    
    // Formatar número de telefone
    $phone = preg_replace('/[^0-9]/', '', str_replace('@s.whatsapp.net', '', $client_data['client_number']));
    
    // Se for número brasileiro, garantir formato correto
    if (strlen($phone) == 11 && !str_starts_with($phone, '55')) {
        $phone = '55' . $phone;
    } elseif (strlen($phone) == 10 && !str_starts_with($phone, '55')) {
        $phone = '55' . substr($phone, 0, 2) . '9' . substr($phone, 2);
    }
    
    // Enviar mensagem via Meta API
    $result = enviarMensagemMetaAPI($phone, $template_name, $template_params, $meta_config);
    
    if ($result['success']) {
        // Registrar mensagem no banco
        $message_text = "Template: {$template_name}";
        if (!empty($template_params)) {
            $message_text .= " | Parâmetros: " . implode(', ', $template_params);
        }
        
        $stmt = $pdo->prepare("INSERT INTO mensagens (client_id, message, origem, created_at) VALUES (?, ?, 'ADM', NOW())");
        $stmt->execute([$client_id, $message_text]);
        
        // Atualizar status da conversa
        $stmt = $pdo->prepare("UPDATE clients SET conversa_ativa = 0 WHERE id = ? AND session_id = ?");
        $stmt->execute([$client_id, $session_id]);
        
        return true;
    }
    
    error_log("Erro ao enviar mensagem Meta: " . $result['message']);
    return false;
}

function enviarMensagemMetaAPI($telefone, $template_name, $template_params, $meta_config) {
    $api_url = "https://graph.facebook.com/v18.0/{$meta_config['phone_id']}/messages";
    
    // Montar estrutura da mensagem
    $message_data = [
        'messaging_product' => 'whatsapp',
        'to' => $telefone,
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
                'text' => (string)$param
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
    
    error_log("Enviando mensagem Meta para: " . $telefone);
    error_log("Template: " . $template_name);
    
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
        return [
            'success' => false,
            'message' => 'Erro CURL: ' . $curl_error
        ];
    }
    
    error_log("Meta API Response Code: " . $http_code);
    error_log("Meta API Response: " . $response);
    
    if ($http_code == 200) {
        $response_data = json_decode($response, true);
        
        if (isset($response_data['messages']) && !empty($response_data['messages'])) {
            return [
                'success' => true,
                'message' => 'Mensagem enviada com sucesso',
                'message_id' => $response_data['messages'][0]['id'] ?? null
            ];
        }
    }
    
    // Tratar erros da API
    $error_message = 'Erro desconhecido';
    if ($response) {
        $error_data = json_decode($response, true);
        if (isset($error_data['error'])) {
            $error_message = $error_data['error']['message'] ?? 'Erro da Meta API';
            if (isset($error_data['error']['error_data']['details'])) {
                $error_message .= ' - ' . $error_data['error']['error_data']['details'];
            }
        }
    }
    
    return [
        'success' => false,
        'message' => $error_message,
        'http_code' => $http_code
    ];
}

function verificarStatusTemplate($template_name, $meta_config) {
    $api_url = "https://graph.facebook.com/v18.0/{$meta_config['whatsapp_business_account_id']}/message_templates";
    
    $headers = [
        'Authorization: Bearer ' . $meta_config['access_token'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init($api_url . "?name={$template_name}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        if (isset($data['data']) && !empty($data['data'])) {
            return $data['data'][0]['status'] ?? 'UNKNOWN';
        }
    }
    
    return 'NOT_FOUND';
}

function listarTemplatesAprovados($meta_config) {
    $api_url = "https://graph.facebook.com/v18.0/{$meta_config['whatsapp_business_account_id']}/message_templates";
    
    $headers = [
        'Authorization: Bearer ' . $meta_config['access_token'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init($api_url . "?status=APPROVED");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        return $data['data'] ?? [];
    }
    
    return [];
}

function criarTemplateWhatsApp($template_data, $meta_config) {
    $api_url = "https://graph.facebook.com/v18.0/{$meta_config['whatsapp_business_account_id']}/message_templates";
    
    $headers = [
        'Authorization: Bearer ' . $meta_config['access_token'],
        'Content-Type: application/json'
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($template_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $data = json_decode($response, true);
        return [
            'success' => true,
            'template_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'PENDING'
        ];
    }
    
    $error_message = 'Erro ao criar template';
    if ($response) {
        $error_data = json_decode($response, true);
        if (isset($error_data['error'])) {
            $error_message = $error_data['error']['message'] ?? $error_message;
        }
    }
    
    return [
        'success' => false,
        'message' => $error_message,
        'http_code' => $http_code
    ];
}
?>