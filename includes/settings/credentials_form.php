<?php
function renderCredentialsForm($session) {
    ?>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold text-blue-200 mb-4">Alterar Credenciais</h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-300 mb-2">Usuário</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($session['username'] ?? ''); ?>" 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
            </div>
            <div>
                <label class="block text-gray-300 mb-2">WhatsApp de Cobrança</label>
                <input type="text" name="n_cobranca" value="<?php echo htmlspecialchars($session['n_cobranca'] ?? ''); ?>" 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
            </div>
            <div>
                <label class="block text-gray-300 mb-2">WhatsApp Administrativo</label>
                <input type="text" name="n_adm" value="<?php echo htmlspecialchars($session['n_adm'] ?? ''); ?>" 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
            </div>
            <div>
                <label class="block text-gray-300 mb-2">Email de Contato</label>
                <input type="email" name="email_p" value="<?php echo htmlspecialchars($session['email_p'] ?? ''); ?>" 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
            </div>
            <button type="submit" name="update_credentials" 
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                Atualizar Credenciais
            </button>
        </form>
    </div>
    <?php
}
?>