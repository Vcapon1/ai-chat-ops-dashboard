<?php
// Modal para visualizar e editar contatos de uma lista
?>

<!-- Modal para Visualizar Contatos -->
<div id="modal-contatos" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg max-w-6xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header do Modal -->
            <div class="flex justify-between items-center p-6 border-b border-gray-700">
                <h3 class="text-xl font-semibold text-white" id="modal-contatos-title">Contatos da Lista</h3>
                <button onclick="closeModal('modal-contatos')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Filtros e Busca -->
            <div class="p-6 border-b border-gray-700">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input type="text" id="search-contatos" placeholder="Buscar por nome ou telefone..." 
                               class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="flex gap-2">
                        <button onclick="filtrarContatos('todos')" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 active:bg-blue-600" id="filter-todos">
                            Todos
                        </button>
                        <button onclick="filtrarContatos('ativos')" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700" id="filter-ativos">
                            Ativos
                        </button>
                        <button onclick="filtrarContatos('inativos')" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700" id="filter-inativos">
                            Inativos
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conteúdo do Modal -->
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div id="contatos-loading" class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                    <p class="text-gray-300 mt-4">Carregando contatos...</p>
                </div>

                <div id="contatos-content" class="hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Telefone</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Interesse</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="contatos-table-body" class="divide-y divide-gray-700">
                                <!-- Contatos serão inseridos aqui via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div id="contatos-pagination" class="mt-6 flex justify-between items-center">
                        <div class="text-sm text-gray-400">
                            <span id="contatos-info">Mostrando 0 de 0 contatos</span>
                        </div>
                        <div class="flex space-x-2">
                            <button id="contatos-prev" onclick="carregarContatosPagina('prev')" 
                                    class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Anterior
                            </button>
                            <span id="contatos-page-info" class="px-3 py-1 text-gray-300">Página 1 de 1</span>
                            <button id="contatos-next" onclick="carregarContatosPagina('next')" 
                                    class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Próxima
                            </button>
                        </div>
                    </div>
                </div>

                <div id="contatos-error" class="hidden text-center py-8">
                    <div class="text-red-400">
                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">Erro ao carregar contatos</p>
                        <p class="text-sm text-gray-400 mt-2" id="contatos-error-message"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Contato -->
<div id="modal-editar-telefone" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[70]">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-800 rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Editar Contato</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Nome do Contato</label>
                        <input type="text" id="edit-nome" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Telefone</label>
                        <input type="text" id="edit-telefone-novo" placeholder="Ex: 5511999999999"
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Digite apenas números (incluindo código do país)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Interesse</label>
                        <textarea id="edit-interesse" placeholder="Interesse do contato..." rows="3"
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button onclick="closeModal('modal-editar-telefone')" 
                            class="px-4 py-2 text-gray-300 hover:text-white">
                        Cancelar
                    </button>
                    <button onclick="salvarEdicaoTelefone()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variável específica para controle da modal de contatos
let currentEditingContact = null;

// Função para abrir modal de contatos
function verContatos(idLista) {
    currentListaId = idLista;
    currentPage = 1;
    currentFilter = 'todos';
    currentSearch = '';
    
    // Resetar filtros visuais
    document.querySelectorAll('[id^="filter-"]').forEach(btn => {
        btn.classList.remove('active:bg-blue-600', 'bg-blue-600');
        btn.classList.add('bg-gray-600');
    });
    document.getElementById('filter-todos').classList.add('bg-blue-600');
    
    // Limpar busca
    document.getElementById('search-contatos').value = '';
    
    openModal('modal-contatos');
    carregarContatos();
}

// Função para carregar contatos
function carregarContatos() {
    const loadingDiv = document.getElementById('contatos-loading');
    const contentDiv = document.getElementById('contatos-content');
    const errorDiv = document.getElementById('contatos-error');
    
    // Mostrar loading
    loadingDiv.classList.remove('hidden');
    contentDiv.classList.add('hidden');
    errorDiv.classList.add('hidden');
    
    const params = new URLSearchParams({
        action: 'get_contatos_lista',
        id: currentListaId,
        page: currentPage,
        search: currentSearch,
        filter: currentFilter
    });
    
    fetch('includes/campanhas/ajax_handlers.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarContatos(data);
                loadingDiv.classList.add('hidden');
                contentDiv.classList.remove('hidden');
                
                // Atualizar título do modal
                document.getElementById('modal-contatos-title').textContent = 
                    `Contatos da Lista: ${data.nome_lista}`;
            } else {
                throw new Error(data.message || 'Erro ao carregar contatos');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            document.getElementById('contatos-error-message').textContent = error.message;
            loadingDiv.classList.add('hidden');
            errorDiv.classList.remove('hidden');
        });
}

