<?php
// Script temporário para executar a migração
echo "<h2>Executando Migração do Banco Disparador</h2>\n";
echo "<pre>\n";

require_once 'includes/db_disparador.php';

try {
    // Executar migração do banco disparador
    echo "Executando migração do banco disparador...\n";
    include 'includes/db_migration_disparador.php';
    echo "\nMigração do banco disparador concluída.\n\n";
    // Verificar e adicionar colunas que podem estar faltando nas campanhas
    $missing_columns = [
        'nome_tabela_envio' => "ALTER TABLE dis_campanhas ADD COLUMN nome_tabela_envio VARCHAR(255)",
        'data_disparo_inicio' => "ALTER TABLE dis_campanhas ADD COLUMN data_disparo_inicio DATETIME NULL",
        'data_disparo_fim' => "ALTER TABLE dis_campanhas ADD COLUMN data_disparo_fim DATETIME NULL",
        'cancelamento_ativo' => "ALTER TABLE dis_campanhas ADD COLUMN cancelamento_ativo BOOLEAN DEFAULT TRUE"
    ];
    
    foreach ($missing_columns as $column => $query) {
        try {
            $pdo_disparador->exec($query);
            echo "Coluna {$column} adicionada com sucesso.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "Coluna {$column} já existe.\n";
            } else {
                echo "Erro ao adicionar coluna {$column}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Executar migração completa
    require_once 'includes/db_migration_disparador.php';
    
} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}

echo "\n</pre>\n";
echo "<p><strong>Migração concluída!</strong> Você pode excluir este arquivo agora.</p>";