
/**
 * Handle status tag button clicks (Interested, Bot ON/OFF, etc)
 */
function setupStatusTagHandlers() {
    document.querySelectorAll('.status-tag').forEach(function(tag) {
        tag.addEventListener('click', function() {
            const action = this.dataset.action;
            const currentValue = this.dataset.value || '0';
            const leadId = this.dataset.clientId || this.dataset.leadId;
            
            // Validar se temos um ID de lead/cliente válido
            if (!leadId) {
                console.error('ID do cliente não encontrado no elemento:', this);
                showNotification('Erro: ID do cliente não encontrado', 'error');
                return;
            }
            
            // Display loading state
            this.classList.add('opacity-50');
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="animate-pulse">Processando...</span>';
            
            const formData = new FormData();
            formData.append('ajax_action', action);
            formData.append('current_value', currentValue);
            formData.append('client_id', leadId);
            
            // Determinar a URL correta com base na página atual
            let url = 'lead_details.php?id=' + leadId;
            
            // Se estamos na tela de mensagens, use a URL correta
            if (window.location.href.includes('messages_in.php')) {
                url = '../includes/lead/ajax_handlers.php';
            }
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                // Try to parse as JSON, handling empty or invalid responses
                try {
                    // Check if response is empty
                    if (!text || text.trim() === '') {
                        throw new Error('Resposta vazia do servidor');
                    }
                    
                    // Check if response starts with HTML doctype or tags
                    if (text.includes('<!DOCTYPE html>') || text.includes('<html')) {
                        throw new Error('O servidor retornou uma página HTML em vez de JSON. Verifique se há erros no servidor.');
                    }
                    
                    return JSON.parse(text);
                } catch (e) {
                    // If it's not valid JSON, throw a more detailed error
                    console.error("Resposta não é um JSON válido:", text);
                    
                    throw new Error('Resposta inválida do servidor: ' + (text ? text.substring(0, 100) + '...' : 'resposta vazia'));
                }
            })
            .then(data => {
                // Reset loading state
                this.classList.remove('opacity-50');
                this.innerHTML = originalText;
                
                if (data.success) {
                    // Show success notification
                    showNotification(data.message || 'Operação realizada com sucesso', 'success');
                    
                    // Update button state if needed
                    if (data.new_label) {
                        // Update the button text - need to update the entire HTML to maintain icons
                        const currentHTML = this.innerHTML;
                        const iconPart = currentHTML.substring(0, currentHTML.indexOf('</svg>') + 6);
                        this.innerHTML = iconPart + ' ' + data.new_label;
                    }
                    
                    // Update data attributes
                    if (data.new_state !== undefined) {
                        this.dataset.value = data.new_state;
                    }
                    
                    // Update styling
                    if (data.new_class) {
                        // Remove existing background and text classes
                        this.className = this.className.replace(/bg-[a-z]+-[0-9]+\/[0-9]+ text-[a-z]+-[0-9]+ border-[a-z]+-[0-9]+\/[0-9]+/g, '').trim();
                        // Add new classes
                        this.classList.add(...data.new_class.split(' '));
                    }
                    
                    // Reload page after certain actions, especially CRM integration
                    if (action === 'create_lead_crm') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    // Show error notification
                    showNotification(data.message || 'Erro ao processar solicitação', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset button state
                this.classList.remove('opacity-50');
                this.innerHTML = originalText;
                // Show error notification
                showNotification('Erro ao processar solicitação: ' + error.message, 'error');
            });
        });
    });
}

// Inicializar handlers quando o documento for carregado
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupStatusTagHandlers);
} else {
    setupStatusTagHandlers();
}
