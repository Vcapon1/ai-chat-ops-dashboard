<?php
require_once 'db.php';

try {
    // Verificar se a coluna codigo_produto existe na tabela intencao
    $check_column = $pdo->query("SHOW COLUMNS FROM intencao LIKE 'codigo_produto'");
    if ($check_column->rowCount() === 0) {
        // A coluna não existe, então vamos criá-la
        $pdo->exec("ALTER TABLE intencao ADD COLUMN codigo_produto VARCHAR(100) DEFAULT NULL");
        echo "Coluna codigo_produto adicionada à tabela intencao com sucesso!<br>";
    } else {
        echo "Coluna codigo_produto já existe na tabela intencao.<br>";
    }
    
    echo "Migração de código de produto para intenções concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro na migração de código de produto: " . $e->getMessage();
}