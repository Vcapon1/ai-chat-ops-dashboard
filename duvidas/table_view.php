
<?php
function renderTable($result) {
    if (!$result || count($result) === 0) {
        echo '<p class="text-gray-300">Nenhuma dúvida encontrada.</p>';
        return;
    }
    ?>
    <div class="overflow-x-auto">
        <table class="min-w-full text-gray-300">
            <thead>
                <tr>
                    <th class="px-4 py-2">Título</th>
                    <th class="px-4 py-2">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result as $row): ?>
                <tr>
                    <td class="border px-4 py-2">
                        <?php echo htmlspecialchars(substr($row['titulo'], 0, 50)) . (strlen($row['titulo']) > 50 ? '...' : ''); ?>
                    </td>
                    <td class="border px-4 py-2">
                        <div class="flex items-center space-x-2">
                            <button 
                                onclick='editPrompt(<?php 
                                    echo json_encode($row['id']) . ", " . 
                                    json_encode($row['titulo']) . ", " . 
                                    json_encode($row['prompt']) . ", " .
                                    json_encode($row['intencao'] ?? 0); 
                                ?>)'
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded">
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
                        </div>
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
