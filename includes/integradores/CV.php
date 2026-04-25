
<?php
session_start();
include __DIR__ . '/../../includes/db.php';
include __DIR__ . '/../../includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$session_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'Erro ao processar solicitação'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Obter dados do cliente (lead)
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check if client_id is in JSON or POST
    $client_id = null;
    if (isset($data['client_id']) && !empty($data['client_id'])) {
        $client_id = $data['client_id'];
    } elseif (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
        $client_id = $_POST['client_id'];
    }
    
    if (!$client_id) {
        throw new Exception('ID do cliente não fornecido');
    }
    
    // Buscar informações do cliente
    $stmt = $pdo->prepare("SELECT c.client_name, c.client_number, c.client_intent, i.codigo_produto 
                          FROM clients c 
                          LEFT JOIN intencao i ON c.client_intent = i.id 
                          WHERE c.id = ? AND c.session_id = ?");
    $stmt->execute([$client_id, $session_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Cliente não encontrado');
    }
    
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar informações de integração CV
    $stmt = $pdo->prepare("SELECT * FROM integrators_cv WHERE id_session = ?");
    $stmt->execute([$session_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Configuração de integração não encontrada');
    }
    
    $cv_config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Formatar número de telefone usando nossa função formatPhoneForCRM
    $telefone = formatPhoneForCRM($cliente['client_number']);
    
    // Montar corpo da requisição
    $payload = [
        "nome" => $cliente['client_name'],
        "permitir_alteracao" => true,
        "telefone" => $telefone,
        "telefone_ddi" => "+55",
        "idempreendimento" => $cliente['codigo_produto'] ?? $cv_config['cod_emp'], // Usa código do produto se disponível
        "campos_adicionais" => [
            "cf_cf_mensagem" => $cliente['client_intent']
        ],
        "midia" => "WhatsApp",
        "reativar_lead" => true,
        "origem" => "MP",
        "midia_principal" => $cv_config['midia_principal'],
        "conversao" => $cv_config['conversao']
    ];
    
    // Configurar cabeçalhos
    $headers = [
        'token: ' . $cv_config['key_token'],
        'Content-Type: application/json',
        'accept: application/json',
        'email: ' . $cv_config['email']
    ];
    
    // Realizar requisição para o CRM
    $ch = curl_init($cv_config['url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $crm_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    // Registrar resposta
    error_log("Resposta da integração CV: " . $crm_response);
    error_log("HTTP Code: " . $http_code);
    
    if ($http_code >= 200 && $http_code < 300) {
        // Data atual para o campo data_lead
        $current_datetime = date('Y-m-d H:i:s');
        
        // Registrar o envio para o CRM no banco de dados e atualizar data_lead
        $stmt = $pdo->prepare("UPDATE clients SET enviado_crm = 1, data_lead = ? WHERE id = ? AND session_id = ?");
        $stmt->execute([$current_datetime, $client_id, $session_id]);
        
        $response = [
            'success' => true, 
            'message' => 'Lead enviado para o CRM com sucesso',
            'crm_response' => json_decode($crm_response, true),
            'data_lead' => $current_datetime
        ];
    } else {
        throw new Exception('Erro ao enviar lead para o CRM. Código: ' . $http_code);
    }
    
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    error_log("Erro ao enviar para CRM CV: " . $e->getMessage());
}

// Retornar resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
