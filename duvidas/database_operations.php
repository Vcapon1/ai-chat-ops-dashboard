
<?php
function deleteDuvida($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM fases WHERE id = ? AND sessao = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao excluir: " . $e->getMessage());
        return false;
    }
}

function editDuvida($pdo, $id, $titulo, $prompt, $intencao) {
    try {
        $stmt = $pdo->prepare("UPDATE fases SET titulo = ?, prompt = ?, intencao = ? WHERE id = ? AND sessao = ?");
        $stmt->execute([$titulo, $prompt, $intencao, $id, $_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao editar: " . $e->getMessage());
        return false;
    }
}

function addDuvida($pdo, $titulo, $prompt, $intencao) {
    try {
        $stmt = $pdo->prepare("INSERT INTO fases (titulo, prompt, tipo, sessao, intencao) VALUES (?, ?, 'DUVIDA', ?, ?)");
        $stmt->execute([$titulo, $prompt, $_SESSION['user_id'], $intencao]);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao inserir: " . $e->getMessage());
        return false;
    }
}

function getDuvidas($pdo, $intencao = null) {
    try {
        $params = [$_SESSION['user_id']];
        $sql = "SELECT * FROM fases WHERE tipo = 'DUVIDA' AND sessao = ?";
        
        if ($intencao !== null && $intencao !== '0') {
            $sql .= " AND intencao = ?";
            $params[] = $intencao;
        } elseif ($intencao === '0') {
            $sql .= " AND (intencao IS NULL OR intencao = 0)";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na query: " . $e->getMessage());
        return [];
    }
}

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
