
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/utils.php';
require_once 'includes/db_queries.php';
require_once 'includes/filters.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Para debug
error_log("Página de conversas acessada por usuário ID: " . $_SESSION['user_id']);

// Configuração da paginação
$items_per_page = 5;
$current_page_number = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page_number - 1) * $items_per_page;

// Processar filtros
$filters = buildFilters($_SESSION['user_id']);
$where_conditions = $filters['conditions'];
$params = $filters['params'];

error_log("Filtros aplicados: " . json_encode($filters));

// Função para obter descrição da ação com base no ID
function getActionDescription($action_id) {
    $actions = [
        1 => 'Perguntar para o administrador',
        2 => 'Retomar essa conversa em outro horário',
        3 => 'Reagir a uma mensagem',
        4 => 'Contatar outra pessoa',
        5 => 'Gerar um lead',
        6 => 'Informou o nome',
        7 => 'desativar cliente',
        8 => 'enviar um vídeo',
        9 => 'Enviar uma mensagem extra',
        10 => 'enviar a localizaçao do mapa',
        11 => 'enviar um documento pdf'
    ];
    
    return isset($actions[$action_id]) ? $actions[$action_id] : 'Ação desconhecida';
}

try {
    // Debug para verificar consultas
    error_log("Iniciando consulta getConversationsCount");
    
    $total_results = getConversationsCount($pdo, $where_conditions, $params);
    $total_pages = max(1, ceil($total_results / $items_per_page));
    $current_page_number = min($current_page_number, $total_pages);
    
    error_log("Total de resultados: $total_results, Total páginas: $total_pages, Página atual: $current_page_number");
    
    $conversas = getConversations($pdo, $where_conditions, $params, $items_per_page, $offset);
    
    error_log("Consulta getConversations concluída. Resultados: " . count($conversas));
    
    // Debug dos resultados
    if (empty($conversas)) {
        error_log("Nenhuma conversa encontrada com os filtros aplicados");
    } else {
        error_log("Primeira conversa: " . json_encode(reset($conversas)));
    }
    
} catch (PDOException $e) {
    error_log("Erro ao buscar conversas: " . $e->getMessage());
    $conversas = [];
    $total_pages = 0;
}

