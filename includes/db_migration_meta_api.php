<?php
require_once 'db.php';

try {
    // Verificar se a tabela integrators_meta existe
    $check_meta_table = $pdo->query("SHOW TABLES LIKE 'integrators_meta'");
    if ($check_meta_table->rowCount() === 0) {
        // Criar tabela para configurações da Meta API
        $pdo->exec("CREATE TABLE integrators_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_session INT NOT NULL,
            access_token VARCHAR(500) NOT NULL,
            phone_id VARCHAR(100) NOT NULL,
            whatsapp_business_account_id VARCHAR(100) NOT NULL,
            webhook_token VARCHAR(255) DEFAULT NULL,
            app_secret VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_session) REFERENCES sessions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Tabela integrators_meta criada com sucesso!<br>";
    }
    
    // Verificar se a tabela message_templates existe
    $check_templates_table = $pdo->query("SHOW TABLES LIKE 'message_templates'");
    if ($check_templates_table->rowCount() === 0) {
        // Criar tabela para templates de mensagens
        $pdo->exec("CREATE TABLE message_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_session INT NOT NULL,
            template_name VARCHAR(100) NOT NULL,
            template_language VARCHAR(10) DEFAULT 'pt_BR',
            template_category VARCHAR(50) DEFAULT 'MARKETING',
            template_status VARCHAR(20) DEFAULT 'PENDING',
            template_body TEXT NOT NULL,
            template_parameters JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_session) REFERENCES sessions(id) ON DELETE CASCADE,
            UNIQUE KEY unique_template_session (template_name, id_session)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Tabela message_templates criada com sucesso!<br>";
    }
    
    // Adicionar coluna para indicar qual API usar (Evolution ou Meta)
    $check_api_type_column = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'api_type'");
    if ($check_api_type_column->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sessions ADD COLUMN api_type ENUM('evolution', 'meta') DEFAULT 'evolution'");
        echo "Coluna api_type adicionada à tabela sessions com sucesso!<br>";
    }
    
    echo "Migração da Meta API concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro na migração da Meta API: " . $e->getMessage();
}