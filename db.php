<?php
$db_config = [
    'host' => '10.180.0.150',
    'port' => '3306',
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
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}