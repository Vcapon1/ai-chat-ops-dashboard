
<?php
/**
 * Table view component for intentions
 */
function renderTable($result) {
    if ($result && $result->rowCount() > 0) {
        ?>
        <table class="min-w-full divide-y divide-gray-700">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Descrição</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Código Produto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-gray-800 divide-y divide-gray-700">
                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td class="px-6 py-4 text-sm text-gray-300 max-w-xs">
                        <?php echo htmlspecialchars($row['titulo']); ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-300">
                        <?php 
                        $descricao = $row['descricao'];
                        echo htmlspecialchars(substr($descricao, 0, 100) . (strlen($descricao) > 100 ? '...' : '')); 
                        ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                        <?php echo htmlspecialchars($row['codigo_produto'] ?? '-'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button 
                            onclick="editPrompt(<?php echo intval($row['id']); ?>, <?php echo htmlspecialchars(json_encode($row['titulo']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($row['descricao']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($row['codigo_produto'] ?? ''), ENT_QUOTES); ?>)"
                            class="inline-flex bg-blue-500 hover:bg-blue-700 text-white text-xs py-1 px-2 rounded"
                        >
                            Editar
                        </button>
                        
                        <form method="POST" class="inline-block">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button 
                                type="submit" 
                                name="delete" 
                                onclick="return confirm('Tem certeza que deseja excluir esta intenção?');"
                                class="bg-red-500 hover:bg-red-700 text-white text-xs py-1 px-2 rounded"
                            >
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p class="text-gray-300">Nenhuma intenção cadastrada.</p>';
    }
}
?>
