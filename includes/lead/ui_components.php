
<?php
/**
 * UI components for the leads page 
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Leads - Dashboard</title>
    <style>
        .kanban-column {
            height: calc(100vh - 250px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #4B5563 #1F2937;
        }
        .kanban-column::-webkit-scrollbar {
            width: 8px;
        }
        .kanban-column::-webkit-scrollbar-track {
            background: #1F2937;
        }
        .kanban-column::-webkit-scrollbar-thumb {
            background-color: #4B5563;
            border-radius: 4px;
        }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Adicionar listener para todos os botões de enviar para CRM
        document.querySelectorAll('.send-to-crm-btn').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const clientId = this.dataset.clientId;
                const button = this;
                
                // Desabilitar botão e mostrar loading
                button.disabled = true;
                button.innerHTML = '<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                
                // Enviar requisição
                fetch('leads.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'ajax_action': 'send_to_crm',
                        'client_id': clientId
                    })
                })
                .then(response => response.text())
                .then(text => {
                    // Try to parse as JSON
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Resposta não é um JSON válido:", text);
                        
                        if (text.includes('<!DOCTYPE html>')) {
                            throw new Error('O servidor retornou uma página HTML em vez de JSON. Verifique se há erros no servidor.');
                        } else {
                            throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100) + '...');
                        }
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Mostrar notificação de sucesso
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg animate-fade-in z-50';
                        notification.textContent = data.message;
                        
                        // Move the card to "Enviados para CRM" column
                        const leadCard = document.getElementById('lead-card-' + clientId);
                        if (leadCard) {
                            const crmColumn = document.getElementById('crm-column');
                            if (crmColumn) {
                                // Update button to show "No CRM" badge
                                const actionButtons = leadCard.querySelector('.action-buttons');
                                if (actionButtons) {
                                    const crmBadge = document.createElement('span');
                                    crmBadge.className = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                                    crmBadge.innerHTML = '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> No CRM';
                                    actionButtons.replaceChild(crmBadge, button);
                                }
                                
                                // Move card
                                crmColumn.insertBefore(leadCard, crmColumn.firstChild);
                            }
                        }
                        
                        document.body.appendChild(notification);
                        
                        // Update counters
                        updateCounters();
                        
                        // Remove notification after 3 seconds
                        setTimeout(() => {
                            notification.classList.replace('animate-fade-in', 'animate-fade-out');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    } else {
                        // Restore button state and show error
                        button.disabled = false;
                        button.innerHTML = 'Enviar para CRM';
                        
                        // Show error notification
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-red-600 text-white px-4 py-2 rounded shadow-lg animate-fade-in z-50';
                        notification.textContent = data.message;
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            notification.classList.replace('animate-fade-in', 'animate-fade-out');
                            setTimeout(() => {
                                notification.remove();
                            }, 300);
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Restore button state
                    button.disabled = false;
                    button.innerHTML = 'Enviar para CRM';
                    
                    // Show error notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-red-600 text-white px-4 py-2 rounded shadow-lg animate-fade-in z-50';
                    notification.textContent = 'Erro ao processar requisição: ' + error.message;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.classList.replace('animate-fade-in', 'animate-fade-out');
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }, 3000);
                });
            });
        });
        
        // Função para atualizar contadores
        function updateCounters() {
            const novosContatos = document.querySelectorAll('#novos-column .lead-card').length;
            const leadsNoCRM = document.querySelectorAll('#crm-column .lead-card').length;
            const leadsDescartados = document.querySelectorAll('#descartados-column .lead-card').length;
            
            document.getElementById('novos-count').textContent = novosContatos;
            document.getElementById('crm-count').textContent = leadsNoCRM;
            document.getElementById('descartados-count').textContent = leadsDescartados;
        }
    });
    </script>
</head>
<body class="bg-gray-900 text-white">
    <div class="flex">
        <?php include 'includes/menu.php'; ?>

        <div class="flex-1">
            <header class="bg-gray-800 shadow-sm py-3">
                <div class="max-w-7xl mx-auto px-4">
                    <h1 class="text-xl font-medium text-white">Leads</h1>
                </div>
            </header>

            <main class="p-6">
                <!-- Filtros -->
                <?php include 'includes/lead/filters_component.php'; ?>

                <!-- Layout Kanban -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Coluna: Novos Contatos -->
                    <?php include 'includes/lead/column_novos.php'; ?>

                    <!-- Coluna: Enviados para CRM -->
                    <?php include 'includes/lead/column_crm.php'; ?>

                    <!-- Coluna: Descartados -->
                    <?php include 'includes/lead/column_descartados.php'; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
<?php
?>
