<?php
// Configuração de conexão com o banco disparador
$db_disparador_config = [
    'host' => '10.180.0.150',
    'port' => '3306',
    'user' => 'vitor',
    'pass' => 'EduGMa8j9Q6pNwVX51Hh',
    'db'   => 'disparador'  // Nome do banco disparador
];

try {
    $pdo_disparador = new PDO(
        "mysql:host={$db_disparador_config['host']};port={$db_disparador_config['port']};dbname={$db_disparador_config['db']}; charset=utf8mb4",
        $db_disparador_config['user'],
        $db_disparador_config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro de conexão com banco disparador: " . var_dump($db_disparador_config['db']) . $e->getMessage());
}