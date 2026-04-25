<?php
/**
 * Operações de banco de dados para o módulo de campanhas
 */

/**
 * Buscar todas as listas do cliente logado com validação de uso recente
 */
function getListas($pdo_disparador, $id_session) {
    try {
        // Garantir que a tabela dis_listas existe
        criarTabelaDisListas($pdo_disparador);
        
        $sql = "
            SELECT l.*, 
                   CASE WHEN c.id_lista IS NOT NULL THEN 1 ELSE 0 END as usada_recentemente,
                   c.data_criacao as data_ultimo_uso,
                   0 as total_contatos,
                   0 as total_validados,
                   0 as total_ativos
            FROM dis_listas l
            LEFT JOIN dis_campanhas c ON l.id = c.id_lista AND c.id_session = ? AND c.data_criacao > DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE l.id_session = ? 
            ORDER BY l.data_criacao DESC
        ";
        
        $stmt = $pdo_disparador->prepare($sql);
        $stmt->execute([$id_session, $id_session]);
        $listas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Para cada lista, buscar estatísticas da sua tabela específica da sessão
        foreach ($listas as &$lista) {
            $contatos_table = "dis_lista_contatos_" . $id_session;
            
            // Verificar se a tabela existe
            $table_check = $pdo_disparador->prepare("SHOW TABLES LIKE ?");
            $table_check->execute([$contatos_table]);
            
            if ($table_check->fetch()) {
                // Buscar totais para esta lista específica
                $total_stmt = $pdo_disparador->prepare("SELECT COUNT(*) as total FROM `{$contatos_table}` WHERE id_lista = ?");
                $total_stmt->execute([$lista['id']]);
                $lista['total_contatos'] = $total_stmt->fetchColumn();
                
                // Buscar validados para esta lista específica
                $validados_stmt = $pdo_disparador->prepare("
                    SELECT COUNT(*) as total_validados, SUM(whatsapp_ativo) as total_ativos 
                    FROM `{$contatos_table}` 
                    WHERE id_lista = ? AND whatsapp_validado = 1
                ");
                $validados_stmt->execute([$lista['id']]);
                $validados = $validados_stmt->fetch(PDO::FETCH_ASSOC);
                $lista['total_validados'] = $validados['total_validados'] ?? 0;
                $lista['total_ativos'] = $validados['total_ativos'] ?? 0;
            }
        }
        
        return $listas;
    } catch (PDOException $e) {
        error_log("Erro ao buscar listas: " . $e->getMessage());
        return [];
    }
}

/**
 * Criar tabela dis_listas se não existir
 */
function criarTabelaDisListas($pdo_disparador) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS dis_listas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_session INT NOT NULL,
            nome VARCHAR(255) NOT NULL,
            descricao TEXT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session (id_session)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo_disparador->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela dis_listas: " . $e->getMessage());
        return false;
    }
}

/**
 * Criar nova lista
 */
function criarLista($pdo_disparador, $id_session, $nome, $tipo = 1) {
    try {
        // Primeiro garantir que a tabela dis_listas existe
        if (!criarTabelaDisListas($pdo_disparador)) {
            throw new Exception('Erro ao criar tabela dis_listas');
        }
        
        $stmt = $pdo_disparador->prepare("INSERT INTO dis_listas (id_session, nome, data_criacao) VALUES (?, ?, NOW())");
        $stmt->execute([$id_session, $nome]);
        $id_lista = $pdo_disparador->lastInsertId();
        
        // Criar tabela específica para os contatos desta sessão se não existir
        if (!criarTabelaContatos($pdo_disparador, $id_session)) {
            // Se falhar ao criar tabela de contatos, remover a lista
            $pdo_disparador->prepare("DELETE FROM dis_listas WHERE id = ?")->execute([$id_lista]);
            throw new Exception('Erro ao criar tabela de contatos');
        }
        
        return $id_lista;
    } catch (PDOException $e) {
        error_log("Erro ao criar lista: " . $e->getMessage());
        return false;
    }
}

/**
 * Criar tabela de contatos para uma sessão específica
 */
