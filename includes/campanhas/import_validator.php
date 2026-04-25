<?php

class ImportValidator {
    
    /**
     * Valida os contatos importados
     */
    public static function validateContacts($contacts) {
        $valid_contacts = [];
        $errors = [];
        $validation_report = [
            'total' => count($contacts),
            'valid' => 0,
            'invalid' => 0,
            'empty_names' => 0,
            'invalid_phones' => 0
        ];
        
        foreach ($contacts as $index => $contact) {
            $line_errors = [];
            
            // Validar nome
            if (empty(trim($contact['nome'] ?? ''))) {
                $line_errors[] = 'Nome vazio';
                $validation_report['empty_names']++;
            }
            
            // Validar e formatar telefone
            $phone = trim($contact['telefone'] ?? '');
            if (empty($phone)) {
                $line_errors[] = 'Telefone vazio';
                $validation_report['invalid_phones']++;
            } else {
                // Formatar telefone: remover caracteres especiais
                $phone = preg_replace('/[^0-9]/', '', $phone);
                
                // Adicionar 55 se não tiver código do país
                if (strlen($phone) >= 10 && strlen($phone) <= 11 && !str_starts_with($phone, '55')) {
                    $phone = '55' . $phone;
                }
                
                // Validar formato brasileiro: 5511999999999 (13 dígitos) ou 11999999999 (11 dígitos)
                if (strlen($phone) < 10 || strlen($phone) > 13) {
                    $line_errors[] = 'Formato de telefone inválido';
                    $validation_report['invalid_phones']++;
                }
            }
            
            if (empty($line_errors)) {
                $valid_contacts[] = [
                    'nome' => trim($contact['nome']),
                    'telefone' => $phone,
                    'interesse' => trim($contact['interesse'] ?? '')
                ];
                $validation_report['valid']++;
            } else {
                $errors[] = [
                    'linha' => $index + 2, // +2 porque linha 1 é header
                    'erros' => $line_errors
                ];
                $validation_report['invalid']++;
            }
        }
        
        return [
            'validation_report' => $validation_report,
            'valid_contacts' => $valid_contacts,
            'errors' => $errors
        ];
    }
    
    /**
     * Gera relatório de importação
     */
    public static function generateImportReport($validation_data, $nome_lista, $total_inserted) {
        // Garantir que validation_data é um array válido
        if (!is_array($validation_data)) {
            $validation_data = [];
        }
        
        $report = $validation_data['validation_report'] ?? [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'empty_names' => 0,
            'invalid_phones' => 0
        ];
        
        return [
            'nome_lista' => $nome_lista,
            'timestamp' => date('Y-m-d H:i:s'),
            'total_linhas' => $report['total'],
            'contatos_validos' => $report['valid'],
            'contatos_invalidos' => $report['invalid'],
            'nomes_vazios' => $report['empty_names'],
            'telefones_invalidos' => $report['invalid_phones'],
            'total_inseridos' => $total_inserted,
            'erros' => isset($validation_data['errors']) && is_array($validation_data['errors']) 
                       ? array_slice($validation_data['errors'], 0, 10) 
                       : [] // Limitar a 10 erros
        ];
    }
    
    /**
     * Salva relatório no banco
     */
    public static function saveImportReport($pdo, $user_id, $report) {
        // Criar tabela se não existir
        $sql_create = "CREATE TABLE IF NOT EXISTS import_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            nome_lista VARCHAR(255) NOT NULL,
            timestamp DATETIME NOT NULL,
            total_linhas INT NOT NULL,
            contatos_validos INT NOT NULL,
            contatos_invalidos INT NOT NULL,
            nomes_vazios INT NOT NULL,
            telefones_invalidos INT NOT NULL,
            total_inseridos INT NOT NULL,
            erros TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql_create);
        
        // Inserir relatório
        $stmt = $pdo->prepare("
            INSERT INTO import_reports 
            (user_id, nome_lista, timestamp, total_linhas, contatos_validos, contatos_invalidos, 
             nomes_vazios, telefones_invalidos, total_inseridos, erros) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $report['nome_lista'],
            $report['timestamp'],
            $report['total_linhas'],
            $report['contatos_validos'],
            $report['contatos_invalidos'],
            $report['nomes_vazios'],
            $report['telefones_invalidos'],
            $report['total_inseridos'],
            json_encode($report['erros'])
        ]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * Obtém relatórios de importação
     */
    public static function getImportReports($pdo, $user_id, $limit = 10) {
        $stmt = $pdo->prepare("
            SELECT * FROM import_reports 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}