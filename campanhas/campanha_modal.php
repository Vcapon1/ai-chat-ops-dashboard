<!-- Modal de Nova Campanha Atualizado v2 -->
<div id="modal-campanha" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-gray-800">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modal-title" class="text-lg font-medium text-white">Nova Campanha de Reativação</h3>
                <button onclick="closeModal('modal-campanha')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="form-campanha" class="space-y-4">
                <div>
                    <label for="nome_campanha" class="block text-sm font-medium text-gray-300 mb-2">Nome da Campanha</label>
                    <input type="text" name="nome_campanha" id="nome_campanha" required 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ex: Reativação Janeiro 2024">
                </div>
                
                <div>
                    <label for="id_lista" class="block text-sm font-medium text-gray-300 mb-2">Lista de Contatos</label>
                    <select name="id_lista" id="id_lista" required 
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Selecione uma lista</option>
                        <?php foreach ($listas as $lista): ?>
                            <?php if (!$lista['usada_recentemente']): ?>
                                <option value="<?php echo $lista['id']; ?>">
                                    <?php echo htmlspecialchars($lista['nome']); ?> (<?php echo $lista['total_contatos']; ?> contatos)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-400">Apenas listas não utilizadas nos últimos 30 dias</p>
                </div>
                
                <!-- Configurações de Envio -->
                <div class="bg-gray-700 p-4 rounded-lg space-y-4">
                    <h3 class="text-lg font-semibold text-white mb-3">📅 Janela de Envio</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="hora_inicio" class="block text-sm font-medium text-gray-300 mb-2">Hora Inicial</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" value="09:00" required 
                                   class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label for="hora_fim" class="block text-sm font-medium text-gray-300 mb-2">Hora Final</label>
                            <input type="time" name="hora_fim" id="hora_fim" value="18:00" required 
                                   class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-3">Dias da Semana para Envio</label>
                        <div class="grid grid-cols-4 gap-2">
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="0" class="mr-2 text-blue-500">
                                <span class="text-white text-sm">Domingo</span>
                            </label>
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="1" class="mr-2 text-blue-500" checked>
                                <span class="text-white text-sm">Segunda</span>
                            </label>
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="2" class="mr-2 text-blue-500" checked>
                                <span class="text-white text-sm">Terça</span>
                            </label>
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="3" class="mr-2 text-blue-500" checked>
                                <span class="text-white text-sm">Quarta</span>
                            </label>
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="4" class="mr-2 text-blue-500" checked>
                                <span class="text-white text-sm">Quinta</span>
                            </label>
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="5" class="mr-2 text-blue-500" checked>
                                <span class="text-white text-sm">Sexta</span>
                            </label>
                            <label class="flex items-center p-2 bg-gray-600 rounded-md hover:bg-gray-500 cursor-pointer">
                                <input type="checkbox" name="dias_semana[]" value="6" class="mr-2 text-blue-500">
                                <span class="text-white text-sm">Sábado</span>
                            </label>
                        </div>
                        <p class="mt-2 text-sm text-gray-400">Selecione os dias em que as mensagens podem ser enviadas</p>
                    </div>
                </div>
                
                <!-- Opção de Mensagem Personalizada -->
                <div class="bg-gray-700 p-4 rounded-lg">
                    <div class="flex items-center mb-3">
                        <input type="checkbox" id="usar_ia" name="usar_ia" value="true"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded bg-gray-600">
                        <label for="usar_ia" class="ml-2 block text-sm font-medium text-white">
                            🤖 Usar Mensagem Personalizada por IA
                            <span class="text-gray-400 text-xs block">
                                💰 <strong><span id="custo-ia-individual">2</span> créditos extras por mensagem</strong> - Cria mensagens únicas para cada cliente
                            </span>
                        </label>
                    </div>
                    
                    <div id="ia-options" class="hidden space-y-3">
                        <div>
                            <label for="contexto_empresa" class="block text-sm font-medium text-gray-300 mb-2">Contexto da Empresa</label>
                            <input type="text" name="contexto_empresa" id="contexto_empresa" 
                                   class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-md text-white placeholder-gray-400"
                                   placeholder="Ex: Consultoria em marketing digital, vendas de cursos online"
                                   data-conditional-required="true">
                        </div>
                        
                        <div>
                            <label for="tom_voz" class="block text-sm font-medium text-gray-300 mb-2">Tom de Voz</label>
                            <select name="tom_voz" id="tom_voz" 
                                    class="w-full px-3 py-2 bg-gray-600 border border-gray-500 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    data-conditional-required="true">
                                <option value="">Selecione o tom de voz</option>
                                <option value="descontraido">Descontraído</option>
                                <option value="profissional">Profissional</option>
                                <option value="entusiasmado">Entusiasmado</option>
                                <option value="conversacional">Conversacional</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Data de Início -->
                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-gray-300 mb-2">Data de Início *</label>
                    <input type="date" name="data_inicio" id="data_inicio" required 
                           class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-auto"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div>
                    <label for="mensagem" class="block text-sm font-medium text-gray-300 mb-2">
                        <span id="label-mensagem">Mensagem</span>
                        <span id="label-prompt" class="hidden">Prompt Base para IA</span>
                    </label>
                    <div class="relative">
                        <textarea name="mensagem" id="mensagem" rows="4" required 
                                  class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="Digite sua mensagem de reativação..."></textarea>
                        <button type="button" id="gerar-ia-button" 
                                class="absolute bottom-2 right-2 px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded-md flex items-center gap-1 transition-colors">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            IA Reescrever
                        </button>
                    </div>
                    <p id="help-text" class="mt-1 text-sm text-gray-400">
                        Esta mensagem será enviada para todos os contatos da lista
                    </p>
                    <p id="help-prompt" class="mt-1 text-sm text-gray-400 hidden">
                        Este prompt será usado pela IA para gerar mensagens personalizadas para cada cliente
                    </p>
                </div>
                
                <!-- Resumo de Custos -->
                <div class="bg-blue-900 bg-opacity-50 border border-blue-700 rounded-md p-3">
                    <h4 class="text-sm font-medium text-blue-300 mb-2">💰 Estimativa de Custos</h4>
                    <div id="cost-breakdown" class="text-xs text-blue-200 space-y-1">
                        <div>Selecione uma lista para ver os custos estimados</div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal('modal-campanha')" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <span id="submit-text" class="loading-text">Criar Campanha</span>
                        <span class="loading-spinner hidden">
                            <svg class="animate-spin h-4 w-4 text-white inline-block mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Criando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle IA options
