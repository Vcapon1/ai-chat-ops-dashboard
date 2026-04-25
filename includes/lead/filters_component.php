
<?php
/**
 * Lead filters component
 */
?>
<div class="bg-gray-800 rounded-lg shadow-xl p-3 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <label class="block text-blue-200 text-sm mb-1">Buscar por nome/telefone</label>
            <input type="text" name="search" 
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                   class="w-full px-3 py-1.5 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none text-sm">
        </div>
        <div>
            <label class="block text-blue-200 text-sm mb-1">Data Início</label>
            <input type="date" name="date_start" 
                   value="<?php echo isset($_GET['date_start']) ? htmlspecialchars($_GET['date_start']) : ''; ?>"
                   class="w-full px-3 py-1.5 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none text-sm">
        </div>
        <div>
            <label class="block text-blue-200 text-sm mb-1">Data Fim</label>
            <input type="date" name="date_end" 
                   value="<?php echo isset($_GET['date_end']) ? htmlspecialchars($_GET['date_end']) : ''; ?>"
                   class="w-full px-3 py-1.5 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none text-sm">
        </div>
        <div>
            <label class="block text-blue-200 text-sm mb-1">Lead Score</label>
            <select name="lead_score" class="w-full px-3 py-1.5 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none text-sm">
                <option value="">Todos</option>
                <?php for($i = 0; $i <= 100; $i += 10): ?>
                    <option value="<?php echo $i; ?>" <?php echo (isset($_GET['lead_score']) && $_GET['lead_score'] == $i) ? 'selected' : ''; ?>>
                        <?php echo $i; ?>%
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-blue-200 text-sm mb-1">Status</label>
            <select name="status" class="w-full px-3 py-1.5 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none text-sm">
                <option value="">Todos</option>
                <option value="interested" <?php echo (isset($_GET['status']) && $_GET['status'] == 'interested') ? 'selected' : ''; ?>>Interessados</option>
                <option value="not_interested" <?php echo (isset($_GET['status']) && $_GET['status'] == 'not_interested') ? 'selected' : ''; ?>>Não Interessados</option>
            </select>
        </div>
        <div class="md:col-span-5 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-sm">
                Filtrar
            </button>
        </div>
    </form>
</div>
