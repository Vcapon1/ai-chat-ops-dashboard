
<?php
require_once 'db.php';

try {
    // Verificar se a coluna assist_name existe na tabela sessions
    $check_column = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'assist_name'");
    if ($check_column->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE sessions ADD COLUMN assist_name VARCHAR(100) NULL AFTER n_adm");
        echo "Coluna assist_name adicionada com sucesso!<br>";
    }
    
    // Verificar se a coluna prompt_lead existe na tabela sessions
    $check_prompt_lead = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'prompt_lead'");
    if ($check_prompt_lead->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE sessions ADD COLUMN prompt_lead TEXT NULL AFTER prompt_saudacao");
        echo "Coluna prompt_lead adicionada com sucesso!<br>";
    }
    
    // Verificar se a coluna prompt_descarte existe na tabela sessions
    $check_prompt_descarte = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'prompt_descarte'");
    if ($check_prompt_descarte->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE sessions ADD COLUMN prompt_descarte TEXT NULL AFTER prompt_lead");
        echo "Coluna prompt_descarte adicionada com sucesso!<br>";
    }
    
    // Verificar se a tabela SDR_CLI existe
    $check_sdr_table = $pdo->query("SHOW TABLES LIKE 'SDR_CLI'");
    if ($check_sdr_table->rowCount() === 0) {
        // A tabela não existe, então vamos criá-la
        $pdo->exec("CREATE TABLE SDR_CLI (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            session_id INT NOT NULL,
            pergunta TEXT NOT NULL,
            resposta TEXT NULL,
            posicao INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Tabela SDR_CLI criada com sucesso!<br>";
    } else {
        // Verificar se a tabela tem a coluna 'posicao' e não 'order'
        $check_posicao = $pdo->query("SHOW COLUMNS FROM SDR_CLI LIKE 'posicao'");
        if ($check_posicao->rowCount() === 0) {
            // Verificar se existe coluna 'order'
            $check_order = $pdo->query("SHOW COLUMNS FROM SDR_CLI LIKE 'order'");
            if ($check_order->rowCount() > 0) {
                // Renomear a coluna de 'order' para 'posicao'
                $pdo->exec("ALTER TABLE SDR_CLI CHANGE `order` posicao INT NOT NULL DEFAULT 0");
                echo "Coluna 'order' renomeada para 'posicao' com sucesso!<br>";
            } else {
                // Adicionar coluna 'posicao' se não existir
                $pdo->exec("ALTER TABLE SDR_CLI ADD COLUMN posicao INT NOT NULL DEFAULT 0");
                echo "Coluna 'posicao' adicionada com sucesso!<br>";
            }
        }
    }
    
    // Verificar se a coluna conversa_ativa existe na tabela clients
    $check_conversa_ativa = $pdo->query("SHOW COLUMNS FROM clients LIKE 'conversa_ativa'");
    if ($check_conversa_ativa->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE clients ADD COLUMN conversa_ativa TINYINT(1) DEFAULT 0 AFTER desinteressado");
        echo "Coluna conversa_ativa adicionada com sucesso!<br>";
    }
    
    // Verificar se a coluna enviado_crm existe na tabela clients
    $check_enviado_crm = $pdo->query("SHOW COLUMNS FROM clients LIKE 'enviado_crm'");
    if ($check_enviado_crm->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE clients ADD COLUMN enviado_crm TINYINT(1) DEFAULT 0 AFTER conversa_ativa");
        echo "Coluna enviado_crm adicionada com sucesso!<br>";
    }
    
    // Verificar se a coluna data_lead existe na tabela clients
    $check_data_lead = $pdo->query("SHOW COLUMNS FROM clients LIKE 'data_lead'");
    if ($check_data_lead->rowCount() === 0) {
        // A coluna não existe, então vamos adicioná-la
        $pdo->exec("ALTER TABLE clients ADD COLUMN data_lead DATETIME NULL AFTER enviado_crm");
        echo "Coluna data_lead adicionada com sucesso!<br>";
    }
    
    echo "Migração concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}
