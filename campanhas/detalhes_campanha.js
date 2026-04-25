
/**
 * Função para ver detalhes de uma campanha
 */
function verCampanha(campanhaId) {
    // Mostrar loading no modal
    document.getElementById('detalhes-content').innerHTML = `
        <div class="flex items-center justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2 text-white">Carregando detalhes...</span>
        </div>
    `;
    
    // Abrir modal
    openModal('modal-detalhes-campanha');
    
    // Fazer requisição para buscar detalhes
    fetch('campanhas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'ajax_action': 'get_campanha_detalhes',
            'campanha_id': campanhaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderDetalhes(data.campanha);
        } else {
            document.getElementById('detalhes-content').innerHTML = `
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-300">Erro ao carregar detalhes</h3>
                    <p class="mt-1 text-sm text-gray-400">${data.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        document.getElementById('detalhes-content').innerHTML = `
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-300">Erro de conexão</h3>
                <p class="mt-1 text-sm text-gray-400">Não foi possível carregar os detalhes da campanha.</p>
            </div>
        `;
    });
}

/**
 * Renderizar detalhes da campanha no modal
 */
function renderDetalhes(campanha) {
    const isEnviada = campanha.foi_disparada == '1';
    const templateId = isEnviada ? 'template-campanha-enviada' : 'template-campanha-agendada';
    const template = document.getElementById(templateId);
    const content = template.content.cloneNode(true);
    
    // Preencher dados básicos
    content.querySelector('.campanha-nome').textContent = campanha.nome_campanha;
    content.querySelector('.campanha-lista').textContent = campanha.nome_lista;
    content.querySelector('.campanha-mensagem').textContent = campanha.mensagem;
    content.querySelector('.campanha-data-criacao').textContent = formatarData(campanha.data_criacao);
    
    // Status badge
    const statusBadge = content.querySelector('.campanha-status-badge');
    if (isEnviada) {
        statusBadge.innerHTML = `
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                ✅ Enviada
            </span>
        `;
    } else {
        const isVencida = new Date(campanha.data_agendada) < new Date();
        if (isVencida) {
            statusBadge.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                    📤 Enviando...
                </span>
            `;
        } else {
            statusBadge.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    ⏰ Agendada
                </span>
            `;
        }
    }
    
    // IA status
    const iaStatus = content.querySelector('.campanha-ia-status');
    if (campanha.usar_ia == '1') {
        iaStatus.innerHTML = `
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                🤖 Sim
            </span>
        `;
    } else {
        iaStatus.innerHTML = `
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                ❌ Não
            </span>
        `;
    }
    
    if (isEnviada) {
        // Preencher dados específicos de campanha enviada
        content.querySelector('.campanha-total-enviados').textContent = campanha.qtd_enviados || '0';
        content.querySelector('.campanha-data-envio').textContent = formatarDataHora(campanha.data_agendada);
        
        // Calcular taxa de entrega
        const totalProgramados = parseInt(campanha.qtd_programados) || 0;
        const totalEnviados = parseInt(campanha.qtd_enviados) || 0;
        const taxaEntrega = totalProgramados > 0 ? (totalEnviados / totalProgramados * 100).toFixed(1) : '0';
        
        content.querySelector('.campanha-taxa-entrega').textContent = `${taxaEntrega}%`;
        content.querySelector('.campanha-barra-entrega').style.width = `${taxaEntrega}%`;
        
        // Dados futuros (placeholder)
        content.querySelector('.campanha-total-responderam').textContent = '-';
        content.querySelector('.campanha-leads-gerados').textContent = '-';
        
    } else {
        // Preencher dados específicos de campanha agendada
        content.querySelector('.campanha-total-contatos').textContent = campanha.qtd_programados || '0';
        content.querySelector('.campanha-contatos-validos').textContent = campanha.qtd_programados || '0';
        content.querySelector('.campanha-data-prevista').textContent = formatarData(campanha.data_agendada);
        
        // Horário
        if (campanha.hora_inicio && campanha.hora_fim) {
            content.querySelector('.campanha-horario').textContent = 
                `${campanha.hora_inicio.substring(0,5)} às ${campanha.hora_fim.substring(0,5)}`;
        } else {
            content.querySelector('.campanha-horario').textContent = formatarHora(campanha.data_agendada);
        }
        
        // Dias da semana
        const diasContainer = content.querySelector('.campanha-dias-container');
        if (campanha.dias_semana) {
            try {
                const dias = JSON.parse(campanha.dias_semana);
                const diasMap = {0: 'Dom', 1: 'Seg', 2: 'Ter', 3: 'Qua', 4: 'Qui', 5: 'Sex', 6: 'Sáb'};
                const diasTexto = dias.map(dia => diasMap[dia]).join(', ');
                content.querySelector('.campanha-dias').textContent = diasTexto;
            } catch (e) {
                diasContainer.style.display = 'none';
            }
        } else {
            diasContainer.style.display = 'none';
        }
    }
    
    // Atualizar o conteúdo do modal
    document.getElementById('detalhes-content').innerHTML = '';
    document.getElementById('detalhes-content').appendChild(content);
}

/**
 * Funções auxiliares de formatação
 */
function formatarData(dataString) {
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR');
}

function formatarDataHora(dataString) {
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

function formatarHora(dataString) {
    const data = new Date(dataString);
    return data.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}