// Processar alteração de status da conversa
if (isset($_POST['toggle_status']) && isset($_POST['client_id'])) {
    $client_id = $_POST['client_id'];
    $new_status = $_POST['new_status'];
    
    try {
        if (updateConversationStatus($pdo, $client_id, $new_status, $_SESSION['user_id'])) {
            header("Location: messages_in.php?client_id=" . $client_id);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status: " . $e->getMessage());
    }
}

// Processar envio de mensagem
if (isset($_POST['send_message']) && isset($_POST['client_id']) && isset($_POST['message'])) {
    $client_id = $_POST['client_id'];
    $message = $_POST['message'];
    
    try {
        if (sendMessage($pdo, $client_id, $message, $_SESSION['user_id'])) {
            header("Location: messages_in.php?client_id=" . $client_id);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erro ao processar envio de mensagem: " . $e->getMessage());
    }
}

include 'includes/header.php';
?>

<div class="flex">
    <?php include 'includes/menu.php'; ?>

    <main class="flex-1 p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-[calc(100vh-120px)]">
            <!-- Filtros -->
            <div class="md:col-span-3 bg-gray-800 rounded-lg shadow-xl p-4 mb-4">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-white text-sm mb-2">Buscar por nome/telefone</label>
                        <input type="text" name="search" 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               class="w-full px-3 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-white text-sm mb-2">Data Início</label>
                        <input type="date" name="date_start" 
                               value="<?php echo isset($_GET['date_start']) ? htmlspecialchars($_GET['date_start']) : ''; ?>"
                               class="w-full px-3 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-white text-sm mb-2">Data Fim</label>
                        <input type="date" name="date_end" 
                               value="<?php echo isset($_GET['date_end']) ? htmlspecialchars($_GET['date_end']) : ''; ?>"
                               class="w-full px-3 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="flex-1 min-w-[200px] flex items-end">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Conversas -->
            <div class="bg-gray-800 rounded-lg shadow-xl p-4 overflow-y-auto max-h-full">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Conversas Recentes</h2>
                    <span class="text-gray-400 text-sm"><?php echo $total_results; ?> resultado(s)</span>
                </div>
                
                <?php if (empty($conversas)): ?>
                <div class="bg-gray-700 rounded-lg p-4 text-center">
                    <p class="text-gray-300">Nenhuma conversa encontrada</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($conversas as $conversa): ?>
                        <div class="bg-gray-700 rounded-lg p-4 hover:bg-gray-600 cursor-pointer transition-all" 
                             onclick="loadMessages(<?php echo $conversa['id']; ?>)">
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-start">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        <div>
                                            <h3 class="text-white font-medium">
                                                <?php echo htmlspecialchars($conversa['client_name'] ?? 'Cliente sem nome'); ?>
                                            </h3>
                                            <p class="text-gray-400 text-sm">
                                                <?php echo htmlspecialchars($conversa['ultima_mensagem'] ?? ''); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <!-- Ícone 1: Instrução -->
                                        <span class="text-blue-400 hover:text-blue-300 tooltip-trigger" title="<?php echo htmlspecialchars($conversa['instrucao'] ?? 'Sem instrução definida'); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square-text">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                                <path d="M13 8H7"/>
                                                <path d="M17 12H7"/>
                                            </svg>
                                        </span>
                                        <!-- Ícone 2: Bot status -->
                                        <span class="<?php echo $conversa['conversa_ativa'] ? 'text-green-400 hover:text-green-300' : 'text-red-400 hover:text-red-300'; ?> tooltip-trigger" title="Bot <?php echo $conversa['conversa_ativa'] ? 'Ativo' : 'Inativo'; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot">
                                                <path d="M12 8V4H8"/>
                                                <rect width="16" height="12" x="4" y="8" rx="2"/>
                                                <path d="M2 14h2"/>
                                                <path d="M20 14h2"/>
                                                <path d="M15 13v2"/>
                                                <path d="M9 13v2"/>
                                            </svg>
                                        </span>
                                        <!-- Horário -->
                                        <span class="text-gray-400 text-xs ml-2">
                                            <?php echo $conversa['horario'] ? date('H:i', strtotime($conversa['horario'])) : ''; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-400">
                                    <?php 
                                    if (isset($conversa['horario'])) {
                                        echo date('d/m/Y', strtotime($conversa['horario']));
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($total_pages > 1): ?>
                <div class="mt-4 flex justify-center">
                    <nav class="flex items-center gap-1">
                        <?php if ($current_page_number > 1): ?>
                            <a href="?page=<?php echo ($current_page_number - 1); ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['date_start']) ? '&date_start=' . htmlspecialchars($_GET['date_start']) : ''; ?><?php echo isset($_GET['date_end']) ? '&date_end=' . htmlspecialchars($_GET['date_end']) : ''; ?>" 
                               class="px-2 py-1 bg-gray-700 text-white rounded hover:bg-gray-600">
                                Anterior
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $current_page_number - 2); $i <= min($total_pages, $current_page_number + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['date_start']) ? '&date_start=' . htmlspecialchars($_GET['date_start']) : ''; ?><?php echo isset($_GET['date_end']) ? '&date_end=' . htmlspecialchars($_GET['date_end']) : ''; ?>" 
                               class="px-3 py-1 <?php echo $i === $current_page_number ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600'; ?> text-white rounded">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($current_page_number < $total_pages): ?>
                            <a href="?page=<?php echo ($current_page_number + 1); ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?><?php echo isset($_GET['date_start']) ? '&date_start=' . htmlspecialchars($_GET['date_start']) : ''; ?><?php echo isset($_GET['date_end']) ? '&date_end=' . htmlspecialchars($_GET['date_end']) : ''; ?>" 
                               class="px-2 py-1 bg-gray-700 text-white rounded hover:bg-gray-600">
                                Próxima
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

            <!-- Área da Conversa -->
            <div class="md:col-span-2 bg-gray-800 rounded-lg shadow-xl flex flex-col h-full">
                <div id="messages-container" class="h-full">
                    <iframe id="mensagens-frame" name="mensagens-frame" src="about:blank" class="w-full h-full border-0 rounded-lg"></iframe>
                </div>
                <input type="hidden" id="current-client-id" value="">
            </div>
        </div>
    </main>
</div>

<style>
.tooltip {
    position: absolute;
    background-color: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 5px 8px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 1000;
    max-width: 300px;
    word-wrap: break-word;
    display: none;
    pointer-events: none;
    top: 0;
    transform: translateY(-100%);
    margin-top: -8px;
}
</style>

<script>
function loadMessages(clientId) {
    const iframe = document.getElementById('mensagens-frame');
    const currentClientId = document.getElementById('current-client-id');
    iframe.src = `messages_in.php?client_id=${clientId}`;
    currentClientId.value = clientId;
}

// Tooltip functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create tooltip element
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    document.body.appendChild(tooltip);

    // Get all elements with tooltip
    const triggerElements = document.querySelectorAll('.tooltip-trigger');
    
    triggerElements.forEach(element => {
        element.addEventListener('mouseover', function(e) {
            const tooltipText = this.getAttribute('title');
            if (tooltipText && tooltipText !== 'null' && tooltipText !== 'undefined') {
                tooltip.textContent = tooltipText;
                tooltip.style.display = 'block';
                
                // Position the tooltip
                const rect = element.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) + 'px';
                tooltip.style.top = rect.top + 'px';
            }
        });
        
        element.addEventListener('mouseout', function() {
            tooltip.style.display = 'none';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
