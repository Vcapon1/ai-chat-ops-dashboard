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
                
                <!-- Seleção de Templates Meta -->
                <div id="templates-meta-section" class="bg-gray-700 p-4 rounded-lg space-y-3">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-white">📱 Templates WhatsApp</h3>
                        <button type="button" id="carregar-templates-btn" 
                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Carregar Templates
                        </button>
                    </div>
                    
                    <div id="templates-loading" class="hidden text-center py-4">
                        <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-400 mt-2">Carregando templates...</p>
                    </div>
                    
                    <div id="templates-error" class="hidden bg-red-900/50 border border-red-700 rounded p-3 text-red-200 text-sm"></div>
                    
                    <div id="templates-container" class="space-y-2 max-h-96 overflow-y-auto">
                        <p class="text-gray-400 text-sm text-center py-4">Clique em "Carregar Templates" para ver os templates disponíveis</p>
                    </div>
                    
                    <input type="hidden" name="templates_selecionados" id="templates_selecionados" value="[]">
                    
                    <p class="text-blue-300 text-sm mt-3 font-medium">
                        🎯 Selecione múltiplos templates para fazer teste A/B! 
                        <span class="block text-gray-400 text-xs mt-1">Um template diferente será escolhido aleatoriamente para cada envio.</span>
                    </p>
                </div>
                
                <!-- Data de Início -->
                <div>
                    <label for="data_inicio" class="block text-sm font-medium text-gray-300 mb-2">Data de Início *</label>
                    <input type="date" name="data_inicio" id="data_inicio" required 
                           class="px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-auto"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <input type="hidden" name="mensagem" id="mensagem" value="">
                
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


// Templates Meta - Carregar e selecionar
let templatesDisponiveis = [];
let templatesSelecionados = [];

document.getElementById('carregar-templates-btn').addEventListener('click', function() {
    const loadingDiv = document.getElementById('templates-loading');
    const errorDiv = document.getElementById('templates-error');
    const containerDiv = document.getElementById('templates-container');
    const button = this;
    
    // Mostrar loading
    loadingDiv.classList.remove('hidden');
    errorDiv.classList.add('hidden');
    containerDiv.innerHTML = '';
    button.disabled = true;
    
    // Fazer requisição AJAX
    fetch('includes/campanhas/ajax_handlers.php?action=get_meta_templates')
        .then(response => response.json())
        .then(data => {
            loadingDiv.classList.add('hidden');
            button.disabled = false;
            
            if (data.success && data.templates && data.templates.length > 0) {
                templatesDisponiveis = data.templates;
                renderTemplates(data.templates);
            } else {
                const message = data.message || 'Nenhum template encontrado';
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
                containerDiv.innerHTML = `<p class="text-gray-400 text-sm text-center py-4">${message}</p>`;
            }
        })
        .catch(error => {
            loadingDiv.classList.add('hidden');
            button.disabled = false;
            errorDiv.textContent = 'Erro ao carregar templates: ' + error.message;
            errorDiv.classList.remove('hidden');
            console.error('Erro ao carregar templates:', error);
        });
});

function renderTemplates(templates) {
    const container = document.getElementById('templates-container');
    container.innerHTML = '';
    
    templates.forEach(template => {
        const div = document.createElement('div');
        div.className = 'border border-gray-600 rounded-lg p-3 hover:border-blue-500 transition-colors cursor-pointer template-card';
        div.dataset.templateId = template.id;
        div.dataset.templateName = template.name;
        div.dataset.templateBody = template.body_text || '';
        
        div.innerHTML = `
            <div class="flex items-start gap-3">
                <input type="checkbox" class="template-checkbox mt-1" data-template-id="${template.id}">
                <div class="flex-1">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-semibold text-white">${template.name}</h4>
                        <span class="text-xs px-2 py-1 rounded ${template.category === 'MARKETING' ? 'bg-blue-900 text-blue-200' : 'bg-green-900 text-green-200'}">
                            ${template.category}
                        </span>
                    </div>
                    <div class="bg-gray-800 rounded p-2 mb-2">
                        <p class="text-sm text-gray-300 whitespace-pre-wrap">${template.body_text || 'Sem prévia disponível'}</p>
                    </div>
                    <p class="text-xs text-gray-500">Idioma: ${template.language}</p>
                </div>
            </div>
        `;
        
        // Click no card seleciona/deseleciona
        div.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.template-checkbox');
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
        
        // Checkbox change
        const checkbox = div.querySelector('.template-checkbox');
        checkbox.addEventListener('change', function(e) {
            e.stopPropagation();
            const card = this.closest('.template-card');
            
            if (this.checked) {
                card.classList.add('border-blue-500', 'bg-blue-900/20');
                templatesSelecionados.push({
                    id: template.id,
                    name: template.name,
                    body_text: template.body_text,
                    language: template.language,
                    category: template.category
                });
            } else {
                card.classList.remove('border-blue-500', 'bg-blue-900/20');
                templatesSelecionados = templatesSelecionados.filter(t => t.id !== template.id);
            }
            
            atualizarTemplatesSelecionados();
        });
        
        container.appendChild(div);
    });
}

function atualizarTemplatesSelecionados() {
    document.getElementById('templates_selecionados').value = JSON.stringify(templatesSelecionados);
    
    // Atualizar contador visual
    const count = templatesSelecionados.length;
    const btnText = count > 0 
        ? `${count} template${count > 1 ? 's' : ''} selecionado${count > 1 ? 's' : ''}`
        : 'Nenhum template selecionado';
    
    // Criar/atualizar badge de contagem
    let badge = document.getElementById('template-count-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.id = 'template-count-badge';
        badge.className = 'ml-2 px-2 py-1 bg-blue-600 text-white text-xs rounded-full';
        document.querySelector('#templates-meta-section h3').appendChild(badge);
    }
    
    if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// Update cost breakdown
document.getElementById('id_lista').addEventListener('change', updateCostBreakdown);

function updateCostBreakdown() {
    const listaSelect = document.getElementById('id_lista');
    const costBreakdown = document.getElementById('cost-breakdown');
    
    if (!listaSelect.value) {
        costBreakdown.innerHTML = '<div>Selecione uma lista para ver os custos estimados</div>';
        return;
    }
    
    const selectedOption = listaSelect.options[listaSelect.selectedIndex];
    const totalContatos = parseInt(selectedOption.text.match(/\((\d+) contatos\)/)[1]);
    
    const custoTotal = totalContatos; // 1 crédito por mensagem
    
    let html = `
        <div class="flex justify-between">
            <span>Mensagens via template (${totalContatos} contatos):</span>
            <span class="font-semibold">${custoTotal} créditos</span>
        </div>
        <div class="border-t border-blue-600 pt-2 mt-2 flex justify-between">
            <span class="font-bold">Total estimado:</span>
            <span class="font-bold text-lg">${custoTotal} créditos</span>
        </div>
    `;
    
    costBreakdown.innerHTML = html;
}
</script>
