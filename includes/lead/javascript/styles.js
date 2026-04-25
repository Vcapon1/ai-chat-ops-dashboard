
/**
 * Define animations and styles for the components
 */
function addStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* Estilos para animação das notificações */
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        .animate-fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        
        /* Estilo para animação de pulso */
        .animate-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);
}
