
<?php
session_start();
include 'db.php';
include 'utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../leads.php');
    exit;
}

$session_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'Erro ao processar solicitação'];

try {
    // Capturar dados do formulário
    $client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
    $client_number = isset($_POST['client_number']) ? trim($_POST['client_number']) : '';
    $client_intent = isset($_POST['client_intent']) ? trim($_POST['client_intent']) : '';
    
    // Validar dados mínimos
    if (empty($client_number)) {
        throw new Exception('O número de telefone é obrigatório');
    }
    
    // Limpar número de telefone para formato padrão
    $client_number = preg_replace('/[^0-9]/', '', $client_number);
    
    // Adicionar sufixo WhatsApp se não existir
    if (strpos($client_number, '@s.whatsapp.net') === false) {
        $client_number .= '@s.whatsapp.net';
    }
    
    // Verificar se o lead já existe para esta sessão
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE client_number = ? AND session_id = ?");
    $stmt->execute([$client_number, $session_id]);
    
    if ($stmt->rowCount() > 0) {
        // Lead já existe, retornar mensagem
        $lead_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        $response = [
            'success' => false, 
            'message' => 'Este lead já existe no sistema',
            'lead_id' => $lead_id
        ];
    } else {
        // Inserir novo lead
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
                conversa_ativa,
                data_lead
            ) VALUES (?, ?, ?, ?, 0, NOW(), 0, 0, 1, NULL)
        ");
        
        if ($stmt->execute([
            $session_id, 
            $client_number, 
            $client_name, 
            $client_intent
        ])) {
            $lead_id = $pdo->lastInsertId();
            $response = [
                'success' => true, 
                'message' => 'Lead criado com sucesso',
                'lead_id' => $lead_id
            ];
        }
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    error_log("Erro ao criar lead: " . $e->getMessage());
}

// Retornar resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
