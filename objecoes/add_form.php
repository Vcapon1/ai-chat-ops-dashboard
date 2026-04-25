
<?php
function renderAddForm($pdo, $selectedIntencao = null) {
    $intencoes = getIntencoes($pdo);
    ?>
    <div class="bg-gray-800 p-6 rounded-lg">
        <h2 class="text-xl font-bold text-white mb-4">Adicionar Nova Objeção</h2>
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
                <label for="intencao" class="block text-white mb-2">Intenção</label>
                <select 
                    name="intencao" 
                    id="intencao"
                    class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                >
                    <option value="0">Geral</option>
                    <?php foreach ($intencoes as $intencao): ?>
                    <option value="<?php echo $intencao['id']; ?>" <?php echo ($selectedIntencao == $intencao['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($intencao['titulo']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="prompt" class="block text-white mb-2">Prompt</label>
                <textarea 
                    name="prompt" 
                    id="prompt"
                    rows="10"
                    required
                    class="w-full p-2 rounded bg-gray-700 text-white border border-gray-600"
                ></textarea>
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
