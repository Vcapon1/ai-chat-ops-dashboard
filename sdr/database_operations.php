
<?php
function deleteFase($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM fases WHERE id = ? AND sessao = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao excluir: " . $e->getMessage());
        return false;
    }
}

function editFase($pdo, $id, $titulo, $prompt, $referencia, $intencao = 0) {
    try {
        $stmt = $pdo->prepare("UPDATE fases SET titulo = ?, prompt = ?, referencia = ?, intencao = ? WHERE id = ? AND sessao = ?");
        $stmt->execute([$titulo, $prompt, $referencia, $intencao, $id, $_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao editar: " . $e->getMessage());
        return false;
    }
}

function moveFase($pdo, $id, $direction) {
    try {
        // Buscar posição atual
        $stmt = $pdo->prepare("SELECT posicao FROM fases WHERE id = ? AND sessao = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $currentPos = $stmt->fetch(PDO::FETCH_ASSOC)['posicao'];
        
        // Calcular nova posição
        $newPos = $direction === 'up' ? $currentPos - 1 : $currentPos + 1;
        
        // Atualizar posição do outro item
        $stmt = $pdo->prepare("UPDATE fases SET posicao = ? WHERE posicao = ? AND tipo = 'SDR' AND sessao = ?");
        $stmt->execute([$currentPos, $newPos, $_SESSION['user_id']]);
        
        // Atualizar posição do item atual
        $stmt = $pdo->prepare("UPDATE fases SET posicao = ? WHERE id = ? AND sessao = ?");
        $stmt->execute([$newPos, $id, $_SESSION['user_id']]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao reordenar: " . $e->getMessage());
        return false;
    }
}

function addFase($pdo, $titulo, $prompt, $referencia, $intencao = 0) {
    try {
        // Pegar a última posição
        $stmt = $pdo->query("SELECT MAX(posicao) as max_pos FROM fases WHERE tipo = 'SDR' AND sessao = " . $_SESSION['user_id']);
        $maxPos = $stmt->fetch(PDO::FETCH_ASSOC)['max_pos'];
        $newPos = $maxPos ? $maxPos + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO fases (titulo, prompt, referencia, tipo, posicao, sessao, intencao) VALUES (?, ?, ?, 'SDR', ?, ?, ?)");
        $stmt->execute([$titulo, $prompt, $referencia, $newPos, $_SESSION['user_id'], $intencao]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao inserir: " . $e->getMessage());
        return false;
    }
}

function getFases($pdo, $intencao = null) {
    try {
        $sql = "SELECT * FROM fases WHERE tipo = 'SDR' AND sessao = ?";
        
        // Add intention filter if specified
        if ($intencao !== null && $intencao != '0') {
            $sql .= " AND intencao = ?";
            $stmt = $pdo->prepare($sql . " ORDER BY posicao");
            $stmt->execute([$_SESSION['user_id'], $intencao]);
        } else {
            $sql .= " AND (intencao = 0 OR intencao IS NULL)";
            $stmt = $pdo->prepare($sql . " ORDER BY posicao");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na query: " . $e->getMessage());
        return [];
    }
}

// Função para buscar intenções considerando a sessão do usuário
function getIntencoes($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM intencao WHERE sessao = ? OR sessao IS NULL ORDER BY titulo");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar intenções: " . $e->getMessage());
        return [];
    }
}