document.getElementById('usar_ia').addEventListener('change', function() {
    const iaOptions = document.getElementById('ia-options');
    const labelMensagem = document.getElementById('label-mensagem');
    const labelPrompt = document.getElementById('label-prompt');
    const helpText = document.getElementById('help-text');
    const helpPrompt = document.getElementById('help-prompt');
    const mensagemField = document.getElementById('mensagem');
    
    // Campos condicionalmente obrigatórios
    const contextoEmpresa = document.getElementById('contexto_empresa');
    const tomVoz = document.getElementById('tom_voz');
    
    if (this.checked) {
        iaOptions.classList.remove('hidden');
        labelMensagem.classList.add('hidden');
        labelPrompt.classList.remove('hidden');
        helpText.classList.add('hidden');
        helpPrompt.classList.remove('hidden');
        mensagemField.placeholder = 'Ex: Crie uma mensagem calorosa oferecendo desconto especial...';
        
        // Tornar campos obrigatórios
        contextoEmpresa.required = true;
        tomVoz.required = true;
    } else {
        iaOptions.classList.add('hidden');
        labelMensagem.classList.remove('hidden');
        labelPrompt.classList.add('hidden');
        helpText.classList.remove('hidden');
        helpPrompt.classList.add('hidden');
        mensagemField.placeholder = 'Digite sua mensagem de reativação...';
        
        // Remover obrigatoriedade
        contextoEmpresa.required = false;
        tomVoz.required = false;
    }
    
    updateCostBreakdown();
});

