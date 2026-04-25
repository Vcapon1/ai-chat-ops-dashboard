
<?php
// Process AJAX requests for lead actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // Iniciar sessão se ainda não foi iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Incluir os arquivos necessários se não foram incluídos
    if (!function_exists('saveLeadNote')) {
        require_once __DIR__ . '/../db.php';
        require_once __DIR__ . '/../utils.php';
    }
    
    // Ensure content type is set to JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Disable output buffering to prevent any unexpected output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Debug log
    error_log("AJAX request received: " . $_POST['ajax_action']);
    
    $response = ['success' => false, 'message' => 'Ação inválida'];
    
    // Ensure we have the session ID for security
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
        exit;
    }
    
    $session_id = $_SESSION['user_id'];
    
    if (!isset($_POST['client_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID do cliente não fornecido']);
        exit;
    }
    
    $lead_id = intval($_POST['client_id']);
    
    try {
        if ($_POST['ajax_action'] === 'toggle_interested') {
            $currentValue = $_POST['current_value'] === '1' ? 1 : 0;
            $newInteressado = $currentValue === 1 ? 0 : 1;
            $newDesinteressado = $currentValue === 1 ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE clients SET interessado = ?, desinteressado = ? WHERE id = ? AND session_id = ?");
            if ($stmt->execute([$newInteressado, $newDesinteressado, $lead_id, $session_id])) {
                $response = [
                    'success' => true, 
                    'message' => 'Status atualizado com sucesso',
                    'new_state' => $newInteressado,
                    'new_label' => $newInteressado === 1 ? 'Interessado' : 'Não Interessado',
                    'new_class' => $newInteressado === 1 ? 'bg-green-600/20 text-green-400 border-green-500/30' : 'bg-red-600/20 text-red-400 border-red-500/30'
                ];
            }
        } elseif ($_POST['ajax_action'] === 'toggle_active_conversation') {
            $currentValue = $_POST['current_value'] === '1' ? 1 : 0;
            $newValue = $currentValue === 1 ? 0 : 1;
            
            $stmt = $pdo->prepare("UPDATE clients SET conversa_ativa = ? WHERE id = ? AND session_id = ?");
            if ($stmt->execute([$newValue, $lead_id, $session_id])) {
                $response = [
                    'success' => true, 
                    'message' => 'Bot ' . ($newValue === 1 ? 'ativado' : 'desativado') . ' com sucesso',
                    'new_state' => $newValue,
                    'new_label' => 'Bot ' . ($newValue === 1 ? 'ON' : 'OFF'),
                    'new_class' => $newValue === 1 ? 'bg-green-600/20 text-green-400 border-green-500/30' : 'bg-gray-600/20 text-gray-400 border-gray-500/30'
                ];
            }
        } elseif ($_POST['ajax_action'] === 'toggle_followup') {
            $currentValue = $_POST['current_value'] === '1' ? 1 : 0;
            $result = toggleFollowup($lead_id, $session_id, $currentValue, $pdo);
            
            if ($result['success']) {
                $newValue = $result['new_value'];
                $response = [
                    'success' => true, 
                    'message' => 'Follow-up ' . ($newValue === 1 ? 'ativado' : 'desativado') . ' com sucesso',
                    'new_state' => $newValue,
                    'new_label' => $result['new_label'],
                    'new_class' => $newValue === 0 ? 'bg-blue-600/20 text-blue-400 border-blue-500/30' : 'bg-amber-600/20 text-amber-400 border-amber-500/30'
                ];
            }
        } elseif ($_POST['ajax_action'] === 'create_lead') {
            $result = createLead($lead_id, $session_id, $pdo);
            
            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => 'Lead criado com sucesso',
                    'new_state' => 1,
                    'new_label' => 'Lead Criado',
                    'new_class' => 'bg-green-600/20 text-green-400 border-green-500/30'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao criar lead'
                ];
            }
        } elseif ($_POST['ajax_action'] === 'create_lead_crm') {
            $stmt = $pdo->prepare("SELECT enviado_crm FROM clients WHERE id = ? AND session_id = ?");
            $stmt->execute([$lead_id, $session_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['enviado_crm'] == 1) {
                $response = [
                    'success' => false,
                    'message' => 'Lead já está no CRM'
                ];
            } else {
                $_POST['client_id'] = $lead_id;
                
                ob_start();
                include __DIR__ . '/../integradores/CV.php';
                $api_response = ob_get_clean();
                
                if ($api_response && ($api_result = json_decode($api_response, true))) {
                    if (isset($api_result['success']) && $api_result['success']) {
                        $data_lead = isset($api_result['data_lead']) ? $api_result['data_lead'] : date('Y-m-d H:i:s');
                        
                        $stmt = $pdo->prepare("UPDATE clients SET enviado_crm = 1, lead_criado = 1, data_lead = ? WHERE id = ? AND session_id = ?");
                        if ($stmt->execute([$data_lead, $lead_id, $session_id])) {
                            $response = [
                                'success' => true,
                                'message' => 'Lead enviado para o CRM com sucesso',
                                'new_state' => 1,
                                'new_label' => 'No CRM',
                                'new_class' => 'bg-green-600/20 text-green-400 border-green-500/30'
                            ];
                        }
                    } else {
                        $error_message = isset($api_result['message']) ? $api_result['message'] : 'Erro ao conectar com o CRM';
                        $response = [
                            'success' => false,
                            'message' => $error_message
                        ];
                    }
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Erro na comunicação com o CRM: resposta inválida'
                    ];
                }
            }
        } elseif ($_POST['ajax_action'] === 'add_note') {
            // Debug log for note addition
            error_log("Tentando adicionar nota. POST data: " . print_r($_POST, true));
            
            if (!isset($_POST['note_content']) || empty(trim($_POST['note_content']))) {
                $response = [
                    'success' => false,
                    'message' => 'O conteúdo da anotação não pode estar vazio'
                ];
            } else {
                $note_content = trim($_POST['note_content']);
                error_log("Conteúdo da nota: " . $note_content);
                
                // Make sure the saveLeadNote function is available
                if (!function_exists('saveLeadNote')) {
                    error_log("A função saveLeadNote não existe");
                    $response = [
                        'success' => false,
                        'message' => 'Erro interno: função de salvar nota não disponível'
                    ];
                } else {
                    if (saveLeadNote($lead_id, $session_id, $note_content, $pdo)) {
                        $stmt = $pdo->prepare("SELECT * FROM lead_notes WHERE client_id = ? AND session_id = ? ORDER BY created_at DESC LIMIT 1");
                        $stmt->execute([$lead_id, $session_id]);
                        $new_note = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $response = [
                            'success' => true,
                            'message' => 'Anotação adicionada com sucesso',
                            'note' => [
                                'content' => nl2br(htmlspecialchars($new_note['note'])),
                                'date' => date('d/m/Y H:i:s', strtotime($new_note['created_at']))
                            ]
                        ];
                        error_log("Nota salva com sucesso");
                    } else {
                        $response = [
                            'success' => false,
                            'message' => 'Erro ao salvar anotação'
                        ];
                        error_log("Falha ao salvar nota");
                    }
                }
            }
        } else {
            error_log("Ação desconhecida: " . $_POST['ajax_action']);
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Erro ao processar ação AJAX: " . $e->getMessage());
        
        // Return a JSON error response
        $response = [
            'success' => false,
            'message' => 'Erro ao processar solicitação: ' . $e->getMessage()
        ];
    }
    
    // Debug the final response
    error_log("AJAX response: " . json_encode($response));
    
    // Ensure only JSON is outputted, nothing else
    echo json_encode($response);
    exit;
}
