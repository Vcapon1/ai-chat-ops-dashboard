
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/objecoes/database_operations.php';
require_once 'includes/objecoes/table_view.php';
require_once 'includes/objecoes/add_form.php';
require_once 'includes/settings/navigation_buttons.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get the selected intention from GET parameter
$selectedIntencao = isset($_GET['intencao']) ? $_GET['intencao'] : '0';

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && isset($_POST['id'])) {
        if (deleteObjecao($pdo, $_POST['id'])) {
            header('Location: objecoes-config.php' . ($selectedIntencao != '0' ? '?intencao=' . $selectedIntencao : ''));
            exit;
        }
    }
    
    if (isset($_POST['edit'])) {
        if (editObjecao($pdo, $_POST['id'], $_POST['titulo'], $_POST['prompt'], $_POST['intencao'])) {
            header('Location: objecoes-config.php' . ($selectedIntencao != '0' ? '?intencao=' . $selectedIntencao : ''));
            exit;
        }
    }
    
    if (isset($_POST['add'])) {
        if (addObjecao($pdo, $_POST['titulo'], $_POST['prompt'], $_POST['intencao'])) {
            header('Location: objecoes-config.php' . ($selectedIntencao != '0' ? '?intencao=' . $selectedIntencao : ''));
            exit;
        }
    }
}

// Buscar objeções com filtro de intenção
$result = getObjecoes($pdo, $selectedIntencao);

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    
    <div class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-white mb-8">Configuração de Objeções</h1>
            
            <!-- Navigation Buttons -->
            <div class="mb-8">
                <?php renderNavigationButtons('objecoes'); ?>
            </div>
            
            <!-- Filtro de intenções -->
            <?php renderIntencaoFilter($pdo, $selectedIntencao); ?>
            
            <!-- Lista de objeções -->
            <div class="bg-gray-800 p-6 rounded-lg mb-8">
                <?php renderTable($result); ?>
            </div>

            <!-- Modal para edição -->
            <div id="promptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <div class="bg-gray-800 p-6 rounded-lg w-full max-w-2xl">
                    <h2 class="text-xl font-bold text-white mb-4">Editar Objeção</h2>
                    <form method="POST" id="promptForm">
                        <input type="hidden" name="id" id="promptId">
                        <div class="mb-4">
                            <label for="promptTitulo" class="block text-white mb-2">Título</label>
                            <input 
                                type="text" 
                                name="titulo" 
                                id="promptTitulo"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                            >
                        </div>
                        <div class="mb-4">
                            <label for="intencao" class="block text-white mb-2">Intenção</label>
                            <select 
                                name="intencao" 
                                id="promptIntencao"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                            >
                                <option value="0">Geral</option>
                                <?php foreach (getIntencoes($pdo) as $intencao): ?>
                                <option value="<?php echo $intencao['id']; ?>">
                                    <?php echo htmlspecialchars($intencao['titulo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="promptText" class="block text-white mb-2">Prompt</label>
                            <textarea 
                                name="prompt" 
                                id="promptText"
                                rows="10"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                            ></textarea>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button 
                                type="button"
                                onclick="closePromptModal()"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Cancelar
                            </button>
                            <button 
                                type="submit"
                                name="edit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php renderAddForm($pdo, $selectedIntencao); ?>
        </div>
    </div>
</div>

<script>
function editPrompt(id, titulo, prompt, intencao) {
    document.getElementById('promptId').value = id;
    document.getElementById('promptTitulo').value = titulo.replace(/\\'/g, "'");
    document.getElementById('promptText').value = prompt.replace(/\\'/g, "'");
    document.getElementById('promptIntencao').value = intencao || '0';
    document.getElementById('promptModal').classList.remove('hidden');
}

function closePromptModal() {
    document.getElementById('promptModal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer.php'; ?>