// Validação de horários
document.getElementById('hora_inicio').addEventListener('change', validateTimeRange);
document.getElementById('hora_fim').addEventListener('change', validateTimeRange);

function validateTimeRange() {
    const horaInicio = document.getElementById('hora_inicio').value;
    const horaFim = document.getElementById('hora_fim').value;
    
    if (horaInicio && horaFim && horaInicio >= horaFim) {
        document.getElementById('hora_fim').setCustomValidity('Hora final deve ser maior que hora inicial');
    } else {
        document.getElementById('hora_fim').setCustomValidity('');
    }
}

// Validação de dias da semana
function validateDiasSemana() {
    const checkboxes = document.querySelectorAll('input[name="dias_semana[]"]:checked');
    const errorDiv = document.getElementById('dias-error');
    
    if (checkboxes.length === 0) {
        if (!errorDiv) {
            const error = document.createElement('div');
            error.id = 'dias-error';
            error.className = 'text-red-400 text-sm mt-1';
            error.textContent = 'Selecione pelo menos um dia da semana';
            document.querySelector('input[name="dias_semana[]"]').closest('div').appendChild(error);
        }
        return false;
    } else {
        if (errorDiv) {
            errorDiv.remove();
        }
        return true;
    }
}

// Botão de IA para reescrever texto
document.getElementById('gerar-ia-button').addEventListener('click', function() {
    const mensagemField = document.getElementById('mensagem');
    const button = this;
    
    if (!mensagemField.value.trim()) {
        alert('Digite uma mensagem primeiro para que a IA possa reescrevê-la.');
        return;
    }
    
    // Simular loading
    const originalText = button.innerHTML;
    button.innerHTML = '<svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Gerando...';
    button.disabled = true;
    
    // TODO: Implementar chamada real para API de IA
    setTimeout(() => {
        // Exemplo de reescrita melhorada
        const textoOriginal = mensagemField.value;
        const textoMelhorado = `Olá! 🌟 Esperamos que você esteja bem. Notamos sua ausência e gostaríamos de reconectá-lo conosco. Temos novidades especiais e ofertas exclusivas esperando por você. Que tal retomar nossa parceria? Estamos aqui para ajudar! 💬`;
        
        mensagemField.value = textoMelhorado;
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Feedback visual
        mensagemField.style.borderColor = '#10B981';
        setTimeout(() => {
            mensagemField.style.borderColor = '';
        }, 2000);
    }, 2000);
});

// Update cost breakdown
document.getElementById('id_lista').addEventListener('change', updateCostBreakdown);

function updateCostBreakdown() {
    const listaSelect = document.getElementById('id_lista');
    const usarIA = document.getElementById('usar_ia').checked;
    const costBreakdown = document.getElementById('cost-breakdown');
    
    if (!listaSelect.value) {
        costBreakdown.innerHTML = '<div>Selecione uma lista para ver os custos estimados</div>';
        return;
    }
    
    const selectedOption = listaSelect.options[listaSelect.selectedIndex];
    const totalContatos = parseInt(selectedOption.text.match(/\((\d+) contatos\)/)[1]);
    
    const custoEnvio = totalContatos * 3;
    const custoIA = usarIA ? totalContatos * 2 : 0;
    const custoTotal = custoEnvio + custoIA;
    
    let html = `
        <div>• Envio de mensagens: ${totalContatos} × 3 = <strong>${custoEnvio} créditos</strong></div>
    `;
    
    if (usarIA) {
        html += `<div>• Personalização IA: ${totalContatos} × 2 = <strong>${custoIA} créditos</strong></div>`;
    }
    
    html += `<div class="border-t border-blue-600 pt-1 mt-1">Total estimado: <strong>${custoTotal} créditos</strong></div>`;
    
    costBreakdown.innerHTML = html;
}
</script>
