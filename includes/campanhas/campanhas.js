// Gerenciamento das seções
function showSection(sectionName) {
    // Ocultar todas as seções
    document.querySelectorAll('.section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Remover classe ativa de todos os botões
    document.querySelectorAll('[id^="btn-"]').forEach(btn => {
        btn.classList.remove('bg-blue-600');
        btn.classList.add('bg-gray-700', 'text-gray-300');
    });
    
    // Mostrar seção selecionada
    document.getElementById(`section-${sectionName}`).classList.remove('hidden');
    
    // Ativar botão selecionado
    const activeBtn = document.getElementById(`btn-${sectionName}`);
    activeBtn.classList.remove('bg-gray-700', 'text-gray-300');
    activeBtn.classList.add('bg-blue-600');
}

// Gerenciamento de modais
function openModal(modalId, dadosCampanha = null) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error(`Modal com ID '${modalId}' não encontrado`);
        return;
    }
    modal.classList.remove('hidden');
    
    // Se for modal de campanha, carregar listas
    if (modalId === 'modal-campanha') {
        carregarListasParaCampanha();
        
        // Se não há dados pré-preenchidos, usar padrões
        if (!dadosCampanha) {
            // Resetar formulário
            document.getElementById('form-campanha').reset();
            document.getElementById('modal-title').textContent = 'Nova Campanha de Reativação';
            document.getElementById('submit-text').textContent = 'Criar Campanha';
            
            // Marcar dias úteis (Segunda a Sexta) como padrão
            const diasUteis = [1, 2, 3, 4, 5]; // Segunda a Sexta
            diasUteis.forEach(dia => {
                const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            
            // Definir horários padrão
            document.getElementById('hora_inicio').value = '09:00';
            document.getElementById('hora_fim').value = '18:00';
            
            // Definir data mínima
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('data_inicio').min = today;
            document.getElementById('data_inicio').value = today;
        } else {
            // Preencher dados da campanha duplicada
            preencherDadosCampanha(dadosCampanha);
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        console.error(`Modal com ID '${modalId}' não encontrado`);
        return;
    }
    modal.classList.add('hidden');
    
    // Limpar formulários ao fechar
    if (modalId === 'modal-import') {
        document.getElementById('form-import').reset();
        document.getElementById('csv-preview').classList.add('hidden');
    }
    
    if (modalId === 'modal-campanha') {
        document.getElementById('form-campanha').reset();
        document.getElementById('modal-title').textContent = 'Nova Campanha de Reativação';
        document.getElementById('submit-text').textContent = 'Criar Campanha';
    }
}

// Carregar listas para o dropdown de campanha
function carregarListasParaCampanha() {
    fetch('includes/campanhas/ajax_handlers.php?action=get_listas')
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector('select[name="id_lista"]');
            select.innerHTML = '<option value="">Selecione uma lista...</option>';
            
            data.listas.forEach(lista => {
                const option = document.createElement('option');
                option.value = lista.id;
                
                // Verificar se foi usada recentemente
                if (lista.usada_recentemente == 1) {
                    option.textContent = `${lista.nome} (${lista.total_contatos} contatos) - BLOQUEADA - Usada em: ${new Date(lista.data_ultimo_uso).toLocaleDateString()}`;
                    option.disabled = true;
                    option.style.color = '#ef4444';
                } else {
                    option.textContent = `${lista.nome} (${lista.total_contatos} contatos)`;
                }
                
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Erro ao carregar listas:', error);
            showNotification('Erro ao carregar listas', 'error');
        });
}

// Função showNotification se não existir
if (typeof showNotification !== 'function') {
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white px-4 py-2 rounded shadow-lg z-50`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

function previewCSV(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split('\n').filter(line => line.trim());
        
        if (lines.length < 2) {
            showNotification('Arquivo CSV deve ter pelo menos 2 linhas (cabeçalho + dados)', 'error');
            return;
        }
        
        const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
        const sampleData = lines.slice(1, 4).map(line => 
            line.split(',').map(cell => cell.trim().replace(/"/g, ''))
        );
        
        renderColumnMapping(headers, sampleData);
    };
    reader.readAsText(file);
}

function renderColumnMapping(headers, sampleData) {
    const mappingDiv = document.getElementById('column-mapping');
    const previewDiv = document.getElementById('csv-preview');
    
    mappingDiv.innerHTML = '';
    
    // Opções para mapeamento
    const fieldOptions = [
        { value: '', text: 'Não usar esta coluna' },
        { value: 'nome', text: 'Nome' },
        { value: 'telefone', text: 'Telefone' },
        { value: 'interesse', text: 'Interesse' }
    ];
    
    headers.forEach((header, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center space-x-4 p-4 bg-gray-700 rounded-lg';
        
        // Coluna original
        const columnDiv = document.createElement('div');
        columnDiv.className = 'flex-1';
        columnDiv.innerHTML = `
            <div class="font-medium text-white">${header}</div>
            <div class="text-sm text-gray-400">
                Exemplos: ${sampleData.map(row => row[index] || '').slice(0, 2).join(', ')}
            </div>
        `;
        
        // Select para mapeamento
        const select = document.createElement('select');
        select.name = `mapping_${index}`;
        select.className = 'px-3 py-2 bg-gray-600 border border-gray-500 rounded text-white';
        
        fieldOptions.forEach(option => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.text;
            
            // Auto-detectar campos comuns
            if (header.toLowerCase().includes('nome') && option.value === 'nome') {
                opt.selected = true;
            } else if (header.toLowerCase().includes('telefone') && option.value === 'telefone') {
                opt.selected = true;
            } else if ((header.toLowerCase().includes('interesse') || header.toLowerCase().includes('interesse')) && option.value === 'interesse') {
                opt.selected = true;
            }
            
            select.appendChild(opt);
        });
        
        div.appendChild(columnDiv);
        div.appendChild(select);
        mappingDiv.appendChild(div);
    });
    
    previewDiv.classList.remove('hidden');
}

// Submit do formulário de importação - versão única e simplificada

document.addEventListener('DOMContentLoaded', function() {
    const formImport = document.getElementById('form-import');
    if (formImport && !formImport.hasImportListener) {
        formImport.hasImportListener = true;
        
        // Listener para preview do CSV
        formImport.addEventListener('change', function(e) {
            if (e.target.name === 'arquivo_csv') {
                const file = e.target.files[0];
                if (file) {
                    previewCSV(file);
                }
            }
        });
        
        // Listener para submit
        formImport.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevenir múltiplas submissões
            if (window.formImportSubmitting) return;
            window.formImportSubmitting = true;
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Importando...';
            
            const formData = new FormData(this);
            formData.append('action', 'import_csv');
            
            // Adicionar mapeamento
            const mappingInputs = document.querySelectorAll('[name^="mapping_"]');
            const mapping = {};
            mappingInputs.forEach(input => {
                const index = input.name.split('_')[1];
                if (input.value) {
                    mapping[index] = input.value;
                }
            });
            formData.append('mapping', JSON.stringify(mapping));
            
            fetch('includes/campanhas/ajax_handlers.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('modal-import');
                    
                    // Abrir relatório se disponível
                    if (data.report_id) {
                        window.open(`import_report.php?id=${data.report_id}`, '_blank', 'width=1000,height=700,scrollbars=yes');
                    }
                    
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao importar lista', 'error');
            })
            .finally(() => {
                window.formImportSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Importar Lista';
            });
        });
    }
    
    // Carregar script de detalhes se não estiver carregado
    if (!window.verCampanha) {
        const script = document.createElement('script');
        script.src = 'includes/campanhas/detalhes_campanha.js';
        document.head.appendChild(script);
    }
});

// Submit do formulário de campanha
document.getElementById('form-campanha').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar campos obrigatórios básicos
    const nomeCampanha = document.getElementById('nome_campanha').value.trim();
    const idLista = document.getElementById('id_lista').value;
    const templatesSelecionados = document.getElementById('templates_selecionados').value;
    
    if (!nomeCampanha) {
        showNotification('Nome da campanha é obrigatório', 'error');
        return;
    }
    
    if (!idLista) {
        showNotification('Selecione uma lista de contatos', 'error');
        return;
    }
    
    // Validar se templates foram selecionados
    const templates = JSON.parse(templatesSelecionados || '[]');
    if (templates.length === 0) {
        showNotification('Selecione pelo menos um template WhatsApp', 'error');
        return;
    }
    
    // Validar dias da semana
    if (!validateDiasSemana()) {
        showNotification('Selecione pelo menos um dia da semana para envio', 'error');
        return;
    }
    
    // Validar horários
    const horaInicio = document.getElementById('hora_inicio').value;
    const horaFim = document.getElementById('hora_fim').value;
    
    if (horaInicio >= horaFim) {
        showNotification('Hora final deve ser maior que hora inicial', 'error');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'create_campanha');
    
    // Adicionar dados de agendamento
    const diasSelecionados = Array.from(document.querySelectorAll('input[name="dias_semana[]"]:checked'))
        .map(checkbox => checkbox.value);
    formData.append('dias_semana', JSON.stringify(diasSelecionados));
    
    fetch('includes/campanhas/ajax_handlers.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            closeModal('modal-campanha');
            showSection('campanhas');
            location.reload();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao criar campanha', 'error');
    });
});

// Função de validação para dias da semana
function validateDiasSemana() {
    const checkboxes = document.querySelectorAll('input[name="dias_semana[]"]:checked');
    const errorDiv = document.getElementById('dias-error');
    
    if (checkboxes.length === 0) {
        if (!errorDiv) {
            const error = document.createElement('div');
            error.id = 'dias-error';
            error.className = 'text-red-400 text-sm mt-1';
            error.textContent = 'Selecione pelo menos um dia da semana';
            document.querySelector('label:has(input[name="dias_semana[]"])').parentNode.appendChild(error);
        }
        return false;
    } else {
        if (errorDiv) {
            errorDiv.remove();
        }
        return true;
    }
}

// Funções utilitárias
function excluirLista(id) {
    if (confirm('Tem certeza que deseja excluir esta lista? Esta ação não pode ser desfeita.')) {
        fetch('includes/campanhas/ajax_handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_lista&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao excluir lista', 'error');
        });
    }
}

function excluirCampanha(id) {
    if (confirm('Tem certeza que deseja excluir esta campanha?')) {
        fetch('includes/campanhas/ajax_handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_campanha&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao excluir campanha', 'error');
        });
    }
}

// Esta função agora é implementada no contatos_modal.php

// Esta função agora é implementada no contatos_modal.php

// Esta função agora é implementada no contatos_modal.php

// Validação de WhatsApp (removida - não é mais necessária)

// Função para iniciar validação de lista
function validarLista(idLista) {
    if (confirm('Tem certeza que deseja validar esta lista? Isso consumirá créditos.')) {
        fetch('includes/campanhas/ajax_handlers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=validar_lista&id=${idLista}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao iniciar validação', 'error');
        });
    }
}

// Função para duplicar campanha
function duplicarCampanha(campanhaId) {
    fetch(`includes/campanhas/ajax_handlers.php?action=get_campanha_detalhes&id=${campanhaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const campanha = data.campanha;
                
                // Adicionar (cópia) ao nome
                campanha.nome_campanha = campanha.nome_campanha + ' (cópia)';
                
                // Limpar ID para criar nova campanha
                delete campanha.id;
                
                // Abrir modal com dados pré-preenchidos
                openModal('modal-campanha', campanha);
            } else {
                showNotification('Erro ao carregar dados da campanha', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao duplicar campanha', 'error');
        });
}

// Função para preencher dados no modal de campanha
function preencherDadosCampanha(dadosCampanha) {
    // Atualizar título do modal
    document.getElementById('modal-title').textContent = 'Duplicar Campanha';
    document.getElementById('submit-text').textContent = 'Criar Cópia';
    
    // Aguardar carregamento das listas antes de preencher
    setTimeout(() => {
        // Preencher campos básicos
        document.getElementById('nome_campanha').value = dadosCampanha.nome_campanha || '';
        document.getElementById('mensagem').value = dadosCampanha.mensagem || '';
        
        // Preencher lista se disponível
        if (dadosCampanha.id_lista) {
            document.getElementById('id_lista').value = dadosCampanha.id_lista;
        }
        
        // Preencher horários
        if (dadosCampanha.hora_inicio) {
            document.getElementById('hora_inicio').value = dadosCampanha.hora_inicio.substring(0, 5);
        }
        if (dadosCampanha.hora_fim) {
            document.getElementById('hora_fim').value = dadosCampanha.hora_fim.substring(0, 5);
        }
        
        // Preencher dias da semana
        if (dadosCampanha.dias_semana) {
            try {
                const diasSelecionados = JSON.parse(dadosCampanha.dias_semana);
                // Limpar todos os checkboxes primeiro
                document.querySelectorAll('input[name="dias_semana[]"]').forEach(cb => cb.checked = false);
                
                // Marcar os dias selecionados
                diasSelecionados.forEach(dia => {
                    const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            } catch (e) {
                console.log('Erro ao processar dias da semana:', e);
            }
        }
        
        // Preencher configurações de IA
        if (dadosCampanha.usar_ia) {
            document.getElementById('usar_ia').checked = dadosCampanha.usar_ia == 1;
            
            // Trigger do evento change para mostrar opções de IA
            document.getElementById('usar_ia').dispatchEvent(new Event('change'));
            
            // Preencher campos de IA
            if (dadosCampanha.contexto_empresa) {
                document.getElementById('contexto_empresa').value = dadosCampanha.contexto_empresa;
            }
            if (dadosCampanha.tom_voz) {
                document.getElementById('tom_voz').value = dadosCampanha.tom_voz;
            }
        }
        
        // Definir data para hoje (nova campanha)
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_inicio').min = today;
        document.getElementById('data_inicio').value = today;
        
    }, 500); // Delay para aguardar carregamento das listas
}

// Definir data mínima como hoje para agendamento
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.querySelector('input[name="data_inicio"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
        dateInput.value = today;
    }
});
