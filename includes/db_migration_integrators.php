
<?php
require_once 'db.php';

try {
    // Verificar se a tabela integrators_cv existe
    $check_integrators_table = $pdo->query("SHOW TABLES LIKE 'integrators_cv'");
    if ($check_integrators_table->rowCount() === 0) {
        // A tabela não existe, então vamos criá-la
        $pdo->exec("CREATE TABLE integrators_cv (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_session INT NOT NULL,
            url VARCHAR(255) NOT NULL,
            key_token VARCHAR(255) NOT NULL,
            cod_emp VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            midia_principal VARCHAR(100) DEFAULT 'Bot WhatsApp',
            conversao VARCHAR(100) DEFAULT 'Mídia Paga | Bot WhatsApp',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_session) REFERENCES sessions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Tabela integrators_cv criada com sucesso!<br>";
    }
    
    // Verificar se a coluna enviado_crm existe na tabela clients
    $check_column = $pdo->query("SHOW COLUMNS FROM clients LIKE 'enviado_crm'");
    if ($check_column->rowCount() === 0) {
        // A coluna não existe, então vamos criá-la
        $pdo->exec("ALTER TABLE clients ADD COLUMN enviado_crm TINYINT(1) DEFAULT 0");
        echo "Coluna enviado_crm adicionada à tabela clients com sucesso!<br>";
    }
    
    echo "Migração de integradores concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro na migração de integradores: " . $e->getMessage();
}