// Função para renderizar contatos na tabela
function renderizarContatos(data) {
    const tbody = document.getElementById('contatos-table-body');
    const info = document.getElementById('contatos-info');
    const pageInfo = document.getElementById('contatos-page-info');
    const prevBtn = document.getElementById('contatos-prev');
    const nextBtn = document.getElementById('contatos-next');
    
    // Limpar tabela
    tbody.innerHTML = '';
    
    // Renderizar contatos
    data.contatos.forEach(contato => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-700';
        
        // Status visual
        let statusBadge = '';
        if (contato.whatsapp_validado == 1) {
            if (contato.whatsapp_ativo == 1) {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ Ativo</span>';
            } else {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">✗ Inativo</span>';
            }
        } else {
            statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Não validado</span>';
        }
        
        // Interesse truncado com tooltip
        const interesse = contato.interesse || '';
        const interesseTruncado = interesse.length > 10 ? interesse.substring(0, 10) + '...' : interesse;
        const interesseCell = interesse.length > 10 ? 
            `<span title="${interesse.replace(/"/g, '&quot;')}" class="cursor-help">${interesseTruncado}</span>` : 
            interesseTruncado;
        
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-white">${contato.nome || 'N/A'}</td>
            <td class="px-4 py-3 text-sm text-white font-mono">${contato.telefone}</td>
            <td class="px-4 py-3 text-sm">${statusBadge}</td>
            <td class="px-4 py-3 text-sm text-gray-300">${interesseCell}</td>
            <td class="px-4 py-3 text-sm">
                <button onclick="editarContato('${contato.telefone}', '${contato.nome || 'N/A'}', '${contato.interesse || ''}')" 
                        class="text-blue-400 hover:text-blue-300 mr-2">
                    Editar
                </button>
                ${contato.whatsapp_ativo == 0 ? 
                    `<button onclick="ativarContato('${contato.telefone}')" class="text-green-400 hover:text-green-300">Ativar</button>` :
                    `<button onclick="inativarContato('${contato.telefone}')" class="text-red-400 hover:text-red-300">Inativar</button>`
                }
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Atualizar informações de paginação
    const start = ((data.page - 1) * 50) + 1;
    const end = Math.min(data.page * 50, data.total);
    info.textContent = `Mostrando ${start}-${end} de ${data.total} contatos`;
    pageInfo.textContent = `Página ${data.page} de ${data.total_pages}`;
    
    // Controle dos botões de paginação
    prevBtn.disabled = data.page <= 1;
    nextBtn.disabled = data.page >= data.total_pages;
}

// Função para filtrar contatos
function filtrarContatos(filtro) {
    currentFilter = filtro;
    currentPage = 1;
    
    // Atualizar visual dos botões
    document.querySelectorAll('[id^="filter-"]').forEach(btn => {
        btn.classList.remove('bg-blue-600');
        btn.classList.add('bg-gray-600');
    });
    document.getElementById('filter-' + filtro).classList.remove('bg-gray-600');
    document.getElementById('filter-' + filtro).classList.add('bg-blue-600');
    
    carregarContatos();
}

// Função para navegar páginas
function carregarContatosPagina(direcao) {
    if (direcao === 'prev' && currentPage > 1) {
        currentPage--;
    } else if (direcao === 'next') {
        currentPage++;
    }
    carregarContatos();
}

// Event listener para busca
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-contatos');
    if (searchInput) {
        let timeoutId;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                currentSearch = this.value;
                currentPage = 1;
                carregarContatos();
            }, 500);
        });
    }
});

// Função para editar contato
function editarContato(telefone, nome, interesse) {
    currentEditingContact = { telefone: telefone, nome: nome, interesse: interesse };
    
    document.getElementById('edit-nome').value = nome;
    document.getElementById('edit-telefone-novo').value = telefone;
    document.getElementById('edit-interesse').value = interesse || '';
    
    openModal('modal-editar-telefone');
}

// Função para salvar edição de contato
function salvarEdicaoTelefone() {
    const nome = document.getElementById('edit-nome').value.trim();
    const novoTelefone = document.getElementById('edit-telefone-novo').value.trim();
    const interesse = document.getElementById('edit-interesse').value.trim();
    
    if (!nome) {
        alert('Por favor, digite o nome do contato');
        return;
    }
    
    if (!novoTelefone) {
        alert('Por favor, digite o telefone');
        return;
    }
    
    // Validar formato do telefone (apenas números)
    if (!/^\d+$/.test(novoTelefone)) {
        alert('O telefone deve conter apenas números');
        return;
    }
    
    if (novoTelefone.length < 10 || novoTelefone.length > 15) {
        alert('O telefone deve ter entre 10 e 15 dígitos');
        return;
    }
    
    // Enviar requisição para atualizar contato
    fetch('includes/campanhas/ajax_handlers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'editar_contato',
            id_lista: currentListaId,
            telefone_atual: currentEditingContact.telefone,
            nome: nome,
            telefone_novo: novoTelefone,
            interesse: interesse
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('modal-editar-telefone');
            carregarContatos(); // Recarregar lista
            showToast('Contato atualizado com sucesso!', 'success');
        } else {
            alert('Erro ao atualizar contato: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar contato: ' + error.message);
    });
}

// Função para ativar contato
function ativarContato(telefone) {
    if (!confirm('Deseja ativar este contato?')) return;
    
    fetch('includes/campanhas/ajax_handlers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'alterar_status_contato',
            id_lista: currentListaId,
            telefone: telefone,
            status: 'ativar'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarContatos();
            showToast('Contato ativado com sucesso!', 'success');
        } else {
            alert('Erro ao ativar contato: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao ativar contato');
    });
}

// Função para inativar contato
function inativarContato(telefone) {
    const motivo = prompt('Motivo da inativação (opcional):');
    if (motivo === null) return; // Usuário cancelou
    
    fetch('includes/campanhas/ajax_handlers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'alterar_status_contato',
            id_lista: currentListaId,
            telefone: telefone,
            status: 'inativar',
            motivo: motivo || 'Inativado manualmente'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            carregarContatos();
            showToast('Contato inativado com sucesso!', 'success');
        } else {
            alert('Erro ao inativar contato: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao inativar contato');
    });
}

// Função auxiliar para toast (assumindo que existe)
function showToast(message, type = 'info') {
    // Implementar ou usar sistema de toast existente
    console.log(`Toast ${type}: ${message}`);
}
</script>