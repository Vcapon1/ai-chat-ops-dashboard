
<?php
function renderProfileForm($session) {
    ?>
    <div class="space-y-8">
        <!-- Dados do Usuário -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold text-blue-200 mb-4">Dados do Usuário</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2">Nome de Usuário</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($session['username'] ?? ''); ?>" 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Email de Contato</label>
                    <input type="email" name="email_p" value="<?php echo htmlspecialchars($session['email_p'] ?? ''); ?>" 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                </div>
                <button type="submit" name="update_credentials" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Atualizar Dados
                </button>
            </form>
        </div>

        <!-- Alterar Senha -->
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold text-blue-200 mb-4">Alterar Senha</h2>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2">Senha Atual</label>
                    <input type="password" name="current_password" 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Nova Senha</label>
                    <input type="password" name="new_password" 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                </div>
                <div>
                    <label class="block text-gray-300 mb-2">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                </div>
                <button type="submit" name="update_password" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    Atualizar Senha
                </button>
            </form>
        </div>
    </div>
    <?php
}
?>
