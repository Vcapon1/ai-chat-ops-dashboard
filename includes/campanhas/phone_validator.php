<?php

class PhoneValidator {
    
    private static $patterns = [
        // Remover caracteres especiais
        'cleanup' => '/[^0-9+]/',
        
        // Padrões brasileiros
        'br_mobile' => '/^(?:\+?55)?(?:0)?([1-9][1-9])9?([0-9]{8})$/',
        'br_landline' => '/^(?:\+?55)?(?:0)?([1-9][1-9])([0-9]{7,8})$/',
        
        // Padrões internacionais básicos
        'international' => '/^\+[1-9][0-9]{7,14}$/'
    ];
    
    public static function validateAndFormat($phone) {
        $result = [
            'original' => $phone,
            'formatted' => null,
            'valid' => false,
            'type' => null,
            'corrections' => []
        ];
        
        // Limpar telefone
        $clean = preg_replace(self::$patterns['cleanup'], '', $phone);
        
        if (empty($clean)) {
            return $result;
        }
        
        // Tentar validar como brasileiro primeiro
        $brazilian_result = self::validateBrazilian($clean);
        if ($brazilian_result['valid']) {
            return array_merge($result, $brazilian_result);
        }
        
        // Tentar validar como internacional
        $international_result = self::validateInternational($clean);
        if ($international_result['valid']) {
            return array_merge($result, $international_result);
        }
        
        return $result;
    }
    
    private static function validateBrazilian($phone) {
        $result = [
            'valid' => false,
            'type' => 'brazilian',
            'corrections' => []
        ];
        
        // Celular brasileiro
        if (preg_match(self::$patterns['br_mobile'], $phone, $matches)) {
            $ddd = $matches[1];
            $number = $matches[2];
            
            // Adicionar 9 se não tiver (celulares antigos)
            if (strlen($number) == 8) {
                $number = '9' . $number;
                $result['corrections'][] = 'Adicionado 9 no início do número';
            }
            
            $result['formatted'] = "55{$ddd}{$number}";
            $result['valid'] = true;
            $result['type'] = 'mobile';
            
            return $result;
        }
        
        // Telefone fixo brasileiro
        if (preg_match(self::$patterns['br_landline'], $phone, $matches)) {
            $ddd = $matches[1];
            $number = $matches[2];
            
            $result['formatted'] = "55{$ddd}{$number}";
            $result['valid'] = true;
            $result['type'] = 'landline';
            
            return $result;
        }
        
        return $result;
    }
    
    private static function validateInternational($phone) {
        $result = [
            'valid' => false,
            'type' => 'international',
            'corrections' => []
        ];
        
        // Adicionar + se não tiver
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
            $result['corrections'][] = 'Adicionado + no início';
        }
        
        if (preg_match(self::$patterns['international'], $phone)) {
            $result['formatted'] = substr($phone, 1); // Remove + para armazenamento
            $result['valid'] = true;
            
            return $result;
        }
        
        return $result;
    }
    
    public static function generateReport($validations) {
        $report = [
            'total' => count($validations),
            'valid' => 0,
            'invalid' => 0,
            'mobile' => 0,
            'landline' => 0,
            'international' => 0,
            'corrections_applied' => 0,
            'corrections_details' => []
        ];
        
        foreach ($validations as $validation) {
            if ($validation['valid']) {
                $report['valid']++;
                
                switch ($validation['type']) {
                    case 'mobile':
                        $report['mobile']++;
                        break;
                    case 'landline':
                        $report['landline']++;
                        break;
                    case 'international':
                        $report['international']++;
                        break;
                }
                
                if (!empty($validation['corrections'])) {
                    $report['corrections_applied']++;
                    foreach ($validation['corrections'] as $correction) {
                        $report['corrections_details'][] = $correction;
                    }
                }
            } else {
                $report['invalid']++;
            }
        }
        
        return $report;
    }
}