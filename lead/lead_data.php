
<?php
// Check if the file already exists and we need to add the function
// We need to implement the saveLeadNote function which seems to be missing

function getLead($lead_id, $session_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ? AND session_id = ?");
        $stmt->execute([$lead_id, $session_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting lead: " . $e->getMessage());
        return false;
    }
}

function getSdrQuestions($lead_id, $session_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sdr_questions WHERE client_id = ? AND session_id = ? ORDER BY id ASC");
        $stmt->execute([$lead_id, $session_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting SDR questions: " . $e->getMessage());
        return [];
    }
}

function getLeadNotes($lead_id, $session_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM lead_notes WHERE client_id = ? AND session_id = ? ORDER BY created_at DESC");
        $stmt->execute([$lead_id, $session_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting lead notes: " . $e->getMessage());
        return [];
    }
}

function saveLeadNote($lead_id, $session_id, $note, $pdo) {
    try {
        // First check if the lead exists
        $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND session_id = ?");
        $checkStmt->execute([$lead_id, $session_id]);
        if ($checkStmt->rowCount() === 0) {
            error_log("Lead does not exist: {$lead_id} for session {$session_id}");
            return false;
        }
        
        // Insert the note
        $stmt = $pdo->prepare("INSERT INTO lead_notes (client_id, session_id, note, created_at) VALUES (?, ?, ?, NOW())");
        $result = $stmt->execute([$lead_id, $session_id, $note]);
        
        if ($result) {
            error_log("Note saved successfully for lead {$lead_id}");
            return true;
        } else {
            error_log("Failed to save note for lead {$lead_id}: " . print_r($stmt->errorInfo(), true));
            return false;
        }
    } catch (PDOException $e) {
        error_log("Error saving lead note: " . $e->getMessage());
        return false;
    }
}

function toggleFollowup($lead_id, $session_id, $current_value, $pdo) {
    try {
        $new_value = $current_value === 1 ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE clients SET conversa_descartada = ? WHERE id = ? AND session_id = ?");
        if ($stmt->execute([$new_value, $lead_id, $session_id])) {
            return [
                'success' => true,
                'new_value' => $new_value,
                'new_label' => 'Follow-up ' . ($new_value === 0 ? 'OFF' : 'ON')
            ];
        } else {
            return ['success' => false, 'message' => 'Erro ao atualizar follow-up'];
        }
    } catch (PDOException $e) {
        error_log("Error toggling followup: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao atualizar follow-up: ' . $e->getMessage()];
    }
}

function createLead($lead_id, $session_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT lead_criado FROM clients WHERE id = ? AND session_id = ?");
        $stmt->execute([$lead_id, $session_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['lead_criado'] == 1) {
            return [
                'success' => false,
                'message' => 'Lead já está criado'
            ];
        }
        
        $stmt = $pdo->prepare("UPDATE clients SET lead_criado = 1 WHERE id = ? AND session_id = ?");
        if ($stmt->execute([$lead_id, $session_id])) {
            return [
                'success' => true,
                'message' => 'Lead criado com sucesso'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erro ao criar lead'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error creating lead: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao criar lead: ' . $e->getMessage()];
    }
}
