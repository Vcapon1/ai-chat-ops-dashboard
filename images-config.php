
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/images/database_operations.php';
require_once 'includes/images/table_view.php';
require_once 'includes/images/add_form.php';
require_once 'includes/settings/navigation_buttons.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';
$selectedIntencao = isset($_GET['intencao']) ? $_GET['intencao'] : '0';

// Processar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete']) && isset($_POST['id'])) {
        if (deleteImage($pdo, $_POST['id'])) {
            $message = "Imagem excluída com sucesso!";
            $messageType = "success";
        } else {
            $message = "Erro ao excluir imagem.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['edit']) && isset($_POST['id'])) {
        if (editImage($pdo, $_POST['id'], $_POST['titulo'], $_POST['intencao'])) {
            $message = "Imagem atualizada com sucesso!";
            $messageType = "success";
        } else {
            $message = "Erro ao atualizar imagem.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['add']) && isset($_FILES['imagem'])) {
        $image_base64 = processImage($_FILES['imagem']);
        if ($image_base64) {
            if (addImage($pdo, $_POST['titulo'], $image_base64, $_SESSION['user_id'], $_POST['intencao'])) {
                $message = "Imagem adicionada com sucesso!";
                $messageType = "success";
            } else {
                $message = "Erro ao salvar imagem no banco de dados.";
                $messageType = "error";
            }
        } else {
            $message = "Erro ao processar imagem. Verifique o formato e tamanho.";
            $messageType = "error";
        }
    }
}

// Buscar imagens com filtro de intenção
$result = getImages($pdo, $selectedIntencao);

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    
    <div class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-white mb-8">Configuração de Imagens</h1>
            
            <!-- Navigation Buttons -->
            <div class="mb-8">
                <?php renderNavigationButtons('images'); ?>
            </div>
            
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filtro de intenções -->
            <?php renderIntencaoFilter($pdo, $selectedIntencao); ?>
            
            <!-- Lista de imagens -->
            <div class="bg-gray-800 p-6 rounded-lg mb-8">
                <?php renderTable($result); ?>
            </div>

            <!-- Modal de edição -->
            <div id="imageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-gray-800 p-6 rounded-lg w-full max-w-2xl">
                    <h2 class="text-xl font-bold text-white mb-4">Editar Imagem</h2>
                    <form method="POST" id="imageForm">
                        <input type="hidden" name="id" id="imageId">
                        <div class="mb-4">
                            <label for="imageTitulo" class="block text-white mb-2">Título</label>
                            <input 
                                type="text" 
                                name="titulo" 
                                id="imageTitulo"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                            >
                        </div>
                        <div class="mb-4">
                            <label for="imageIntencao" class="block text-white mb-2">Intenção</label>
                            <select 
                                name="intencao" 
                                id="imageIntencao"
                                class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                            >
                                <option value="0">Geral</option>
                                <?php 
                                $intencoes = getIntencoes($pdo);
                                foreach ($intencoes as $intencao): 
                                ?>
                                <option value="<?php echo $intencao['id']; ?>">
                                    <?php echo htmlspecialchars($intencao['titulo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button 
                                type="button"
                                onclick="closeImageModal()"
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
function editImage(id, titulo, intencao) {
    // Decodifica os caracteres HTML primeiro
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = titulo;
    const decodedTitulo = tempDiv.textContent;
    
    document.getElementById('imageId').value = id;
    document.getElementById('imageTitulo').value = decodedTitulo;
    document.getElementById('imageIntencao').value = intencao || 0;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

// Adiciona listener para fechar o modal quando clicar fora dele
document.addEventListener('click', function(event) {
    const modal = document.getElementById('imageModal');
    if (modal && event.target === modal) {
        closeImageModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
