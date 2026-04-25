
<?php
function renderTable($result) {
    if (!$result || count($result) === 0) {
        echo '<p class="text-gray-300">Nenhuma imagem encontrada.</p>';
        return;
    }
    ?>
    <div class="overflow-x-auto">
        <table class="min-w-full text-gray-300">
            <thead>
                <tr>
                    <th class="px-4 py-2">Título</th>
                    <th class="px-4 py-2">Miniatura</th>
                    <th class="px-4 py-2">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row): ?>
                <tr>
                    <td class="border px-4 py-2">
                        <?php echo htmlspecialchars($row['titulo']); ?>
                    </td>
                    <td class="border px-4 py-2">
                        <img src="data:image/jpeg;base64,<?php echo $row['image']; ?>" 
                             alt="<?php echo htmlspecialchars($row['titulo']); ?>"
                             class="max-w-[100px] max-h-[100px] object-contain">
                    </td>
                    <td class="border px-4 py-2">
                        <button 
                            onclick='editImage(<?php 
                                echo json_encode($row['id']) . ", " . 
                                json_encode($row['titulo']) . ", " .
                                json_encode($row['intencao'] ?? 0); 
                            ?>)'
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded mr-1">
                            Editar
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir? Esta ação não pode ser desfeita.');">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button 
                                type="submit" 
                                name="delete" 
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded">
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function renderIntencaoFilter($pdo, $selectedIntencao = null) {
    $intencoes = getIntencoes($pdo);
    ?>
    <div class="mb-6">
        <form method="GET" class="flex items-center space-x-2">
            <label for="intencao_filter" class="text-white">Filtrar por Intenção:</label>
            <select 
                name="intencao" 
                id="intencao_filter"
                class="p-2 rounded bg-gray-700 text-white border border-gray-600"
                onchange="this.form.submit()"
            >
                <option value="0" <?php echo ($selectedIntencao == '0' || $selectedIntencao === null) ? 'selected' : ''; ?>>Geral</option>
                <?php foreach ($intencoes as $intencao): ?>
                <option value="<?php echo $intencao['id']; ?>" <?php echo ($selectedIntencao == $intencao['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($intencao['titulo']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php
}
?>
