<?php
/**
 * Migração das tabelas do banco disparador
 */

require_once 'db_disparador.php';

try {
    // Criar tabela dis_listas
    $sql_listas = "CREATE TABLE IF NOT EXISTS dis_listas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_session INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        total_contatos INT DEFAULT 0,
        INDEX idx_session (id_session)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo_disparador->exec($sql_listas);
    echo "Tabela dis_listas criada/verificada com sucesso.\n";

    // Criar tabela dis_campanhas
    $sql_campanhas = "CREATE TABLE IF NOT EXISTS dis_campanhas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_session INT NOT NULL,
        nome_campanha VARCHAR(255) NOT NULL,
        id_lista INT NOT NULL,
        mensagem TEXT NOT NULL,
        data_agendada DATETIME NOT NULL,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        foi_disparada TINYINT(1) DEFAULT 0,
        qtd_enviados INT DEFAULT 0,
        qtd_programados INT DEFAULT 0,
        nome_tabela_envio VARCHAR(255),
        data_disparo_inicio DATETIME NULL,
        data_disparo_fim DATETIME NULL,
        cancelamento_ativo BOOLEAN DEFAULT TRUE,
        INDEX idx_session (id_session),
        INDEX idx_lista (id_lista),
        FOREIGN KEY (id_lista) REFERENCES dis_listas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo_disparador->exec($sql_campanhas);
    echo "Tabela dis_campanhas criada/verificada com sucesso.\n";

    // Adicionar novos campos se não existirem (para campanhas existentes)
    $alter_queries = [
        "ALTER TABLE dis_campanhas ADD COLUMN nome_tabela_envio VARCHAR(255)",
        "ALTER TABLE dis_campanhas ADD COLUMN data_disparo_inicio DATETIME NULL",
        "ALTER TABLE dis_campanhas ADD COLUMN data_disparo_fim DATETIME NULL", 
        "ALTER TABLE dis_campanhas ADD COLUMN cancelamento_ativo BOOLEAN DEFAULT TRUE",
        "ALTER TABLE dis_campanhas ADD COLUMN templates_selecionados TEXT NULL COMMENT 'JSON array com templates Meta selecionados'"
    ];
    
    foreach ($alter_queries as $query) {
        try {
            $pdo_disparador->exec($query);
        } catch (PDOException $e) {
            // Ignorar erro se coluna já existir
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Aviso: " . $e->getMessage() . "\n";
            }
        }
    }

    // Criar tabela dis_logs_envio
    $sql_logs = "CREATE TABLE IF NOT EXISTS dis_logs_envio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_campanha INT NOT NULL,
        id_contato INT NOT NULL,
        telefone VARCHAR(20) NOT NULL,
        status ENUM('enviado', 'entregue', 'lido', 'respondido', 'erro') DEFAULT 'enviado',
        data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        mensagem_enviada TEXT,
        resposta TEXT NULL,
        data_resposta TIMESTAMP NULL,
        INDEX idx_campanha (id_campanha),
        INDEX idx_telefone (telefone),
        FOREIGN KEY (id_campanha) REFERENCES dis_campanhas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo_disparador->exec($sql_logs);
    echo "Tabela dis_logs_envio criada/verificada com sucesso.\n";

    // Criar tabela de blacklist global
    $sql_blacklist = "CREATE TABLE IF NOT EXISTS dis_blacklist_optout (
        id INT AUTO_INCREMENT PRIMARY KEY,
        telefone VARCHAR(20) NOT NULL,
        data_cancelamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        id_session INT NOT NULL,
        id_campanha INT,
        UNIQUE KEY unique_telefone_session (telefone, id_session),
        INDEX idx_session (id_session)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo_disparador->exec($sql_blacklist);
    echo "Tabela dis_blacklist_optout criada/verificada com sucesso.\n";

    // Adicionar colunas de validação WhatsApp na tabela de contatos
    $whatsapp_columns = [
        "ALTER TABLE dis_contatos ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_contatos ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1",
        "ALTER TABLE dis_contatos ADD COLUMN data_validacao TIMESTAMP NULL"
    ];
    
    foreach ($whatsapp_columns as $query) {
        try {
            $pdo_disparador->exec($query);
            echo "Coluna WhatsApp adicionada com sucesso.\n";
        } catch (PDOException $e) {
            // Ignorar erro se coluna já existir
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Aviso WhatsApp: " . $e->getMessage() . "\n";
            }
        }
    }

    // Adicionar colunas de controle de validação na tabela de listas
    $validation_columns = [
        "ALTER TABLE dis_listas ADD COLUMN validar_necessario TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_listas ADD COLUMN validacao_iniciada TINYINT(1) DEFAULT 0", 
        "ALTER TABLE dis_listas ADD COLUMN validacao_concluida TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_listas ADD COLUMN custo_validacao INT DEFAULT 0",
        "ALTER TABLE dis_listas ADD COLUMN data_validacao_inicio DATETIME NULL",
        "ALTER TABLE dis_listas ADD COLUMN data_validacao_fim DATETIME NULL",
        "ALTER TABLE dis_listas ADD COLUMN validar_nome_ia TINYINT(1) DEFAULT 0 COMMENT 'Flag para indicar se algum contato desta lista precisa validação de nome por IA'"
    ];
    
    foreach ($validation_columns as $query) {
        try {
            $pdo_disparador->exec($query);
            echo "Coluna de validação adicionada com sucesso.\n";
        } catch (PDOException $e) {
            // Ignorar erro se coluna já existir
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Aviso validação: " . $e->getMessage() . "\n";
            }
        }
    }

    // Adicionar colunas de validação nas tabelas de contatos
    $contact_validation_columns = [
        "ALTER TABLE dis_contatos_1 ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_contatos_1 ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1", 
        "ALTER TABLE dis_contatos_1 ADD COLUMN data_validacao DATETIME NULL",
        "ALTER TABLE dis_contatos_1 ADD COLUMN motivo_inativacao TEXT NULL",
        "ALTER TABLE dis_contatos_2 ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_contatos_2 ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1",
        "ALTER TABLE dis_contatos_2 ADD COLUMN data_validacao DATETIME NULL",
        "ALTER TABLE dis_contatos_2 ADD COLUMN motivo_inativacao TEXT NULL",
        "ALTER TABLE dis_contatos_3 ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_contatos_3 ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1",
        "ALTER TABLE dis_contatos_3 ADD COLUMN data_validacao DATETIME NULL",
        "ALTER TABLE dis_contatos_3 ADD COLUMN motivo_inativacao TEXT NULL",
        "ALTER TABLE dis_contatos_4 ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_contatos_4 ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1",
        "ALTER TABLE dis_contatos_4 ADD COLUMN data_validacao DATETIME NULL",
        "ALTER TABLE dis_contatos_4 ADD COLUMN motivo_inativacao TEXT NULL",
        "ALTER TABLE dis_contatos_5 ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0",
        "ALTER TABLE dis_contatos_5 ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1",
        "ALTER TABLE dis_contatos_5 ADD COLUMN data_validacao DATETIME NULL",
        "ALTER TABLE dis_contatos_5 ADD COLUMN motivo_inativacao TEXT NULL"
    ];
    
    foreach ($contact_validation_columns as $query) {
        try {
            $pdo_disparador->exec($query);
            echo "Coluna de validação de contatos adicionada com sucesso.\n";
        } catch (PDOException $e) {
            // Ignorar erro se coluna já existir
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "Aviso validação contatos: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "Todas as tabelas foram criadas/verificadas com sucesso!";

} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}