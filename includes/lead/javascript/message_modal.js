
/**
 * Setup modal for viewing messages
 */
function setupMessageModal() {
    const verConversaLinks = document.querySelectorAll('a[href^="messages_in.php"]');
    const modal = document.getElementById('mensagens-modal');
    const mensagensContainer = document.getElementById('mensagens-container');
    const fecharModal = document.getElementById('fechar-modal');
    
    if (verConversaLinks && modal && mensagensContainer) {
        verConversaLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                
                // Mostrar modal
                modal.classList.remove('hidden');
                mensagensContainer.innerHTML = '<p class="text-center text-gray-400">Carregando mensagens...</p>';
                
                // Log the URL for debugging
                console.log('Carregando mensagens de:', url);
                
                // Carregar mensagens via fetch
                fetch(url)
                    .then(response => {
                        console.log('Status da resposta de mensagens:', response.status);
                        if (!response.ok) {
                            throw new Error('Erro na resposta da rede: ' + response.status);
                        }
                        return response.text();
                    })
                    .then(html => {
                        console.log('HTML recebido, tamanho:', html.length);
                        
                        // Extrair apenas a parte das mensagens do HTML retornado
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        
                        // Atualizar o seletor para corresponder à estrutura atual da página
                        const mensagens = tempDiv.querySelector('.messages-area');
                        if (mensagens) {
                            mensagensContainer.innerHTML = mensagens.innerHTML;
                            console.log('Mensagens extraídas e exibidas no modal');
                        } else {
                            console.error('Seletor de mensagens não encontrado no HTML');
                            mensagensContainer.innerHTML = '<p class="text-center text-gray-400">Nenhuma mensagem encontrada</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar mensagens:', error);
                        mensagensContainer.innerHTML = '<p class="text-center text-red-400">Erro ao carregar mensagens: ' + error.message + '</p>';
                    });
            });
        });
        
        // Fechar modal quando clicar no botão fechar
        if (fecharModal) {
            fecharModal.addEventListener('click', function() {
                modal.classList.add('hidden');
            });
            
            // Fechar modal quando clicar fora do conteúdo
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    } else {
        console.error('Elementos necessários para o modal não foram encontrados:', {
            links: verConversaLinks ? verConversaLinks.length : 0,
            modal: !!modal,
            container: !!mensagensContainer
        });
    }
}
