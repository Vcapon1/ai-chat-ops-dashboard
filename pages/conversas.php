
<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Buscar conversas do banco de dados
try {
    $stmt = $pdo->query("SELECT c.*, m.message_cliente as ultima_mensagem, m.created_at as horario, m.instrucao
                         FROM clients c 
                         LEFT JOIN messages m ON m.client_id = c.id 
                         WHERE m.id = (
                             SELECT MAX(id) 
                             FROM messages 
                             WHERE client_id = c.id
                         )
                         ORDER BY m.created_at DESC");
    $conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar conversas: " . $e->getMessage());
    $conversas = [];
}

include 'includes/header.php';
?>

<div class="flex">
    <?php include 'includes/menu.php'; ?>

    <!-- Conteúdo Principal -->
    <main class="flex-1 p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Lista de Conversas -->
            <div class="bg-gray-800 rounded-lg shadow-xl p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Conversas Recentes</h2>
                </div>
                <div class="space-y-2">
                    <?php foreach ($conversas as $conversa): ?>
                        <div class="bg-gray-700 rounded p-3 hover:bg-gray-600 cursor-pointer">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                    <div>
                                        <h3 class="text-white font-medium">
                                            <?php echo htmlspecialchars($conversa['client_name']); ?>
                                        </h3>
                                        <p class="text-gray-400 text-sm">
                                            <?php echo htmlspecialchars($conversa['ultima_mensagem']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <!-- Ícone 1: Instrução -->
                                    <span class="text-blue-400 hover:text-blue-300" title="<?php echo htmlspecialchars($conversa['instrucao'] ?? 'Sem instrução definida'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square-text">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                            <path d="M13 8H7"/>
                                            <path d="M17 12H7"/>
                                        </svg>
                                    </span>
                                    <!-- Ícone 2 -->
                                    <span class="text-green-400 hover:text-green-300" title="Ações">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings">
                                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </span>
                                    <!-- Ícone 3 -->
                                    <span class="text-yellow-400 hover:text-yellow-300" title="Informações">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 16v-4"/>
                                            <path d="M12 8h.01"/>
                                        </svg>
                                    </span>
                                    <span class="text-gray-400 text-xs ml-2">
                                        <?php echo date('H:i', strtotime($conversa['horario'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Área da Conversa -->
            <div class="md:col-span-2 bg-gray-800 rounded-lg shadow-xl p-4 flex flex-col h-[600px]">
                <div class="flex items-center space-x-4 border-b border-gray-700 pb-4">
                    <div class="flex-1">
                        <h2 class="text-white font-medium">Selecione uma conversa</h2>
                        <p class="text-gray-400 text-sm">Clique em uma conversa para ver as mensagens</p>
                    </div>
                </div>

                <!-- Mensagens -->
                <div class="flex-1 overflow-y-auto py-4 space-y-4">
                    <!-- As mensagens serão carregadas via AJAX quando uma conversa for selecionada -->
                </div>

                <!-- Input de Mensagem -->
                <div class="border-t border-gray-700 pt-4">
                    <form class="flex space-x-4">
                        <input
                            type="text"
                            placeholder="Digite sua mensagem..."
                            class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            disabled
                        >
                        <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700" disabled>
                            Enviar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
