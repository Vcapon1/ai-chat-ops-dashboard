<?php
require_once 'db.php';

try {
    // Adicionar coluna credito na tabela sessions
    $check_credito = $pdo->query("SHOW COLUMNS FROM sessions LIKE 'credito'");
    if ($check_credito->rowCount() === 0) {
        $pdo->exec("ALTER TABLE sessions ADD COLUMN credito INT DEFAULT 100 AFTER assist_name");
        echo "Coluna credito adicionada com sucesso!<br>";
    }
    
    // Criar tabela de histórico de créditos
    $check_credito_historico = $pdo->query("SHOW TABLES LIKE 'credito_historico'");
    if ($check_credito_historico->rowCount() === 0) {
        $pdo->exec("CREATE TABLE credito_historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            tipo ENUM('envio_mensagem', 'validacao_whatsapp', 'mensagem_ia', 'compra', 'bonus') NOT NULL,
            quantidade INT NOT NULL,
            saldo_anterior INT NOT NULL,
            saldo_atual INT NOT NULL,
            descricao TEXT NULL,
            campanha_id INT NULL,
            lista_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Tabela credito_historico criada com sucesso!<br>";
    }
    
    // Criar tabela para validação de WhatsApp em background
    $check_validacao_whatsapp = $pdo->query("SHOW TABLES LIKE 'validacao_whatsapp_queue'");
    if ($check_validacao_whatsapp->rowCount() === 0) {
        $pdo->exec("CREATE TABLE validacao_whatsapp_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            lista_id INT NOT NULL,
            telefone VARCHAR(20) NOT NULL,
            status ENUM('pendente', 'processando', 'validado', 'invalido', 'erro') DEFAULT 'pendente',
            resultado JSON NULL,
            tentativas INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
            INDEX idx_status_created (status, created_at),
            INDEX idx_session_lista (session_id, lista_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Tabela validacao_whatsapp_queue criada com sucesso!<br>";
    }
    
    echo "Migração de créditos concluída com sucesso!";
} catch (PDOException $e) {
    echo "Erro na migração de créditos: " . $e->getMessage();
}