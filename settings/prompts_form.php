
<?php
function renderPromptsForm($session) {
    ?>
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-semibold text-blue-200 mb-4">Configuração do Assistente</h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-300 mb-2">Instrução Principal do Produto</label>
                <textarea name="prompt" rows="4" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white"
                          placeholder="Escreva aqui todas as informações sobre o produto, que serão consultadas em todas as respostas"><?php echo htmlspecialchars($session['prompt'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-400 mt-1">Coloque aqui o texto falando do produto como um todo que será consultado em todas as respostas</p>
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Nome do Assistente</label>
                <input type="text" name="assist_name" value="<?php echo htmlspecialchars($session['assist_name'] ?? ''); ?>" 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white"
                       placeholder="Ex: Maria, João, Assistente Virtual"
                       maxlength="100">
                <p class="text-sm text-gray-400 mt-1">Escreva aqui o nome que o assistente deve assumir</p>
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Telefone do Administrador</label>
                <input type="text" name="n_adm" value="<?php echo htmlspecialchars($session['n_adm'] ?? ''); ?>" 
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white"
                       placeholder="Ex: 5511999999999" 
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <p class="text-sm text-gray-400 mt-1">Formato: código do país + DDD + número (apenas números)</p>
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Saudação</label>
                <textarea name="prompt_saudacao" rows="4" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white"
                          placeholder="Ex: Olá, eu sou o [nome do assistente] e estou aqui para ajudar..."><?php echo htmlspecialchars($session['prompt_saudacao'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-400 mt-1">Como o assistente deve atender e se apresentar ao cliente na primeira mensagem</p>
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Regra para Criação do Lead no CRM</label>
                <textarea name="prompt_lead" rows="4" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white"
                          placeholder="Ex: Critérios para identificar potenciais leads..."><?php echo htmlspecialchars($session['prompt_lead'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-400 mt-1">Define as regras ou critérios para identificar e criar um lead no CRM</p>
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Regra para Inativação</label>
                <textarea name="prompt_descarte" rows="4" 
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white"
                          placeholder="Ex: Critérios para inativar um lead..."><?php echo htmlspecialchars($session['prompt_descarte'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-400 mt-1">Define as regras ou critérios para determinar quando um lead deve ser inativado</p>
            </div>
            
            <button type="submit" name="update_settings" 
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                Atualizar Configurações
            </button>
        </form>
    </div>
    <?php
}
?>
