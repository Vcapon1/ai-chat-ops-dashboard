<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

require_once '../db_disparador.php';
require_once 'database_operations.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_listas':
        $id_session = $_SESSION['user_id'];
        $listas = getListas($pdo_disparador, $id_session);
        echo json_encode(['success' => true, 'listas' => $listas]);
        break;
        
    case 'import_csv':
        handleCSVImport();
        break;
        
    case 'create_campanha':
        handleCreateCampanha();
        break;
        
    case 'delete_lista':
        handleDeleteLista();
        break;
        
    case 'delete_campanha':
        handleDeleteCampanha();
        break;
        
    case 'get_relatorio_campanha':
        handleGetRelatorioCampanha();
        break;
        
    case 'marcar_cancelamento':
        handleMarcarCancelamento();
        break;
        
    case 'get_import_reports':
        handleGetImportReports();
        break;
        
    case 'get_evolution_status':
        handleGetEvolutionStatus();
        break;
        
    case 'get_contatos_lista':
        handleGetContatosLista();
        break;
        
    case 'validar_whatsapp':
        handleValidarWhatsApp();
        break;
        
    case 'validar_nome_ia':
        handleValidarNomeIA();
        break;
        
    case 'validar_lista':
        handleValidarLista();
        break;
        
    case 'editar_telefone_contato':
        handleEditarTelefoneContato();
        break;
        
    case 'editar_contato':
        handleEditarContato();
        break;
        
    case 'alterar_status_contato':
        handleAlterarStatusContato();
        break;
        
    case 'get_campanha_detalhes':
        handleGetCampanhaDetalhes();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
        break;
}

