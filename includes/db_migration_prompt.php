
<?php
session_start();
require_once 'db.php';

try {
    // Verificar se a coluna 'instrucao' existe na tabela mensagens
    $check_instrucao = $pdo->query("SHOW COLUMNS FROM mensagens LIKE 'instrucao'");
    
    if ($check_instrucao->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN instrucao TEXT NULL AFTER message");
        echo "Coluna 'instrucao' adicionada com sucesso à tabela mensagens!<br>";
    } else {
        echo "A coluna 'instrucao' já existe na tabela mensagens.<br>";
    }
    
    // Verificar se a coluna 'motivo' existe e corrigir tipo se necessário
    $check_motivo = $pdo->query("SHOW COLUMNS FROM mensagens LIKE 'motivo'");
    
    if ($check_motivo->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN motivo TEXT NULL AFTER instrucao");
        echo "Coluna 'motivo' adicionada com sucesso à tabela mensagens!<br>";
    } else {
        // Coluna existe, vamos garantir que seja do tipo TEXT
        $pdo->exec("ALTER TABLE mensagens MODIFY COLUMN motivo TEXT NULL");
        echo "Tipo da coluna 'motivo' atualizado para TEXT!<br>";
    }
    
    // Verificar se a coluna 'acao' existe e corrigir tipo se necessário
    $check_acao = $pdo->query("SHOW COLUMNS FROM mensagens LIKE 'acao'");
    
    if ($check_acao->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE mensagens ADD COLUMN acao INT NULL AFTER motivo");
        echo "Coluna 'acao' adicionada com sucesso à tabela mensagens!<br>";
    } else {
        // Coluna existe, mas vamos garantir que seja do tipo INT
        $pdo->exec("ALTER TABLE mensagens MODIFY COLUMN acao INT NULL");
        echo "Tipo da coluna 'acao' atualizado para INT!<br>";
    }
    
    // Adicionar debug para verificar estrutura final
    echo "<h2>Estrutura atual da tabela mensagens:</h2>";
    $columns = $pdo->query("SHOW COLUMNS FROM mensagens");
    echo "<pre>";
    print_r($columns->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    echo "Verificação concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}
?>
