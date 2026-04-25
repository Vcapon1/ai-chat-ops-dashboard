<?php
$db_config = [
    'host' => '45.239.42.6',
    'port' => '33306',
    'user' => 'vitor',
    'pass' => 'EduGMa8j9Q6pNwVX51Hh',
    'db'   => 'mariai'
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['db']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Conexão bem sucedida!\n";
    echo "IP do servidor: " . $_SERVER['SERVER_ADDR'] . "\n";
    echo "IP do cliente: " . $_SERVER['REMOTE_ADDR'] . "\n";
    
    // Testa a consulta na tabela sessions
    $stmt = $pdo->query("SELECT COUNT(*) FROM sessions");
    $count = $stmt->fetchColumn();
    echo "Número de registros na tabela sessions: " . $count;
    
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>