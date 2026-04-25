
<?php
function getLeadDetailsStyles() {
    ob_start();
?>
<style>
    html, body {
        height: 100%;
        margin: 0;
        overflow: hidden;
    }
    
    .scroll-container {
        height: calc(100vh - 4rem); /* 4rem = altura do header (h-16) */
        overflow-y: auto;
        padding-bottom: 6rem; /* Aumentar padding inferior para garantir rolagem adequada */
    }
    
    /* Para garantir que o conteúdo possa ser rolado até o final */
    .content-bottom-spacer {
        height: 5rem;
    }

    /* Estilização de scrollbar */
    .scroll-container::-webkit-scrollbar {
        width: 8px;
    }
    
    .scroll-container::-webkit-scrollbar-track {
        background: #1f2937;
    }
    
    .scroll-container::-webkit-scrollbar-thumb {
        background-color: #4b5563;
        border-radius: 4px;
    }
    
    .scroll-container::-webkit-scrollbar-thumb:hover {
        background-color: #6b7280;
    }
    
    /* Estilos para o modal de mensagens */
    #mensagens-modal {
        z-index: 50;
    }
    
    #mensagens-container {
        max-height: 70vh;
        overflow-y: auto;
    }
    
    #mensagens-container::-webkit-scrollbar {
        width: 8px;
    }
    
    #mensagens-container::-webkit-scrollbar-track {
        background: #1f2937;
    }
    
    #mensagens-container::-webkit-scrollbar-thumb {
        background-color: #4b5563;
        border-radius: 4px;
    }
    
    #mensagens-container::-webkit-scrollbar-thumb:hover {
        background-color: #6b7280;
    }
</style>
<script src="https://cdn.tailwindcss.com"></script>
<?php
    return ob_get_clean();
}
?>
