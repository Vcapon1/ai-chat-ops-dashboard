<?php

// Script para executar validação Evolution em background
if ($argc < 2) {
    die("Usage: php background_validator.php <temp_file_path>\n");
}

$temp_file = $argv[1];

if (!file_exists($temp_file)) {
    die("Temp file not found: {$temp_file}\n");
}

require_once __DIR__ . '/../db_disparador.php';
require_once __DIR__ . '/evolution_validator.php';

try {
    // Ler dados do arquivo temporário
    $data = json_decode(file_get_contents($temp_file), true);
    
    if (!$data || $data['status'] !== 'pending') {
        die("Invalid temp file data\n");
    }
    
    // Marcar como processando
    $data['status'] = 'processing';
    $data['started_at'] = date('Y-m-d H:i:s');
    file_put_contents($temp_file, json_encode($data));
    
    // Buscar credenciais da Evolution API
    $user_id = $data['user_id'];
    $stmt = $pdo_disparador->prepare("SELECT * FROM credenciais WHERE sessao = ?");
    $stmt->execute([$user_id]);
    $credentials = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$credentials || empty($credentials['evolution_url']) || empty($credentials['evolution_key'])) {
        throw new Exception("Credenciais da Evolution API não encontradas");
    }
    
    // Inicializar validador
    $validator = new EvolutionValidator(
        $credentials['evolution_url'],
        $credentials['evolution_key'],
        $credentials['instancia_nome'] ?? 'default'
    );
    
    // Validar números em lotes pequenos para não saturar
    $results = $validator->validateBatch($data['phones'], 5, 2);
    
    // Salvar resultados
    $data['status'] = 'completed';
    $data['completed_at'] = date('Y-m-d H:i:s');
    $data['results'] = $results;
    $data['report'] = EvolutionValidator::generateEvolutionReport($results);
    
    file_put_contents($temp_file, json_encode($data));
    
    // Log de sucesso
    error_log("Background Evolution validation completed for user {$user_id}, list {$data['list_id']}");
    
} catch (Exception $e) {
    // Marcar como erro
    $data['status'] = 'error';
    $data['error'] = $e->getMessage();
    $data['failed_at'] = date('Y-m-d H:i:s');
    
    file_put_contents($temp_file, json_encode($data));
    
    error_log("Background Evolution validation failed: " . $e->getMessage());
}