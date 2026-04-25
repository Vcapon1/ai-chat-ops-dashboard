
<?php
function renderAddForm() {
    ?>
    <div class="bg-gray-800 p-6 rounded-lg">
        <h2 class="text-xl font-bold text-white mb-4">Adicionar Nova Intenção</h2>
        <form method="POST">
            <div class="mb-4">
                <label for="titulo" class="block text-white mb-2">Título</label>
                <input 
                    type="text" 
                    name="titulo" 
                    id="titulo"
                    required
                    class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                >
            </div>
            <div class="mb-4">
                <label for="descricao" class="block text-white mb-2">Descrição</label>
                <textarea 
                    name="descricao" 
                    id="descricao"
                    rows="10"
                    required
                    class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                ></textarea>
            </div>
            <div class="mb-4">
                <label for="codigo_produto" class="block text-white mb-2">Código do Produto (opcional)</label>
                <input 
                    type="text" 
                    name="codigo_produto" 
                    id="codigo_produto"
                    class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                    placeholder="Ex: PROD001"
                >
            </div>
            <div class="flex justify-end">
                <button 
                    type="submit"
                    name="add"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                    Adicionar
                </button>
            </div>
        </form>
    </div>
    <?php
}
?>