function handleCSVImport() {
    global $pdo_disparador;
    
    try {
        // Verificações básicas
        if (!isset($_FILES['arquivo_csv'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado']);
            return;
        }
        
        if ($_FILES['arquivo_csv']['error'] != 0) {
            echo json_encode(['success' => false, 'message' => 'Erro no upload: ' . $_FILES['arquivo_csv']['error']]);
            return;
        }
        
        $nome_lista = $_POST['nome_lista'] ?? '';
        if (empty($nome_lista)) {
            echo json_encode(['success' => false, 'message' => 'Nome da lista é obrigatório']);
            return;
        }
        
        $file = $_FILES['arquivo_csv']['tmp_name'];
        $handle = fopen($file, 'r');
        
        if (!$handle) {
            echo json_encode(['success' => false, 'message' => 'Não foi possível ler o arquivo']);
            return;
        }
        
        $id_session = $_SESSION['user_id'];
        
        // Garantir que a tabela dis_listas existe
        criarTabelaDisListas($pdo_disparador);
        
        // Garantir que a estrutura da tabela de contatos está atualizada
        atualizarEstruturaContatos($pdo_disparador, $id_session);
        
        // Detectar separador e ler primeira linha como cabeçalho
        $delimiter = detectCSVDelimiter($_FILES['arquivo_csv']['tmp_name']);
        $headers = fgetcsv($handle, 0, $delimiter);
        
        // Verificar se existe mapeamento de colunas vindas do JavaScript
        $mapping = [];
        if (isset($_POST['mapping']) && !empty($_POST['mapping'])) {
            $mapping_data = json_decode($_POST['mapping'], true);
            // O JavaScript envia: {"0": "nome", "1": "telefone", "2": "interesse"}
            // Precisamos inverter para: {"nome": 0, "telefone": 1, "interesse": 2}
            foreach ($mapping_data as $column_index => $field_name) {
                if (!empty($field_name)) {
                    $mapping[$field_name] = (int)$column_index;
                }
            }
        }
        
        // Validar se temos pelo menos nome e telefone mapeados
        if (!isset($mapping['nome']) || !isset($mapping['telefone'])) {
            echo json_encode(['success' => false, 'message' => 'É necessário mapear pelo menos os campos Nome e Telefone']);
            return;
        }
        
        // Gerar nome único para lista se já existir
        $nome_original = $nome_lista;
        $contador = 1;
        
        while (true) {
            // Verificar se nome da lista já existe
            $stmt = $pdo_disparador->prepare("SELECT COUNT(*) FROM dis_listas WHERE id_session = ? AND nome = ?");
            $stmt->execute([$id_session, $nome_lista]);
            if ($stmt->fetchColumn() == 0) {
                break; // Nome disponível
            }
            $nome_lista = $nome_original . ' (' . $contador . ')';
            $contador++;
        }
        
        // Criar lista
        $id_lista = criarLista($pdo_disparador, $id_session, $nome_lista, 1);
        
        if (!$id_lista) {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar lista']);
            return;
        }
        
        // Processar dados
        $contatos = [];
        $linha = 1; // Contador de linha (começando do cabeçalho)
        
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $linha++;
            
            // Verificar se a linha tem colunas suficientes
            $max_index = max(array_values($mapping));
            if (count($data) <= $max_index) {
                continue; // Pular linhas que não têm colunas suficientes
            }
            
            // Extrair dados usando o mapeamento
            $nome = isset($mapping['nome']) && isset($data[$mapping['nome']]) ? trim($data[$mapping['nome']]) : '';
            $telefone = isset($mapping['telefone']) && isset($data[$mapping['telefone']]) ? trim($data[$mapping['telefone']]) : '';
            $interesse = isset($mapping['interesse']) && isset($data[$mapping['interesse']]) ? trim($data[$mapping['interesse']]) : '';
            
            // SEMPRE limpar telefone: remover letras, caracteres especiais e espaços (incluindo pontos)
            $telefone = preg_replace('/[^0-9]/', '', $telefone);
            
            // Aplicar validação de formato brasileiro se marcada
            $validar_formato = isset($_POST['validar_formato']) && $_POST['validar_formato'] === 'true';
            if ($validar_formato) {
                $telefone = validarFormatoTelefoneParaBrasil($telefone);
            }
            
            // Debug: log do telefone após limpeza
            error_log("DEBUG: Telefone após limpeza: '$telefone' (validar_formato: " . ($validar_formato ? 'true' : 'false') . ")");
            
            // Validar se nome e telefone não estão vazios
            if (empty($nome) || empty($telefone)) {
                continue;
            }
            
            // Configurar flags de validação
            $validar_whatsapp = isset($_POST['validar_whatsapp']) && $_POST['validar_whatsapp'] === 'true';
            $validar_nome_ia = isset($_POST['validar_nome_ia']) && $_POST['validar_nome_ia'] === 'true';
            
            // Adicionar contato ao array
            $contatos[] = [
                'nome' => $nome,
                'telefone' => $telefone,
                'interesse' => $interesse,
                'validar_necessario' => $validar_whatsapp ? 1 : 0,  // Corrigindo nome da flag
                'validar_nome_ia' => $validar_nome_ia ? 1 : 0
            ];
        }
        
        // Inserir todos os contatos de uma vez
        $contatos_inseridos = 0;
        if (!empty($contatos)) {
            if (inserirContatos($pdo_disparador, $id_session, $id_lista, $contatos)) {
                $contatos_inseridos = count($contatos);
            }
        }
        
        fclose($handle);
        
        if ($contatos_inseridos > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Lista '{$nome_lista}' criada com sucesso! {$contatos_inseridos} contatos importados.",
                'lista_id' => $id_lista,
                'nome_lista' => $nome_lista,
                'total_contatos' => $contatos_inseridos
            ]);
        } else {
            // Se nenhum contato foi inserido, remover a lista criada e sua tabela
            $stmt = $pdo_disparador->prepare("DELETE FROM dis_listas WHERE id = ?");
            $stmt->execute([$id_lista]);
            
            // Remover tabela de contatos vazia
            // Limpar contatos da tabela da sessão para esta lista (se existirem)
            $pdo_disparador->prepare("DELETE FROM `dis_lista_contatos_{$id_session}` WHERE id_lista = ?")->execute([$id_lista]);
            
            echo json_encode(['success' => false, 'message' => 'Nenhum contato válido encontrado no arquivo']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleCreateCampanha() {
    global $pdo_disparador;
    
    try {
        $nome_campanha = $_POST['nome_campanha'] ?? '';
        $id_lista = $_POST['id_lista'] ?? '';
        $mensagem = $_POST['mensagem'] ?? '';
        $data_inicio = $_POST['data_inicio'] ?? '';
        $hora_inicio = $_POST['hora_inicio'] ?? '';
        $hora_fim = $_POST['hora_fim'] ?? '';
        $dias_semana = $_POST['dias_semana'] ?? '';
        $usar_ia = isset($_POST['usar_ia']) ? 1 : 0;
        $contexto_empresa = $_POST['contexto_empresa'] ?? '';
        $tom_voz = $_POST['tom_voz'] ?? '';
        
        // Validar campos obrigatórios básicos
        if (empty($nome_campanha)) {
            throw new Exception('Nome da campanha é obrigatório');
        }
        
        if (empty($id_lista)) {
            throw new Exception('Selecione uma lista de contatos');
        }
        
        if (empty($mensagem)) {
            throw new Exception('Mensagem é obrigatória');
        }
        
        if (empty($data_inicio)) {
            throw new Exception('Data de início é obrigatória');
        }
        
        if (empty($hora_inicio)) {
            throw new Exception('Hora de início é obrigatória');
        }
        
        if (empty($hora_fim)) {
            throw new Exception('Hora de fim é obrigatória');
        }
        
        // Validar contexto da empresa e tom de voz se IA estiver ativada
        if ($usar_ia) {
            if (empty($contexto_empresa)) {
                throw new Exception('Contexto da empresa é obrigatório quando usar IA');
            }
            
            if (empty($tom_voz)) {
                throw new Exception('Tom de voz é obrigatório quando usar IA');
            }
            
            // Validar se o tom de voz é uma das opções válidas
            $tons_validos = ['descontraido', 'profissional', 'entusiasmado', 'conversacional'];
            if (!in_array($tom_voz, $tons_validos)) {
                throw new Exception('Tom de voz inválido');
            }
        }
        
        // Validar e processar dias da semana
        $dias_array = json_decode($dias_semana, true);
        if (empty($dias_array) || !is_array($dias_array)) {
            throw new Exception('Selecione pelo menos um dia da semana');
        }
        
        // Validar horários
        if ($hora_inicio >= $hora_fim) {
            throw new Exception('Hora final deve ser maior que hora inicial');
        }
        
        // Validar data futura
        if (strtotime($data_inicio) < strtotime(date('Y-m-d'))) {
            throw new Exception('A data de início deve ser hoje ou futura');
        }
        
        $id_session = $_SESSION['user_id'];
        
        // Criar campanha com novos campos incluindo tom_voz
        if (criarCampanhaAvancada($pdo_disparador, $id_session, $nome_campanha, $id_lista, $mensagem, 
                                  $data_inicio, $hora_inicio, $hora_fim, $dias_array, $usar_ia, $contexto_empresa, $tom_voz)) {
            echo json_encode(['success' => true, 'message' => 'Campanha criada com sucesso!']);
        } else {
            throw new Exception('Erro ao criar campanha');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDeleteLista() {
    global $pdo_disparador;
    
    try {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            throw new Exception('ID da lista não informado');
        }
        
        $id_session = $_SESSION['user_id']; // Este é o id da tabela sessions
        if (excluirLista($pdo_disparador, $id_session, $id)) {
            echo json_encode(['success' => true, 'message' => 'Lista excluída com sucesso']);
        } else {
            throw new Exception('Erro ao excluir lista');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDeleteCampanha() {
    global $pdo_disparador;
    
    try {
        $id = $_POST['id'] ?? '';
        
        if (empty($id)) {
            throw new Exception('ID da campanha não informado');
        }
        
        $id_session = $_SESSION['user_id']; // Este é o id da tabela sessions
        $stmt = $pdo_disparador->prepare("DELETE FROM dis_campanhas WHERE id = ? AND id_session = ? AND foi_disparada = 0");
        
        if ($stmt->execute([$id, $id_session])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Campanha excluída com sucesso']);
            } else {
                throw new Exception('Campanha não encontrada ou já foi disparada');
            }
        } else {
            throw new Exception('Erro ao excluir campanha');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetRelatorioCampanha() {
    global $pdo_disparador;
    
    try {
        $id_campanha = $_GET['id'] ?? '';
        
        if (empty($id_campanha)) {
            throw new Exception('ID da campanha não informado');
        }
        
        $id_session = $_SESSION['user_id'];
        $relatorio = getRelatorioCampanha($pdo_disparador, $id_session, $id_campanha);
        
        if (!$relatorio) {
            throw new Exception('Campanha não encontrada');
        }
        
        echo json_encode(['success' => true, 'relatorio' => $relatorio]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleMarcarCancelamento() {
    global $pdo_disparador;
    
    try {
        $id_campanha = $_POST['id_campanha'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        
        if (empty($id_campanha) || empty($telefone)) {
            throw new Exception('Dados obrigatórios não informados');
        }
        
        $id_session = $_SESSION['user_id'];
        
        if (marcarCancelamentoCampanha($pdo_disparador, $id_session, $id_campanha, $telefone)) {
            echo json_encode(['success' => true, 'message' => 'Cancelamento registrado com sucesso']);
        } else {
            throw new Exception('Erro ao registrar cancelamento');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetImportReports() {
    global $pdo_disparador;
    
    require_once __DIR__ . '/import_validator.php';
    
    try {
        $id_session = $_SESSION['user_id'];
        $limit = $_GET['limit'] ?? 10;
        
        $reports = ImportValidator::getImportReports($pdo_disparador, $id_session, $limit);
        
        echo json_encode(['success' => true, 'reports' => $reports]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetEvolutionStatus() {
    global $pdo_disparador;
    
    require_once __DIR__ . '/evolution_validator.php';
    
    try {
        $temp_file_name = $_GET['file'] ?? '';
        
        if (empty($temp_file_name)) {
            throw new Exception('Nome do arquivo não informado');
        }
        
        $temp_file = sys_get_temp_dir() . "/" . $temp_file_name;
        $validator = new EvolutionValidator('', '', ''); // Dummy instance just for status check
        $status = $validator->getValidationStatus($temp_file);
        
        echo json_encode(['success' => true, 'status' => $status]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGetContatosLista() {
    global $pdo_disparador;
    
    try {
        $id_lista = $_GET['id'] ?? '';
        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'todos';
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        if (empty($id_lista)) {
            throw new Exception('ID da lista não informado');
        }
        
        $id_session = $_SESSION['user_id'];
        $tabela_contatos = "dis_lista_contatos_{$id_session}";
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT nome FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lista) {
            throw new Exception('Lista não encontrada');
        }
        
        // Construir WHERE clause para busca
        $where_conditions = ["id_lista = ?"];
        $params = [$id_lista];
        
        if (!empty($search)) {
            $where_conditions[] = "(nome LIKE ? OR telefone LIKE ?)";
            $search_term = "%{$search}%";
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        // Filtros por status
        if ($filter === 'ativos') {
            $where_conditions[] = "whatsapp_ativo = 1";
        } elseif ($filter === 'inativos') {
            $where_conditions[] = "whatsapp_ativo = 0";
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Contar total de registros
        $count_sql = "SELECT COUNT(*) FROM {$tabela_contatos} {$where_clause}";
        $count_stmt = $pdo_disparador->prepare($count_sql);
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // Buscar contatos com paginação
        $sql = "SELECT nome, telefone, interesse, data_criacao, whatsapp_validado, whatsapp_ativo, motivo_inativacao 
                FROM {$tabela_contatos} 
                {$where_clause}
                ORDER BY data_criacao DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo_disparador->prepare($sql);
        $stmt->execute($params);
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'contatos' => $contatos, 
            'nome_lista' => $lista['nome'],
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleValidarWhatsApp() {
    global $pdo_disparador;
    
    try {
        $id_lista = $_POST['id_lista'] ?? '';
        
        if (empty($id_lista)) {
            throw new Exception('ID da lista não informado');
        }
        
        $id_session = $_SESSION['user_id'];
        
        // Atualizar estrutura da tabela de contatos se necessário
        atualizarEstruturaContatos($pdo_disparador, $id_session);
        
        // Validar WhatsApp dos contatos
        $resultado = validarWhatsAppContatos($pdo_disparador, $id_session, $id_lista);
        
        echo json_encode([
            'success' => true,
            'message' => $resultado['message'],
            'resultados' => $resultado['resultados']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleValidarLista() {
    global $pdo_disparador;
    
    try {
        $id_lista = $_POST['id'] ?? '';
        
        if (empty($id_lista)) {
            throw new Exception('ID da lista não informado');
        }
        
        $id_session = $_SESSION['user_id'];
        
        // Marcar lista para validação
        if (marcarListaParaValidacao($pdo_disparador, $id_session, $id_lista)) {
            // Iniciar processo de validação
            if (iniciarValidacaoLista($pdo_disparador, $id_session, $id_lista)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Validação da lista iniciada com sucesso!'
                ]);
            } else {
                throw new Exception('Erro ao iniciar validação');
            }
        } else {
            throw new Exception('Erro ao marcar lista para validação');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleEditarTelefoneContato() {
    global $pdo_disparador;
    
    try {
        $id_lista = $_POST['id_lista'] ?? '';
        $telefone_atual = $_POST['telefone_atual'] ?? '';
        $telefone_novo = $_POST['telefone_novo'] ?? '';
        
        if (empty($id_lista) || empty($telefone_atual) || empty($telefone_novo)) {
            throw new Exception('Todos os campos são obrigatórios');
        }
        
        // Validar formato do telefone novo
        if (!preg_match('/^\d{10,15}$/', $telefone_novo)) {
            throw new Exception('Telefone deve conter apenas números e ter entre 10 e 15 dígitos');
        }
        
        $id_session = $_SESSION['user_id'];
        $tabela_contatos = "dis_lista_contatos_{$id_session}";
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        if (!$stmt->fetch()) {
            throw new Exception('Lista não encontrada');
        }
        
        // Verificar se o telefone atual existe na lista
        $stmt = $pdo_disparador->prepare("SELECT id FROM {$tabela_contatos} WHERE telefone = ?");
        $stmt->execute([$telefone_atual]);
        if (!$stmt->fetch()) {
            throw new Exception('Contato não encontrado');
        }
        
        // Verificar se o telefone novo já existe na lista
        $stmt = $pdo_disparador->prepare("SELECT id FROM {$tabela_contatos} WHERE telefone = ?");
        $stmt->execute([$telefone_novo]);
        if ($stmt->fetch()) {
            throw new Exception('Já existe um contato com este telefone na lista');
        }
        
        // Atualizar telefone
        $stmt = $pdo_disparador->prepare("UPDATE {$tabela_contatos} SET telefone = ? WHERE telefone = ?");
        if ($stmt->execute([$telefone_novo, $telefone_atual])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Telefone atualizado com sucesso']);
            } else {
                throw new Exception('Nenhum registro foi atualizado');
            }
        } else {
            throw new Exception('Erro ao atualizar telefone');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleEditarContato() {
    global $pdo_disparador;
    
    try {
        $id_lista = $_POST['id_lista'] ?? '';
        $telefone_atual = $_POST['telefone_atual'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $telefone_novo = $_POST['telefone_novo'] ?? '';
        $interesse = $_POST['interesse'] ?? '';
        
        if (empty($id_lista) || empty($telefone_atual) || empty($nome) || empty($telefone_novo)) {
            throw new Exception('Nome, telefone e lista são obrigatórios');
        }
        
        // Validar formato do telefone novo
        if (!preg_match('/^\d{10,15}$/', $telefone_novo)) {
            throw new Exception('Telefone deve conter apenas números e ter entre 10 e 15 dígitos');
        }
        
        $id_session = $_SESSION['user_id'];
        $tabela_contatos = "dis_lista_contatos_{$id_session}";
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        if (!$stmt->fetch()) {
            throw new Exception('Lista não encontrada');
        }
        
        // Verificar se o contato atual existe na lista
        $stmt = $pdo_disparador->prepare("SELECT id FROM {$tabela_contatos} WHERE telefone = ?");
        $stmt->execute([$telefone_atual]);
        if (!$stmt->fetch()) {
            throw new Exception('Contato não encontrado');
        }
        
        // Se o telefone mudou, verificar se o telefone novo já existe na lista
        if ($telefone_atual !== $telefone_novo) {
            $stmt = $pdo_disparador->prepare("SELECT id FROM {$tabela_contatos} WHERE telefone = ?");
            $stmt->execute([$telefone_novo]);
            if ($stmt->fetch()) {
                throw new Exception('Já existe um contato com este telefone na lista');
            }
        }
        
        // Atualizar contato
        $stmt = $pdo_disparador->prepare("UPDATE {$tabela_contatos} SET nome = ?, telefone = ?, interesse = ? WHERE telefone = ?");
        if ($stmt->execute([$nome, $telefone_novo, $interesse, $telefone_atual])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Contato atualizado com sucesso']);
            } else {
                throw new Exception('Nenhum registro foi atualizado');
            }
        } else {
            throw new Exception('Erro ao atualizar contato');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleAlterarStatusContato() {
    global $pdo_disparador;
    
    try {
        $id_lista = $_POST['id_lista'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $status = $_POST['status'] ?? '';
        $motivo = $_POST['motivo'] ?? '';
        
        if (empty($id_lista) || empty($telefone) || empty($status)) {
            throw new Exception('Dados obrigatórios não informados');
        }
        
        if (!in_array($status, ['ativar', 'inativar'])) {
            throw new Exception('Status inválido');
        }
        
        $id_session = $_SESSION['user_id'];
        $tabela_contatos = "dis_lista_contatos_{$id_session}";
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        if (!$stmt->fetch()) {
            throw new Exception('Lista não encontrada');
        }
        
        // Preparar valores baseado no status
        if ($status === 'ativar') {
            $whatsapp_ativo = 1;
            $motivo_inativacao = null;
            $action_message = 'ativado';
        } else {
            $whatsapp_ativo = 0;
            $motivo_inativacao = $motivo ?: 'Inativado manualmente';
            $action_message = 'inativado';
        }
        
        // Atualizar status do contato
        $stmt = $pdo_disparador->prepare("
            UPDATE {$tabela_contatos} 
            SET whatsapp_ativo = ?, motivo_inativacao = ? 
            WHERE telefone = ?
        ");
        
        if ($stmt->execute([$whatsapp_ativo, $motivo_inativacao, $telefone])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => "Contato {$action_message} com sucesso"]);
            } else {
                throw new Exception('Contato não encontrado');
            }
        } else {
            throw new Exception("Erro ao {$status} contato");
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleValidarNomeIA() {
    global $pdo_disparador, $pdo;
    
    try {
        $id_lista = $_POST['id_lista'] ?? '';
        
        if (empty($id_lista)) {
            throw new Exception('ID da lista é obrigatório');
        }
        
        $id_session = $_SESSION['user_id'];
        $tabela_contatos = "dis_lista_contatos_{$id_session}";
        
        // Verificar se a lista pertence ao usuário
        $stmt = $pdo_disparador->prepare("SELECT id FROM dis_listas WHERE id = ? AND id_session = ?");
        $stmt->execute([$id_lista, $id_session]);
        if (!$stmt->fetch()) {
            throw new Exception('Lista não encontrada');
        }
        
        // Buscar contatos que precisam de validação de nome por IA
        $stmt = $pdo_disparador->prepare("
            SELECT id, nome 
            FROM {$tabela_contatos} 
            WHERE id_lista = ? AND validar_nome_ia = 1 AND nome_validado IS NULL
            LIMIT 50
        ");
        $stmt->execute([$id_lista]);
        $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contatos)) {
            echo json_encode(['success' => true, 'message' => 'Não há nomes para validar', 'total_processados' => 0]);
            return;
        }
        
        // Buscar chave OpenAI do usuário
        $stmt = $pdo->prepare("SELECT openai_key FROM usuarios WHERE id = ?");
        $stmt->execute([$id_session]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['openai_key'])) {
            throw new Exception('Chave OpenAI não configurada. Configure nas configurações do usuário.');
        }
        
        $processados = 0;
        $erros = 0;
        
        foreach ($contatos as $contato) {
            try {
                $resultado = validarNomeComIA($contato['nome'], $user['openai_key']);
                
                if ($resultado['success']) {
                    // Atualizar resultado da validação
                    $stmt = $pdo_disparador->prepare("
                        UPDATE {$tabela_contatos} 
                        SET nome_validado = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$resultado['e_nome_real'] ? 1 : 0, $contato['id']]);
                    
                    // Descontar 1 crédito
                    descontarCreditos($pdo_disparador, $id_session, 1);
                    
                    $processados++;
                } else {
                    $erros++;
                    error_log("Erro ao validar nome '{$contato['nome']}': " . $resultado['message']);
                }
                
                // Pequena pausa para não sobrecarregar a API
                usleep(100000); // 0.1 segundo
                
            } catch (Exception $e) {
                $erros++;
                error_log("Erro ao processar nome '{$contato['nome']}': " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Validação concluída: {$processados} processados, {$erros} erros",
            'total_processados' => $processados,
            'total_erros' => $erros
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function validarNomeComIA($nome, $openai_key) {
    $prompt = "Analise se '{$nome}' é um nome real de pessoa física. Responda apenas 'SIM' se for um nome real de pessoa (ex: João Silva, Maria Santos) ou 'NÃO' se for nome de empresa, fantasia, apelido ou não for um nome real. Resposta:";
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 10,
        'temperature' => 0.1
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $openai_key
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code !== 200) {
        return ['success' => false, 'message' => 'Erro na API OpenAI: HTTP ' . $http_code];
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'message' => 'Resposta inválida da API OpenAI'];
    }
    
    $resposta = trim(strtoupper($result['choices'][0]['message']['content']));
    $e_nome_real = (strpos($resposta, 'SIM') !== false);
    
    return [
        'success' => true,
        'e_nome_real' => $e_nome_real,
        'resposta_ia' => $resposta
    ];
}

function validarFormatoTelefoneParaBrasil($telefone) {
    // Remove todos os caracteres não numéricos (já foi feito antes, mas garantindo)
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    
    // Se não começar com 55 (código do Brasil), adicionar
    if (strlen($telefone) >= 10 && substr($telefone, 0, 2) != '55') {
        // Se começar com 0, remove
        if (substr($telefone, 0, 1) == '0') {
            $telefone = substr($telefone, 1);
        }
        
        // Formato brasileiro: deve ter 10 ou 11 dígitos
        if (strlen($telefone) == 10) {
            // Adiciona o 9 no celular se não tiver (celulares começam com 6,7,8,9)
            if (in_array(substr($telefone, 2, 1), ['6', '7', '8', '9'])) {
                $telefone = substr($telefone, 0, 2) . '9' . substr($telefone, 2);
            }
        }
        
        // Adiciona código do país (55) se tiver 10 ou 11 dígitos
        if (strlen($telefone) >= 10 && strlen($telefone) <= 11) {
            $telefone = '55' . $telefone;
        }
    } else {
        // Já tem 55, verificar se precisa adicionar 9
        if (strlen($telefone) == 12) { // 55 + 10 dígitos
            $parte_sem_55 = substr($telefone, 2);
            // Adiciona o 9 no celular se não tiver
            if (in_array(substr($parte_sem_55, 2, 1), ['6', '7', '8', '9'])) {
                $telefone = '55' . substr($parte_sem_55, 0, 2) . '9' . substr($parte_sem_55, 2);
            }
        }
    }
    
    return $telefone;
}

// Manter função original para compatibilidade
function validarFormatoTelefone($telefone) {
    return validarFormatoTelefoneParaBrasil($telefone);
}

function detectCSVDelimiter($file) {
    $handle = fopen($file, 'r');
    if (!$handle) return ',';
    
    // Ler as primeiras linhas para análise
    $sample = '';
    for ($i = 0; $i < 5; $i++) {
        $line = fgets($handle);
        if ($line === false) break;
        $sample .= $line;
    }
    fclose($handle);
    
    // Detectar separador mais comum
    $comma_count = substr_count($sample, ',');
    $semicolon_count = substr_count($sample, ';');
    
    // Retornar o separador mais frequente
    return ($semicolon_count > $comma_count) ? ';' : ',';
}

function handleGetCampanhaDetalhes() {
    global $pdo_disparador;
    
    try {
        $id_campanha = $_GET['id'] ?? '';
        
        if (empty($id_campanha)) {
            throw new Exception('ID da campanha não informado');
        }
        
        $id_session = $_SESSION['user_id'];
        $campanha = getCampanhaDetalhes($pdo_disparador, $id_campanha, $id_session);
        
        if (!$campanha) {
            throw new Exception('Campanha não encontrada');
        }
        
        echo json_encode(['success' => true, 'campanha' => $campanha]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
