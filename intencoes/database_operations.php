<?php
/**
 * Database operations for intentions management
 */

/**
 * Get all intentions
 */
function getIntencoes($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM intencao WHERE sessao = ? OR sessao IS NULL ORDER BY titulo");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erro ao buscar intenções: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a new intention
 */
function addIntencao($pdo, $titulo, $descricao, $codigo_produto = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO intencao (titulo, descricao, codigo_produto, sessao) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$titulo, $descricao, $codigo_produto, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Erro ao adicionar intenção: " . $e->getMessage());
        return false;
    }
}

/**
 * Edit an existing intention
 */
function editIntencao($pdo, $id, $titulo, $descricao, $codigo_produto = null) {
    try {
        $stmt = $pdo->prepare("UPDATE intencao SET titulo = ?, descricao = ?, codigo_produto = ? WHERE id = ? AND (sessao = ? OR sessao IS NULL)");
        return $stmt->execute([$titulo, $descricao, $codigo_produto, $id, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Erro ao editar intenção: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete an intention
 */
function deleteIntencao($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM intencao WHERE id = ? AND (sessao = ? OR sessao IS NULL)");
        return $stmt->execute([$id, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log("Erro ao excluir intenção: " . $e->getMessage());
        return false;
    }
}