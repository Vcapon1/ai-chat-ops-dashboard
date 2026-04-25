<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';
require_once 'includes/db_disparador.php';
require_once 'includes/campanhas/database_operations.php';

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax_action'] === 'criar_lista') {
        $nome_lista = $_POST['nome_lista'] ?? null;
        $session_id = $_SESSION['user_id'] ?? null;

        if (!$nome_lista || !$session_id) {
            echo json_encode(['success' => false, 'message' => 'Nome da lista e ID da sessão são obrigatórios']);
            exit;
        }

        $result = criarLista($pdo_disparador, $nome_lista, $session_id);
        echo json_encode($result);
        exit;
    }

    if ($_POST['ajax_action'] === 'excluir_lista') {
        $lista_id = $_POST['lista_id'] ?? null;

        if (!$lista_id) {
            echo json_encode(['success' => false, 'message' => 'ID da lista é obrigatório']);
            exit;
        }

        $result = excluirLista($pdo_disparador, $lista_id);
        echo json_encode($result);
        exit;
    }

    if ($_POST['ajax_action'] === 'criar_campanha') {
        $nome_campanha = $_POST['nome_campanha'] ?? null;
        $lista_id = $_POST['lista_id'] ?? null;
        $mensagem = $_POST['mensagem'] ?? null;
        $data_agendada = $_POST['data_agendada'] ?? null;
        $hora_inicio = $_POST['hora_inicio'] ?? null;
        $hora_fim = $_POST['hora_fim'] ?? null;
        $dias_semana = $_POST['dias_semana'] ?? null;
        $usar_ia = isset($_POST['usar_ia']) ? (int)$_POST['usar_ia'] : 0;
    
        $session_id = $_SESSION['user_id'] ?? null;
    
        if (!$nome_campanha || !$lista_id || !$mensagem) {
            echo json_encode(['success' => false, 'message' => 'Nome da campanha, lista e mensagem são obrigatórios']);
            exit;
        }
    
        $result = criarCampanha(
            $pdo_disparador,
            $nome_campanha,
            $lista_id,
            $mensagem,
            $data_agendada,
            $hora_inicio,
            $hora_fim,
            $dias_semana,
            $usar_ia,
            $session_id
        );
        echo json_encode($result);
        exit;
    }

    if ($_POST['ajax_action'] === 'excluir_campanha') {
        $campanha_id = $_POST['campanha_id'] ?? null;

        if (!$campanha_id) {
            echo json_encode(['success' => false, 'message' => 'ID da campanha é obrigatório']);
            exit;
        }

        $result = excluirCampanha($pdo_disparador, $campanha_id);
        echo json_encode($result);
        exit;
    }
    
    if ($_POST['ajax_action'] === 'get_campanha_detalhes') {
        $campanha_id = $_POST['campanha_id'] ?? null;
        $session_id = $_SESSION['user_id'] ?? null;
        
        if (!$campanha_id) {
            echo json_encode(['success' => false, 'message' => 'ID da campanha é obrigatório']);
            exit;
        }
        
        if (!$session_id) {
            echo json_encode(['success' => false, 'message' => 'Sessão não encontrada']);
            exit;
        }
        
        $campanha = getCampanhaDetalhes($pdo_disparador, $campanha_id, $session_id);
        
        if ($campanha) {
            echo json_encode([
                'success' => true, 
                'campanha' => $campanha
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Campanha não encontrada'
            ]);
        }
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="flex">
    <?php require_once 'includes/menu.php'; ?>
    
    <main class="flex-1 p-8 bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Campanhas de Reativação</h1>
                <p class="text-gray-400">Gerencie suas listas de contatos e campanhas de reativação</p>
            </div>

            <!-- Navegação entre seções -->
            <div class="flex space-x-4 mb-8">
                <button onclick="showSection('listas')" id="btn-listas" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Minhas Listas
                </button>
                <button onclick="showSection('campanhas')" id="btn-campanhas" class="px-6 py-3 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors">
                    Campanhas
                </button>
                <button onclick="showSection('relatorios')" id="btn-relatorios" class="px-6 py-3 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors">
                    Relatórios
                </button>
            </div>

            <!-- Seção: Minhas Listas -->
            <div id="section-listas" class="section">
                <?php include 'includes/campanhas/listas_view.php'; ?>
            </div>

            <!-- Seção: Campanhas -->
            <div id="section-campanhas" class="section hidden">
                <?php include 'includes/campanhas/campanhas_view.php'; ?>
            </div>

            <!-- Seção: Relatórios -->
            <div id="section-relatorios" class="section hidden">
                <?php include 'includes/campanhas/relatorios_view.php'; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal para importação de CSV -->
<?php include 'includes/campanhas/upload_modal.php'; ?>

<?php include 'includes/campanhas/campanha_modal.php'; ?>
<?php include 'includes/campanhas/contatos_modal.php'; ?>

<!-- Modal para detalhes da campanha -->
<?php include 'includes/campanhas/detalhes_campanha_modal.php'; ?>

<script src="includes/campanhas/campanhas.js"></script>
<script src="includes/campanhas/detalhes_campanha.js"></script>

<script>
// Variáveis globais para controle dos contatos
let currentListaId = null;
let currentPage = 1;
let currentFilter = 'todos';
let currentSearch = '';

// Garantir que as funções estejam disponíveis globalmente
if (typeof openModal === 'undefined') {
    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
}

if (typeof closeModal === 'undefined') {
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
}

// A função verContatos é definida no contatos_modal.php
</script>

<?php require_once 'includes/footer.php'; ?>
