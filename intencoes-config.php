
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/intencoes/database_operations.php';
require_once 'includes/intencoes/table_view.php';
require_once 'includes/intencoes/add_form.php';
require_once 'includes/settings/navigation_buttons.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && isset($_POST['id'])) {
        if (deleteIntencao($pdo, $_POST['id'])) {
            header('Location: intencoes-config.php');
            exit;
        }
    }
    
    if (isset($_POST['edit'])) {
        if (editIntencao($pdo, $_POST['id'], $_POST['titulo'], $_POST['descricao'], $_POST['codigo_produto'] ?? null)) {
            header('Location: intencoes-config.php');
            exit;
        }
    }
    
    if (isset($_POST['add'])) {
        if (addIntencao($pdo, $_POST['titulo'], $_POST['descricao'], $_POST['codigo_produto'] ?? null)) {
            header('Location: intencoes-config.php');
            exit;
        }
    }
}

// Buscar intenções
$result = getIntencoes($pdo);

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    
    <div class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-white mb-8">Configuração de Intenções</h1>
            
            <!-- Navigation Buttons -->
            <div class="mb-8">
                <?php renderNavigationButtons('intencoes'); ?>
            </div>
            
            <!-- Lista de intenções -->
            <div class="bg-gray-800 p-6 rounded-lg mb-8">
                <?php renderTable($result); ?>
            </div>

            <!-- Modal para edição -->
            <div id="promptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-gray-800 p-6 rounded-lg w-full max-w-2xl">
                    <h2 class="text-xl font-bold text-white mb-4">Editar Intenção</h2>
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
                            <label for="promptDescricao" class="block text-white mb-2">Descrição</label>
                            <textarea 
                                name="descricao" 
                                id="promptDescricao"
                                rows="10"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                            ></textarea>
                        </div>
                        <div class="mb-4">
                            <label for="promptCodigoProduto" class="block text-white mb-2">Código do Produto (opcional)</label>
                            <input 
                                type="text" 
                                name="codigo_produto" 
                                id="promptCodigoProduto"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                                placeholder="Ex: PROD001"
                            >
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

            <?php renderAddForm(); ?>
        </div>
    </div>
</div>

<script>
function editPrompt(id, titulo, descricao, codigoProduto) {
    // Decodifica os caracteres HTML primeiro
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = titulo;
    const decodedTitulo = tempDiv.textContent;
    
    tempDiv.innerHTML = descricao;
    const decodedDescricao = tempDiv.textContent;
    
    tempDiv.innerHTML = codigoProduto || '';
    const decodedCodigoProduto = tempDiv.textContent;
    
    document.getElementById('promptId').value = id;
    document.getElementById('promptTitulo').value = decodedTitulo;
    document.getElementById('promptDescricao').value = decodedDescricao;
    document.getElementById('promptCodigoProduto').value = decodedCodigoProduto;
    document.getElementById('promptModal').classList.remove('hidden');
}

function closePromptModal() {
    document.getElementById('promptModal').classList.add('hidden');
}

// Adiciona listener para fechar o modal quando clicar fora dele
document.addEventListener('click', function(event) {
    const modal = document.getElementById('promptModal');
    const modalContent = modal.querySelector('.bg-gray-800');
    
    if (event.target === modal) {
        closePromptModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