function criarTabelaContatos($pdo_disparador, $id_session) {
    try {
        $table_name = "dis_lista_contatos_" . $id_session;
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_lista INT NOT NULL,
            nome VARCHAR(255),
            telefone VARCHAR(20) NOT NULL,
            interesse VARCHAR(255),
            intencao TEXT,
            whatsapp_validado TINYINT(1) DEFAULT 0,
            whatsapp_ativo TINYINT(1) DEFAULT 1,
            validar_necessario TINYINT(1) DEFAULT 0,
            validar_nome_ia TINYINT(1) DEFAULT 0,
            nome_validado TINYINT(1) NULL,
            data_validacao TIMESTAMP NULL,
            motivo_inativacao TEXT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_telefone_lista (telefone, id_lista),
            INDEX idx_telefone (telefone),
            INDEX idx_lista (id_lista),
            INDEX idx_validar_necessario (validar_necessario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo_disparador->exec($sql);
        
        // Verificar e adicionar colunas que podem estar faltando em tabelas existentes
        atualizarEstruturaContatos($pdo_disparador, $id_session);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de contatos: " . $e->getMessage());
        return false;
    }
}

/**
 * Inserir contatos na lista
 */
function inserirContatos($pdo_disparador, $id_session, $id_lista, $contatos) {
    try {
        // Garantir que a tabela de contatos existe para esta sessão
        if (!criarTabelaContatos($pdo_disparador, $id_session)) {
            throw new Exception('Erro ao criar tabela de contatos');
        }
        
        $table_name = "dis_lista_contatos_" . $id_session;
        
        // Verificar quais colunas existem na tabela
        $stmt = $pdo_disparador->prepare("DESCRIBE `{$table_name}`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Preparar SQL com base nas colunas disponíveis
        $insert_columns = ['id_lista', 'nome', 'telefone'];
        $placeholders = ['?', '?', '?'];
        
        if (in_array('interesse', $columns)) {
            $insert_columns[] = 'interesse';
            $placeholders[] = '?';
        }
        if (in_array('intencao', $columns)) {
            $insert_columns[] = 'intencao';
            $placeholders[] = '?';
        }
        if (in_array('validar_necessario', $columns)) {
            $insert_columns[] = 'validar_necessario';
            $placeholders[] = '?';
        }
        if (in_array('validar_nome_ia', $columns)) {
            $insert_columns[] = 'validar_nome_ia';
            $placeholders[] = '?';
        }
        
        $sql = "INSERT IGNORE INTO `{$table_name}` (" . implode(', ', $insert_columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo_disparador->prepare($sql);
        
        $inseridos = 0;
        foreach ($contatos as $contato) {
            $values = [$id_lista, $contato['nome'], $contato['telefone']];
            
            if (in_array('interesse', $columns)) {
                $values[] = $contato['interesse'] ?? '';
            }
            if (in_array('intencao', $columns)) {
                $values[] = $contato['intencao'] ?? '';
            }
            if (in_array('validar_necessario', $columns)) {
                $values[] = $contato['validar_necessario'] ?? 0;
            }
            if (in_array('validar_nome_ia', $columns)) {
                $values[] = $contato['validar_nome_ia'] ?? 0;
            }
            
            if ($stmt->execute($values)) {
                if ($stmt->rowCount() > 0) {
                    $inseridos++;
                }
            }
        }
        
        // Verificar e atualizar flags de validação na dis_listas se necessário
        $tem_validacao_whatsapp = false;
        $tem_validacao_nome_ia = false;
        
        foreach ($contatos as $contato) {
            if (isset($contato['validar_necessario']) && $contato['validar_necessario'] == 1) {
                $tem_validacao_whatsapp = true;
            }
            if (isset($contato['validar_nome_ia']) && $contato['validar_nome_ia'] == 1) {
                $tem_validacao_nome_ia = true;
            }
        }
        
        // Verificar se a coluna validar_nome_ia existe na tabela dis_listas
        $stmt_check = $pdo_disparador->prepare("SHOW COLUMNS FROM dis_listas LIKE 'validar_nome_ia'");
        $stmt_check->execute();
        $coluna_existe = $stmt_check->fetch();
        
        if ($coluna_existe) {
            $stmt = $pdo_disparador->prepare("
                UPDATE dis_listas 
                SET validar_necessario = ?, 
                    validar_nome_ia = ?
                WHERE id = ? AND id_session = ?
            ");
            $stmt->execute([$tem_validacao_whatsapp ? 1 : 0, $tem_validacao_nome_ia ? 1 : 0, $id_lista, $id_session]);
        } else {
            $stmt = $pdo_disparador->prepare("
                UPDATE dis_listas 
                SET validar_necessario = ?
                WHERE id = ? AND id_session = ?
            ");
            $stmt->execute([$tem_validacao_whatsapp ? 1 : 0, $id_lista, $id_session]);
        }
        
        error_log("DEBUG: Lista $id_lista atualizada - WhatsApp: " . ($tem_validacao_whatsapp ? 'SIM' : 'NÃO') . ", Nome IA: " . ($tem_validacao_nome_ia ? 'SIM' : 'NÃO'));

        return $inseridos > 0;
    } catch (PDOException $e) {
        error_log("Erro ao inserir contatos: " . $e->getMessage());
        return false;
    }
}

/**
 * Buscar todas as campanhas do cliente
 */
function getCampanhas($pdo_disparador, $id_session) {
    try {
        $stmt = $pdo_disparador->prepare("
            SELECT c.*, l.nome as nome_lista 
            FROM dis_campanhas c 
            LEFT JOIN dis_listas l ON c.id_lista = l.id 
            WHERE c.id_session = ? 
            ORDER BY c.data_criacao DESC
        ");
        $stmt->execute([$id_session]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar campanhas: " . $e->getMessage());
        return [];
    }
}

/**
 * Validar se a lista foi usada nos últimos 30 dias
 */
function validarListaDisponivel($pdo_disparador, $id_session, $id_lista) {
    try {
        $stmt = $pdo_disparador->prepare("
            SELECT COUNT(*) as uso_recente 
            FROM dis_campanhas 
            WHERE id_session = ? AND id_lista = ? 
            AND data_criacao > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$id_session, $id_lista]);
        $uso_recente = $stmt->fetchColumn();
        
        return $uso_recente == 0;
    } catch (PDOException $e) {
        error_log("Erro ao validar lista: " . $e->getMessage());
        return false;
    }
}

/**
 * Criar tabela de envios para a campanha
 */
function criarTabelaEnviosCampanha($pdo_disparador, $id_session, $id_campanha, $id_lista, $mensagem) {
    try {
        $contatos_table = "dis_lista_contatos_" . $id_session;
        
        // Verificar se a tabela de contatos existe primeiro
        $table_check = $pdo_disparador->prepare("SHOW TABLES LIKE '{$contatos_table}'");
        $table_check->execute();
        
        if (!$table_check->fetch()) {
            error_log("DEBUG: Tabela {$contatos_table} não existe, tentando criar...");
            if (!criarTabelaContatos($pdo_disparador, $id_lista)) {
                throw new Exception('Erro ao criar tabela de contatos');
            }
        }
        
        // Verificar e criar tabela de blacklist se não existir
        $blacklist_check = $pdo_disparador->prepare("SHOW TABLES LIKE 'dis_blacklist_optout'");
        $blacklist_check->execute();
        
        if (!$blacklist_check->fetch()) {
            error_log("DEBUG: Tabela dis_blacklist_optout não existe, criando...");
            $sql_blacklist = "CREATE TABLE dis_blacklist_optout (
                id INT AUTO_INCREMENT PRIMARY KEY,
                telefone VARCHAR(20) NOT NULL,
                data_cancelamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                id_session INT NOT NULL,
                id_campanha INT,
                UNIQUE KEY unique_telefone_session (telefone, id_session),
                INDEX idx_session (id_session)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo_disparador->exec($sql_blacklist);
            error_log("DEBUG: Tabela dis_blacklist_optout criada com sucesso");
        }
        
        // Verificar se existem contatos ativos na lista antes de criar tabela
        $check_stmt = $pdo_disparador->prepare("
            SELECT COUNT(*) as total_contatos
            FROM `{$contatos_table}` c
            LEFT JOIN dis_blacklist_optout b ON c.telefone = b.telefone AND b.id_session = ?
            WHERE b.telefone IS NULL AND c.whatsapp_ativo = 1
        ");
        $check_stmt->execute([$id_session]);
        $contatos_disponiveis = $check_stmt->fetchColumn();
        
        error_log("DEBUG: Lista {$id_lista} tem {$contatos_disponiveis} contatos disponíveis (não blacklist)");
        
        if ($contatos_disponiveis == 0) {
            throw new Exception('A lista não possui contatos válidos para envio (todos podem estar em blacklist ou inativos)');
        }
        
        $table_name = "dis_envios_campanha_" . $id_campanha;
        
        // Criar tabela de envios
        $sql = "CREATE TABLE `{$table_name}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_contato INT NOT NULL,
            id_lista INT NOT NULL,
            telefone VARCHAR(20) NOT NULL,
            nome VARCHAR(255),
            intencao TEXT,
            mensagem TEXT NOT NULL,
            enviado TINYINT(1) DEFAULT 0,
            lido TINYINT(1) DEFAULT 0,
            respondido TINYINT(1) DEFAULT 0,
            cancelado TINYINT(1) DEFAULT 0,
            data_envio TIMESTAMP NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_telefone (telefone),
            INDEX idx_enviado (enviado),
            INDEX idx_contato (id_contato)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo_disparador->exec($sql);
        error_log("DEBUG: Tabela {$table_name} criada com sucesso");
        
        // Verificar quais colunas existem na tabela de contatos
        $stmt = $pdo_disparador->prepare("DESCRIBE `{$contatos_table}`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Preparar SQL com base nas colunas disponíveis
        $select_columns = ['c.id', '? as id_lista', 'c.telefone', 'c.nome'];
        $insert_columns = ['id_contato', 'id_lista', 'telefone', 'nome'];
        
        if (in_array('intencao', $columns)) {
            $select_columns[] = 'c.intencao';
            $insert_columns[] = 'intencao';
        } else {
            $select_columns[] = "'' as intencao";
            $insert_columns[] = 'intencao';
        }
        
        $select_columns[] = '? as mensagem';
        $insert_columns[] = 'mensagem';
        
        // Copiar contatos da lista para a tabela de envios, excluindo blacklist e contatos inativos
        $insert_sql = "
            INSERT INTO `{$table_name}` (" . implode(', ', $insert_columns) . ")
            SELECT " . implode(', ', $select_columns) . "
            FROM `{$contatos_table}` c
            LEFT JOIN dis_blacklist_optout b ON c.telefone = b.telefone AND b.id_session = ?
            WHERE b.telefone IS NULL AND c.whatsapp_ativo = 1 AND c.id_lista = ?
        ";
        
        $stmt = $pdo_disparador->prepare($insert_sql);
        $stmt->execute([$id_lista, $mensagem, $id_session, $id_lista]);
        
        $qtd_programados = $stmt->rowCount();
        error_log("DEBUG: {$qtd_programados} contatos copiados para tabela de envios {$table_name}");
        
        return $qtd_programados;
        
    } catch (PDOException $e) {
        error_log("Erro ao criar tabela de envios: " . $e->getMessage());
        return false;
    }
}

/**
 * Criar nova campanha
 */
function criarCampanha($pdo_disparador, $id_session, $nome_campanha, $id_lista, $mensagem, $data_agendada) {
    try {
        // Validar se a lista pode ser usada
        if (!validarListaDisponivel($pdo_disparador, $id_session, $id_lista)) {
            throw new Exception('Esta lista foi usada nos últimos 30 dias e não pode ser reutilizada ainda.');
        }
        
        // Criar a campanha primeiro (sem transação pois vamos criar tabelas)
        $stmt = $pdo_disparador->prepare("
            INSERT INTO dis_campanhas (id_session, nome_campanha, id_lista, mensagem, data_agendada, data_criacao, foi_disparada, qtd_enviados, qtd_programados, cancelamento_ativo) 
            VALUES (?, ?, ?, ?, ?, NOW(), 0, 0, 0, 1)
        ");
        
        $stmt->execute([$id_session, $nome_campanha, $id_lista, $mensagem, $data_agendada]);
        $id_campanha = $pdo_disparador->lastInsertId();
        
        if (!$id_campanha) {
            throw new Exception('Erro ao criar campanha');
        }
        
        // Criar tabela de envios e copiar contatos (isso inclui CREATE TABLE que comita automaticamente)
        $qtd_programados = criarTabelaEnviosCampanha($pdo_disparador, $id_session, $id_campanha, $id_lista, $mensagem);
        
        if ($qtd_programados === false) {
            // Se falhou, excluir a campanha criada
            $stmt = $pdo_disparador->prepare("DELETE FROM dis_campanhas WHERE id = ?");
            $stmt->execute([$id_campanha]);
            throw new Exception('Erro ao preparar envios da campanha');
        }
        
        // Atualizar campanha com nome da tabela e quantidade programada
        $table_name = "dis_envios_campanha_" . $id_campanha;
        $update_sql = "UPDATE dis_campanhas SET nome_tabela_envio = ?, qtd_programados = ? WHERE id = ?";
        $stmt = $pdo_disparador->prepare($update_sql);
        if (!$stmt->execute([$table_name, $qtd_programados, $id_campanha])) {
            throw new Exception('Erro ao atualizar dados da campanha');
        }
        
        return $id_campanha;
        
    } catch (Exception $e) {
        error_log("Erro ao criar campanha: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Buscar estatísticas do cliente
 */
function getEstatisticas($pdo_disparador, $id_session) {
    try {
        // Total de campanhas
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) as total_campanhas FROM dis_campanhas WHERE id_session = ?");
        $stmt->execute([$id_session]);
        $total_campanhas = $stmt->fetchColumn();

        // Campanhas disparadas
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) as campanhas_disparadas FROM dis_campanhas WHERE id_session = ? AND foi_disparada = 1");
        $stmt->execute([$id_session]);
        $campanhas_disparadas = $stmt->fetchColumn();

        // Total programado
        $stmt = $pdo_disparador->prepare("SELECT SUM(qtd_programados) as total_programado FROM dis_campanhas WHERE id_session = ?");
        $stmt->execute([$id_session]);
        $total_programado = $stmt->fetchColumn() ?: 0;

        // Total enviado
        $stmt = $pdo_disparador->prepare("SELECT SUM(qtd_enviados) as total_enviado FROM dis_campanhas WHERE id_session = ?");
        $stmt->execute([$id_session]);
        $total_enviado = $stmt->fetchColumn() ?: 0;

        // Total de listas
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) as total_listas FROM dis_listas WHERE id_session = ?");
        $stmt->execute([$id_session]);
        $total_listas = $stmt->fetchColumn();

        // Total de contatos - somar de todas as tabelas de listas
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id_session = ?");
        $stmt->execute([$id_session]);
        $listas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $total_contatos = 0;
        $contatos_table = "dis_lista_contatos_" . $id_session;
        $table_check = $pdo_disparador->prepare("SHOW TABLES LIKE ?");
        $table_check->execute([$contatos_table]);
        
        if ($table_check->fetch()) {
            foreach ($listas as $id_lista) {
                $count_stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$contatos_table}` WHERE id_lista = ?");
                $count_stmt->execute([$id_lista]);
                $total_contatos += $count_stmt->fetchColumn();
            }
        }

        return [
            'total_campanhas' => $total_campanhas,
            'campanhas_disparadas' => $campanhas_disparadas,
            'total_programado' => $total_programado,
            'total_enviado' => $total_enviado,
            'total_listas' => $total_listas,
            'total_contatos' => $total_contatos
        ];
    } catch (PDOException $e) {
        error_log("Erro ao buscar estatísticas: " . $e->getMessage());
        return [
            'total_campanhas' => 0,
            'campanhas_disparadas' => 0,
            'total_programado' => 0,
            'total_enviado' => 0,
            'total_listas' => 0,
            'total_contatos' => 0
        ];
    }
}

/**
 * Buscar relatório detalhado de uma campanha
 */
function getRelatorioCampanha($pdo_disparador, $id_session, $id_campanha) {
    try {
        // Buscar dados da campanha
        $stmt = $pdo_disparador->prepare("
            SELECT c.*, l.nome as nome_lista 
            FROM dis_campanhas c 
            LEFT JOIN dis_listas l ON c.id_lista = l.id 
            WHERE c.id = ? AND c.id_session = ?
        ");
        $stmt->execute([$id_campanha, $id_session]);
        $campanha = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campanha) {
            return false;
        }
        
        // Se tem tabela de envios, buscar estatísticas
        if ($campanha['nome_tabela_envio']) {
            $table_name = $campanha['nome_tabela_envio'];
            
            // Total de contatos
            $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}`");
            $stmt->execute();
            $total_contatos = $stmt->fetchColumn();
            
            // Total enviados
            $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE enviado = 1");
            $stmt->execute();
            $total_enviados = $stmt->fetchColumn();
            
            // Total lidos
            $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE lido = 1");
            $stmt->execute();
            $total_lidos = $stmt->fetchColumn();
            
            // Total respondidos
            $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE respondido = 1");
            $stmt->execute();
            $total_respondidos = $stmt->fetchColumn();
            
            // Total cancelados
            $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE cancelado = 1");
            $stmt->execute();
            $total_cancelados = $stmt->fetchColumn();
            
            $campanha['estatisticas'] = [
                'total_contatos' => $total_contatos,
                'total_enviados' => $total_enviados,
                'total_lidos' => $total_lidos,
                'total_respondidos' => $total_respondidos,
                'total_cancelados' => $total_cancelados
            ];
        }
        
        return $campanha;
        
    } catch (PDOException $e) {
        error_log("Erro ao buscar relatório de campanha: " . $e->getMessage());
        return false;
    }
}

/**
 * Adicionar contato ao blacklist
 */
function adicionarBlacklist($pdo_disparador, $id_session, $telefone, $id_campanha = null) {
    try {
        $stmt = $pdo_disparador->prepare("
            INSERT IGNORE INTO dis_blacklist_optout (telefone, id_session, id_campanha) 
            VALUES (?, ?, ?)
        ");
        
        return $stmt->execute([$telefone, $id_session, $id_campanha]);
    } catch (PDOException $e) {
        error_log("Erro ao adicionar blacklist: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar cancelamento em campanha específica
 */
function marcarCancelamentoCampanha($pdo_disparador, $id_session, $id_campanha, $telefone) {
    try {
        // Buscar nome da tabela de envios
        $stmt = $pdo_disparador->prepare("SELECT nome_tabela_envio FROM dis_campanhas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_campanha, $id_session]);
        $table_name = $stmt->fetchColumn();
        
        if (!$table_name) {
            return false;
        }
        
        // Marcar como cancelado na tabela de envios
        $stmt = $pdo_disparador->prepare("UPDATE `{$table_name}` SET cancelado = 1 WHERE telefone = ?");
        $result = $stmt->execute([$telefone]);
        
        // Adicionar ao blacklist global
        if ($result) {
            adicionarBlacklist($pdo_disparador, $id_session, $telefone, $id_campanha);
        }
        
        return $result;
        
    } catch (PDOException $e) {
        error_log("Erro ao marcar cancelamento: " . $e->getMessage());
        return false;
    }
}

/**
 * Marcar lista para validação e calcular custo
 */
function marcarListaParaValidacao($pdo_disparador, $id_session, $id_lista) {
    try {
        // Contar total de contatos na lista
        $table_name = "dis_lista_contatos_" . $id_session;
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE id_lista = ?");
        $stmt->execute([$id_lista]);
        $total_contatos = $stmt->fetchColumn();
        
        // Marcar lista para validação com custo calculado
        $stmt = $pdo_disparador->prepare("
            UPDATE dis_listas 
            SET validar_necessario = 1, 
                custo_validacao = ?, 
                validacao_iniciada = 0,
                validacao_concluida = 0
            WHERE id = ? AND id_session = ?
        ");
        
        return $stmt->execute([$total_contatos, $id_lista, $id_session]);
    } catch (PDOException $e) {
        error_log("Erro ao marcar lista para validação: " . $e->getMessage());
        return false;
    }
}

/**
 * Iniciar processo de validação
 */
function iniciarValidacaoLista($pdo_disparador, $id_session, $id_lista) {
    try {
        $stmt = $pdo_disparador->prepare("
            UPDATE dis_listas 
            SET validacao_iniciada = 1,
                data_validacao_inicio = NOW()
            WHERE id = ? AND id_session = ? AND validar_necessario = 1
        ");
        
        return $stmt->execute([$id_lista, $id_session]);
    } catch (PDOException $e) {
        error_log("Erro ao iniciar validação: " . $e->getMessage());
        return false;
    }
}

/**
 * Buscar números para validação
 */
function buscarNumerosParaValidacao($pdo_disparador, $id_session, $id_lista) {
    try {
        $table_name = "dis_lista_contatos_" . $id_lista;
        $stmt = $pdo_disparador->prepare("
            SELECT id, telefone, nome 
            FROM `{$table_name}` 
            WHERE whatsapp_validado = 0
            ORDER BY id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar números para validação: " . $e->getMessage());
        return [];
    }
}

/**
 * Atualizar resultado da validação de um contato
 */
function atualizarResultadoValidacao($pdo_disparador, $id_session, $id_lista, $id_contato, $whatsapp_ativo) {
    try {
        $table_name = "dis_lista_contatos_" . $id_session;
        $stmt = $pdo_disparador->prepare("
            UPDATE `{$table_name}` 
            SET whatsapp_validado = 1,
                whatsapp_ativo = ?,
                data_validacao = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$whatsapp_ativo, $id_contato]);
    } catch (PDOException $e) {
        error_log("Erro ao atualizar resultado validação: " . $e->getMessage());
        return false;
    }
}

/**
 * Finalizar validação da lista
 */
function finalizarValidacaoLista($pdo_disparador, $id_session, $id_lista) {
    try {
        $stmt = $pdo_disparador->prepare("
            UPDATE dis_listas 
            SET validacao_concluida = 1,
                data_validacao_fim = NOW()
            WHERE id = ? AND id_session = ?
        ");
        
        return $stmt->execute([$id_lista, $id_session]);
    } catch (PDOException $e) {
        error_log("Erro ao finalizar validação: " . $e->getMessage());
        return false;
    }
}

/**
 * Buscar estatísticas de validação da lista
 */
function getEstatisticasValidacao($pdo_disparador, $id_session, $id_lista) {
    try {
        $table_name = "dis_lista_contatos_" . $id_lista;
        
        // Total de contatos
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}`");
        $stmt->execute();
        $total_contatos = $stmt->fetchColumn();
        
        // Validados
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE whatsapp_validado = 1");
        $stmt->execute();
        $total_validados = $stmt->fetchColumn();
        
        // Ativos no WhatsApp
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE whatsapp_validado = 1 AND whatsapp_ativo = 1");
        $stmt->execute();
        $whatsapp_ativos = $stmt->fetchColumn();
        
        // Inativos no WhatsApp
        $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE whatsapp_validado = 1 AND whatsapp_ativo = 0");
        $stmt->execute();
        $whatsapp_inativos = $stmt->fetchColumn();
        
        return [
            'total_contatos' => $total_contatos,
            'total_validados' => $total_validados,
            'whatsapp_ativos' => $whatsapp_ativos,
            'whatsapp_inativos' => $whatsapp_inativos,
            'pendentes_validacao' => $total_contatos - $total_validados,
            'percentual_validado' => $total_contatos > 0 ? round(($total_validados / $total_contatos) * 100, 2) : 0
        ];
    } catch (PDOException $e) {
        error_log("Erro ao buscar estatísticas de validação: " . $e->getMessage());
        return false;
    }
}

/**
 * Excluir lista
 */
function excluirLista($pdo_disparador, $id_session, $id_lista) {
    try {
        $pdo_disparador->beginTransaction();
        
        // Excluir contatos da lista na tabela da sessão
        $contatos_table = "dis_lista_contatos_" . $id_session;
        $pdo_disparador->prepare("DELETE FROM `{$contatos_table}` WHERE id_lista = ?")->execute([$id_lista]);
        
        // Excluir a lista
        $stmt = $pdo_disparador->prepare("DELETE FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        
        $pdo_disparador->commit();
        return true;
    } catch (PDOException $e) {
        $pdo_disparador->rollBack();
        error_log("Erro ao excluir lista: " . $e->getMessage());
        return false;
    }
}

/**
 * Criar nova campanha com configurações avançadas de agendamento
 */
function criarCampanhaAvancada($pdo_disparador, $id_session, $nome_campanha, $id_lista, $mensagem, 
                               $data_inicio, $hora_inicio, $hora_fim, $dias_semana, $usar_ia = 0, $contexto_empresa = '', $tom_voz = '', $templates_selecionados = null) {
    try {
        // Verificar e atualizar estrutura da tabela campanhas se necessário
        atualizarEstruturaCampanhas($pdo_disparador);
        
        // Validar se a lista pode ser usada
        if (!validarListaDisponivel($pdo_disparador, $id_session, $id_lista)) {
            throw new Exception('Esta lista foi usada nos últimos 30 dias e não pode ser reutilizada ainda.');
        }
        
        // Converter array de dias para JSON
        $dias_semana_json = json_encode($dias_semana);
        
        // Converter templates para JSON se existir
        $templates_json = $templates_selecionados ? json_encode($templates_selecionados) : null;
        
        // Criar a campanha primeiro (sem transação pois vamos criar tabelas)
        $stmt = $pdo_disparador->prepare("
            INSERT INTO dis_campanhas (
                id_session, nome_campanha, id_lista, mensagem, data_agendada, 
                data_inicio, hora_inicio, hora_fim, dias_semana, usar_ia, contexto_empresa, tom_voz,
                templates_selecionados, data_criacao, foi_disparada, qtd_enviados, qtd_programados, cancelamento_ativo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, 0, 0, 1)
        ");
        
        // Para manter compatibilidade, usar data_inicio + hora_inicio como data_agendada
        $data_agendada = $data_inicio . ' ' . $hora_inicio . ':00';
        
        $stmt->execute([
            $id_session, $nome_campanha, $id_lista, $mensagem, $data_agendada,
            $data_inicio, $hora_inicio, $hora_fim, $dias_semana_json, $usar_ia, $contexto_empresa, $tom_voz, $templates_json
        ]);
        
        $id_campanha = $pdo_disparador->lastInsertId();
        
        if (!$id_campanha) {
            throw new Exception('Erro ao criar campanha');
        }
        
        // Criar tabela de envios e copiar contatos
        $qtd_programados = criarTabelaEnviosCampanha($pdo_disparador, $id_session, $id_campanha, $id_lista, $mensagem);
        
        if ($qtd_programados === false) {
            // Se falhou, excluir a campanha criada
            $stmt = $pdo_disparador->prepare("DELETE FROM dis_campanhas WHERE id = ?");
            $stmt->execute([$id_campanha]);
            throw new Exception('Erro ao preparar envios da campanha');
        }
        
        // Atualizar campanha com nome da tabela e quantidade programada
        $table_name = "dis_envios_campanha_" . $id_campanha;
        $update_sql = "UPDATE dis_campanhas SET nome_tabela_envio = ?, qtd_programados = ? WHERE id = ?";
        $stmt = $pdo_disparador->prepare($update_sql);
        if (!$stmt->execute([$table_name, $qtd_programados, $id_campanha])) {
            throw new Exception('Erro ao atualizar dados da campanha');
        }
        
        return $id_campanha;
        
    } catch (Exception $e) {
        error_log("Erro ao criar campanha avançada: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Atualizar estrutura da tabela campanhas para incluir novos campos de agendamento
 */
function atualizarEstruturaCampanhas($pdo_disparador) {
    try {
        // Verificar se as colunas existem
        $stmt = $pdo_disparador->prepare("DESCRIBE dis_campanhas");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $alterations = [];
        
        // Verificar cada coluna necessária
        if (!in_array('data_inicio', $columns)) {
            $alterations[] = "ADD COLUMN data_inicio DATE NULL";
        }
        if (!in_array('hora_inicio', $columns)) {
            $alterations[] = "ADD COLUMN hora_inicio TIME NULL";
        }
        if (!in_array('hora_fim', $columns)) {
            $alterations[] = "ADD COLUMN hora_fim TIME NULL";
        }
        if (!in_array('dias_semana', $columns)) {
            $alterations[] = "ADD COLUMN dias_semana JSON NULL";
        }
        if (!in_array('usar_ia', $columns)) {
            $alterations[] = "ADD COLUMN usar_ia TINYINT(1) DEFAULT 0";
        }
        if (!in_array('contexto_empresa', $columns)) {
            $alterations[] = "ADD COLUMN contexto_empresa TEXT NULL";
        }
        if (!in_array('tom_voz', $columns)) {
            $alterations[] = "ADD COLUMN tom_voz VARCHAR(50) NULL";
        }
        
        // Executar alterações se necessário
        if (!empty($alterations)) {
            $sql = "ALTER TABLE dis_campanhas " . implode(', ', $alterations);
            $pdo_disparador->exec($sql);
            error_log("DEBUG: Estrutura da tabela dis_campanhas atualizada com sucesso");
        }
        
    } catch (PDOException $e) {
        error_log("Erro ao atualizar estrutura da tabela campanhas: " . $e->getMessage());
        // Não lançar exceção aqui pois pode ser que a tabela já tenha as colunas
    }
}

/**
 * Buscar contatos de uma lista específica
 */
function getContatosLista(PDO $pdo_disparador, string $id_session, int $id_lista): array {
    try {
        $tabela_contatos = "dis_lista_contatos_{$id_lista}";
        
        // Verificar se a tabela existe
        $stmt = $pdo_disparador->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela_contatos]);
        
        if (!$stmt->fetch()) {
            return [];
        }
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Lista não encontrada ou não pertence ao usuário');
        }
        
        // Buscar contatos da lista
        $sql = "SELECT nome, telefone, interesse, data_criacao, whatsapp_validado, whatsapp_ativo, motivo_inativacao 
                FROM {$tabela_contatos} 
                ORDER BY data_criacao DESC";
        
        $stmt = $pdo_disparador->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Erro ao buscar contatos da lista: " . $e->getMessage());
        throw new Exception('Erro ao buscar contatos da lista');
    }
}

/**
 * Validar números de WhatsApp de uma lista
 */
function validarWhatsAppContatos(PDO $pdo_disparador, string $id_session, int $id_lista): array {
    try {
        $tabela_contatos = "dis_lista_contatos_{$id_lista}";
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        
        if (!$stmt->fetch()) {
            throw new Exception('Lista não encontrada ou não pertence ao usuário');
        }
        
        // Buscar contatos não validados da lista (limitando a 10 por vez)
        $sql = "SELECT telefone FROM {$tabela_contatos} 
                WHERE (whatsapp_validado IS NULL OR whatsapp_validado = 0) 
                LIMIT 10";
        
        $stmt = $pdo_disparador->prepare($sql);
        $stmt->execute();
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contatos)) {
            return [
                'message' => 'Todos os contatos já foram validados ou não há contatos para validar',
                'resultados' => []
            ];
        }
        
        // Preparar números para validação
        $numeros = array_column($contatos, 'telefone');
        
        // Chamar API de validação
        require_once __DIR__ . '/../db.php';
        $resultados_api = chamarApiValidacaoWhatsApp($numeros, $id_session, $pdo);
        
        // Atualizar status dos contatos no banco
        $creditos_consumidos = 0;
        $resultados = [];
        
        foreach ($resultados_api as $resultado) {
            $telefone = $resultado['number'];
            $valido = $resultado['exists'];
            
            // Atualizar status no banco
            $sql_update = "UPDATE {$tabela_contatos} 
                          SET whatsapp_validado = 1, 
                              whatsapp_ativo = ?, 
                              data_validacao = NOW() 
                          WHERE telefone = ?";
            
            $stmt_update = $pdo_disparador->prepare($sql_update);
            $stmt_update->execute([$valido ? 1 : 0, $telefone]);
            
            // Verificar se a atualização foi bem sucedida
            if ($stmt_update->rowCount() === 0) {
                error_log("Falha ao atualizar contato: $telefone na lista $id_lista");
            }
            
            $creditos_consumidos++;
            $resultados[] = [
                'telefone' => $telefone,
                'valido' => $valido
            ];
        }
        
        // Descontar créditos do usuário
        require_once __DIR__ . '/../credits_manager.php';
        debitarCreditos($pdo, $id_session, 'validacao_whatsapp', $creditos_consumidos, "Validação WhatsApp - $creditos_consumidos números");
        
        return [
            'message' => "Validação concluída! {$creditos_consumidos} créditos consumidos. " . 
                        count(array_filter($resultados, fn($r) => $r['valido'])) . " números válidos, " .
                        count(array_filter($resultados, fn($r) => !$r['valido'])) . " números inválidos.",
            'resultados' => $resultados
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao validar WhatsApp: " . $e->getMessage());
        throw new Exception('Erro ao validar números do WhatsApp: ' . $e->getMessage());
    }
}

/**
 * Chamar API de validação do WhatsApp
 */
function chamarApiValidacaoWhatsApp(array $numeros, string $id_session, PDO $pdo): array {
    try {
        // Buscar session name e bot_token do usuário
        $stmt = $pdo->prepare("SELECT session_name, bot_token FROM sessions WHERE id = ?");
        $stmt->execute([$id_session]);
        $session_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session_data || empty($session_data['session_name']) || empty($session_data['bot_token'])) {
            throw new Exception('Session name ou bot_token não encontrados para este usuário');
        }
        
        $session_name = $session_data['session_name'];
        $apikey = $session_data['bot_token'];
        
        $url = "http://45.239.42.53:8080/chat/whatsappNumbers/{$session_name}";
        
        $data = [
            'numbers' => $numeros
        ];
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $apikey
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Erro na API de validação: HTTP ' . $http_code);
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            throw new Exception('Resposta inválida da API de validação');
        }
        
        return $decoded;
        
    } catch (Exception $e) {
        error_log("Erro ao chamar API de validação WhatsApp: " . $e->getMessage());
        throw new Exception('Erro ao validar números: ' . $e->getMessage());
    }
}

/**
 * Descontar créditos do usuário
 */
function descontarCreditos(PDO $pdo_disparador, string $id_session, int $quantidade): void {
    global $pdo; // Usar conexão principal onde está a tabela sessions
    
    try {
        // Verificar se usuário tem créditos suficientes
        $stmt = $pdo->prepare("SELECT creditos FROM sessions WHERE id = ?");
        $stmt->execute([$id_session]);
        $creditos_atuais = $stmt->fetchColumn();
        
        if ($creditos_atuais < $quantidade) {
            throw new Exception('Créditos insuficientes para validação');
        }
        
        // Descontar créditos
        $stmt = $pdo->prepare("UPDATE sessions SET creditos = creditos - ? WHERE id = ?");
        $stmt->execute([$quantidade, $id_session]);
        
    } catch (PDOException $e) {
        error_log("Erro ao descontar créditos: " . $e->getMessage());
        throw new Exception('Erro ao processar créditos');
    }
}

/**
 * Atualizar estrutura da tabela de contatos para incluir campos de validação WhatsApp
 */
function atualizarEstruturaContatos(PDO $pdo_disparador, string $id_session): void {
    try {
        $tabela_contatos = "dis_lista_contatos_{$id_session}";
        
        // Verificar se a tabela existe
        $stmt = $pdo_disparador->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela_contatos]);
        
        if (!$stmt->fetch()) {
            // Se não existe, criar com a estrutura completa
            criarTabelaContatos($pdo_disparador, $id_session);
            return;
        }
        
        // Verificar se as colunas existem
        $stmt = $pdo_disparador->prepare("DESCRIBE `{$tabela_contatos}`");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $alterations = [];
        
        // Verificar cada coluna necessária
        if (!in_array('whatsapp_validado', $columns)) {
            $alterations[] = "ADD COLUMN whatsapp_validado TINYINT(1) DEFAULT 0";
        }
        if (!in_array('whatsapp_ativo', $columns)) {
            $alterations[] = "ADD COLUMN whatsapp_ativo TINYINT(1) DEFAULT 1";
        }
        if (!in_array('data_validacao', $columns)) {
            $alterations[] = "ADD COLUMN data_validacao TIMESTAMP NULL";
        }
        if (!in_array('motivo_inativacao', $columns)) {
            $alterations[] = "ADD COLUMN motivo_inativacao TEXT NULL";
        }
        if (!in_array('validar_necessario', $columns)) {
            $alterations[] = "ADD COLUMN validar_necessario TINYINT(1) DEFAULT 0 COMMENT 'Marcar se este contato precisa validação WhatsApp'";
        }
        if (!in_array('validar_nome_ia', $columns)) {
            $alterations[] = "ADD COLUMN validar_nome_ia TINYINT(1) DEFAULT 0 COMMENT 'Marcar se este contato precisa validação de nome por IA'";
        }
        if (!in_array('nome_validado', $columns)) {
            $alterations[] = "ADD COLUMN nome_validado TINYINT(1) NULL COMMENT 'Resultado da validação IA: 1=nome real, 0=não é nome real, NULL=não validado'";
        }
        
        // Executar alterações se necessário
        if (!empty($alterations)) {
            $sql = "ALTER TABLE `{$tabela_contatos}` " . implode(', ', $alterations);
            $pdo_disparador->exec($sql);
            error_log("DEBUG: Estrutura da tabela {$tabela_contatos} atualizada com campos de validação WhatsApp");
        }
        
    } catch (PDOException $e) {
        error_log("Erro ao atualizar estrutura da tabela de contatos: " . $e->getMessage());
        // Não lançar exceção aqui pois pode ser que a tabela já tenha as colunas
    }
}

/**
 * Busca detalhes completos de uma campanha
 */
function getCampanhaDetalhes($pdo_disparador, $campanha_id, $session_id) {
    try {
        $stmt = $pdo_disparador->prepare("
            SELECT c.*, l.nome as nome_lista, l.total_contatos
            FROM dis_campanhas c
            LEFT JOIN dis_listas l ON l.id = c.id_lista
            WHERE c.id = ? AND c.id_session = ?
        ");
        $stmt->execute([$campanha_id, $session_id]);
        
        $campanha = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campanha) {
            return null;
        }
        
        // Buscar estatísticas adicionais se necessário
        // TODO: Implementar busca de respostas e leads gerados quando a estrutura estiver pronta
        
        return $campanha;
        
    } catch (PDOException $e) {
        error_log("Erro ao buscar detalhes da campanha: " . $e->getMessage());
        return null;
    }
}
