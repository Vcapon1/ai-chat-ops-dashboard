<?php

class EvolutionValidator {
    
    private $instance_url;
    private $api_key;
    private $instance_name;
    
    public function __construct($instance_url, $api_key, $instance_name) {
        $this->instance_url = rtrim($instance_url, '/');
        $this->api_key = $api_key;
        $this->instance_name = $instance_name;
    }
    
    /**
     * Valida um único número no WhatsApp
     */
    public function validateSingleNumber($phone) {
        $url = "{$this->instance_url}/chat/whatsappNumbers/{$this->instance_name}";
        
        $data = [
            'numbers' => [$phone . '@s.whatsapp.net']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $this->api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 201 || $http_code === 200) {
            $result = json_decode($response, true);
            
            if (isset($result[0]['exists'])) {
                return [
                    'valid' => $result[0]['exists'],
                    'phone' => $phone,
                    'jid' => $result[0]['jid'] ?? null,
                    'error' => null
                ];
            }
        }
        
        return [
            'valid' => false,
            'phone' => $phone,
            'jid' => null,
            'error' => "HTTP {$http_code}: {$response}"
        ];
    }
    
    /**
     * Valida múltiplos números em lote (com rate limiting)
     */
    public function validateBatch($phones, $batch_size = 10, $delay_seconds = 1) {
        $results = [];
        $batches = array_chunk($phones, $batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            $batch_results = [];
            
            foreach ($batch as $phone) {
                $result = $this->validateSingleNumber($phone);
                $batch_results[] = $result;
                
                // Delay entre cada número para não saturar
                if (count($batch) > 1) {
                    usleep(500000); // 0.5 segundo
                }
            }
            
            $results = array_merge($results, $batch_results);
            
            // Delay entre batches
            if ($batch_index < count($batches) - 1) {
                sleep($delay_seconds);
            }
            
            // Log do progresso
            $total_processed = ($batch_index + 1) * $batch_size;
            $total_phones = count($phones);
            error_log("Evolution validation progress: {$total_processed}/{$total_phones}");
        }
        
        return $results;
    }
    
    /**
     * Valida números em background (para listas grandes)
     */
    public function validateInBackground($phones, $list_id, $user_id) {
        // Criar arquivo temporário para processar em background
        $temp_file = sys_get_temp_dir() . "/evolution_validation_{$user_id}_{$list_id}.json";
        
        $data = [
            'phones' => $phones,
            'list_id' => $list_id,
            'user_id' => $user_id,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($temp_file, json_encode($data));
        
        // Executar validação em background (seria melhor usar queue/job)
        $command = "php " . __DIR__ . "/background_validator.php {$temp_file} > /dev/null 2>&1 &";
        exec($command);
        
        return $temp_file;
    }
    
    /**
     * Verifica status da validação em background
     */
    public function getValidationStatus($temp_file) {
        if (!file_exists($temp_file)) {
            return ['status' => 'not_found'];
        }
        
        $data = json_decode(file_get_contents($temp_file), true);
        return $data;
    }
    
    /**
     * Gera relatório de validação Evolution
     */
    public static function generateEvolutionReport($validations) {
        $report = [
            'total_checked' => count($validations),
            'valid_whatsapp' => 0,
            'invalid_whatsapp' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        foreach ($validations as $validation) {
            if ($validation['error']) {
                $report['errors']++;
            } elseif ($validation['valid']) {
                $report['valid_whatsapp']++;
            } else {
                $report['invalid_whatsapp']++;
            }
        }
        
        return $report;
    }
}