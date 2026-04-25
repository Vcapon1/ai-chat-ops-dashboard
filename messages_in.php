<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Acesso não autorizado");
}

if (!isset($_GET['client_id'])) {
    die("ID do cliente não fornecido");
}

$client_id = intval($_GET['client_id']);

// Handle form submission for sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !empty($_POST['message'])) {
    $message = trim($_POST['message']);
    
    try {
        // Insert the message with origin 'ADM'
        $stmt = $pdo->prepare("INSERT INTO mensagens (client_id, message, origem, created_at) VALUES (?, ?, 'ADM', NOW())");
        if ($stmt->execute([$client_id, $message])) {
            // Send the message via the API
            require_once 'includes/db_queries.php';
            $result = sendMessage($pdo, $client_id, $message, $_SESSION['user_id']);
            
            if (!$result) {
                // Log error but continue to show the page
                error_log("Failed to send message via API for client ID $client_id");
            }
            
            // Optionally, deactivate the bot conversation
            $stmt = $pdo->prepare("UPDATE clients SET conversa_ativa = 0 WHERE id = ?");
            $stmt->execute([$client_id]);
            
            // Redirect to refresh the page and show the new message
            header("Location: messages_in.php?client_id=" . $client_id);
            exit;
        }
    } catch (PDOException $e) {
        die("Erro ao enviar mensagem: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ? AND session_id = ?");
    $stmt->execute([$client_id, $_SESSION['user_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        die("Cliente não encontrado");
    }
    
    $stmt = $pdo->prepare("SELECT * FROM mensagens WHERE client_id = ? ORDER BY created_at ASC");
    $stmt->execute([$client_id]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar mensagens: " . $e->getMessage());
}

// Função para obter descrição da ação com base no ID
function getActionDescription($action_id) {
    $actions = [
        1 => 'Perguntar para o administrador',
        2 => 'Retomar essa conversa em outro horário',
        3 => 'Reagir a uma mensagem',
        4 => 'Contatar outra pessoa',
        5 => 'Gerar um lead',
        6 => 'Informou o nome',
        7 => 'desativar cliente',
        8 => 'enviar um vídeo',
        9 => 'Enviar uma mensagem extra',
        10 => 'enviar a localizaçao do mapa',
        11 => 'enviar um documento pdf'
    ];
    
    return isset($actions[$action_id]) ? $actions[$action_id] : 'Ação desconhecida';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/lucide-icons@latest/dist/umd/lucide.min.css" rel="stylesheet">
    <style>
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
        .animate-fade-out {
            animation: fade-out 0.3s ease-out;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fade-out {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        .message-info-icon {
            cursor: pointer;
            transition: color 0.2s;
        }
        .message-info-icon:hover {
            color: #60a5fa;
        }
        .tooltip {
            position: absolute;
            background-color: rgba(17, 24, 39, 0.95);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            max-width: 300px;
            word-wrap: break-word;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            pointer-events: none;
            top: -10px;  /* Posição acima do cursor */
            left: 50%;
            transform: translateX(-50%) translateY(-100%);
            margin-top: -8px;  /* Ajuste fino para distância */
        }
        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }
        .flex-col {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Garante que o conteúdo da mensagem preenche o espaço */
        .message-container {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .messages-area {
            flex-grow: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        .message-input-area {
            padding: 1rem;
            border-top: 1px solid #4B5563;
            background-color: #1F2937;
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gray-800">
    <div class="flex flex-col h-screen">
        <div class="flex items-center space-x-4 border-b border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <h2 class="text-white font-medium">
                    <?php echo htmlspecialchars($cliente['client_name'] ?? 'Cliente sem nome'); ?>
                </h2>
                <form method="POST" action="conversas.php" class="inline">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <input type="hidden" name="new_status" value="<?php echo $cliente['conversa_ativa'] ? '0' : '1'; ?>">
                    <button type="submit" name="toggle_status" class="text-sm font-medium text-white">
                        Bot 
                        <span class="ml-1 px-2 py-0.5 rounded text-xs <?php echo $cliente['conversa_ativa'] ? 'bg-green-500' : 'bg-red-500'; ?>">
                            <?php echo $cliente['conversa_ativa'] ? 'ON' : 'OFF'; ?>
                        </span>
                    </button>
                </form>
                <p class="text-gray-400 text-sm">
                    Lead Score: <?php echo htmlspecialchars((string)($cliente['lead_score'] ?? '0')); ?>
                </p>
                
                <!-- Botão Enviar para CRM -->
                <?php if (empty($cliente['enviado_crm'])): ?>
                <button id="sendToCRM" 
                        data-client-id="<?php echo $client_id; ?>"
                        data-action="create_lead_crm"
                        class="status-tag ml-2 px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700 transition-colors flex items-center gap-1">
                    <svg class="lucide lucide-upload-cloud" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path><path d="M12 12v9"></path><path d="m16 16-4-4-4 4"></path></svg>
                    Enviar para CRM
                </button>
                <?php else: ?>
                <span class="ml-2 px-3 py-1 bg-green-600 text-white text-xs rounded flex items-center gap-1">
                    <svg class="lucide lucide-check-circle" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>
                    No CRM
                </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="messages-area space-y-4" id="messagesContainer">
            <?php foreach ($mensagens as $mensagem): ?>
                <?php 
                    $messageClass = '';
                    $timeClass = '';
                    $alignment = '';
                    $isAssistant = false;
                    
                    switch ($mensagem['origem']) {
                        case 'user':
                            $messageClass = 'bg-blue-100 text-gray-800';
                            $timeClass = 'text-gray-500';
                            $alignment = 'justify-start';
                            break;
                        case 'assistant':
                            $messageClass = 'bg-green-100 text-gray-800';
                            $timeClass = 'text-gray-500';
                            $alignment = 'justify-end';
                            $isAssistant = true;
                            break;
                        case 'ADM':
                            $messageClass = 'bg-orange-100 text-gray-800';
                            $timeClass = 'text-gray-500';
                            $alignment = 'justify-end';
                            break;
                    }
                ?>
                
                <div class="flex <?php echo $alignment; ?>">
                    <div class="<?php echo $messageClass; ?> rounded-lg px-4 py-2 max-w-xs relative group">
                        <p><?php echo htmlspecialchars($mensagem['message'] ?? ''); ?></p>
                        
                        <?php if ($isAssistant && (!empty($mensagem['instrucao']) || !empty($mensagem['motivo']) || !empty($mensagem['acao']))): ?>
                        <div class="flex gap-2 mt-1 items-center">
                            <?php if (!empty($mensagem['instrucao'])): ?>
                            <span class="message-info-icon tooltip-trigger" data-tooltip="Instrução: <?php echo htmlspecialchars($mensagem['instrucao']); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-500">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    <path d="M13 8H7"/>
                                    <path d="M17 12H7"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($mensagem['motivo'])): ?>
                            <span class="message-info-icon tooltip-trigger" data-tooltip="Motivo: <?php echo htmlspecialchars($mensagem['motivo']); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-yellow-500">
                                    <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <line x1="10" y1="9" x2="8" y2="9"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($mensagem['acao'])): ?>
                            <span class="message-info-icon tooltip-trigger" data-tooltip="Ação: <?php echo getActionDescription($mensagem['acao']); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="<?php echo $mensagem['acao'] == 5 ? 'text-purple-500' : 'text-green-500'; ?>">
                                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <span class="text-xs <?php echo $timeClass; ?> block mt-1">
                            <?php echo date('d/m/y H:i:s', strtotime($mensagem['created_at'])); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Área de entrada de mensagem -->
        <div class="message-input-area">
            <form method="POST" action="" class="flex space-x-4">
                <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                <input
                    type="text"
                    name="message"
                    placeholder="Digite sua mensagem..."
                    class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                <button 
                    type="submit"
                    class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition-colors flex items-center gap-2"
                >
                    <svg class="lucide lucide-send" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4Z"></path><path d="M22 2 11 13"></path></svg>
                    Enviar
                </button>
            </form>
        </div>
    </div>
    
    <!-- Notificação toast -->
    <div id="toast" class="fixed top-4 right-4 z-50 transform translate-y-[-100px] opacity-0 transition-all duration-300">
        <div class="bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center">
            <span id="toast-icon" class="mr-2">
                <!-- O ícone será inserido via JavaScript -->
            </span>
            <span id="toast-message"></span>
        </div>
    </div>
    
    <script>
        // Função para mostrar notificações toast
        function showNotification(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');
            
            // Definir o ícone com base no tipo
            if (type === 'success') {
                toastIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-500"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><path d="m9 11 3 3L22 4"></path></svg>';
            } else {
                toastIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-500"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
            }
            
            // Definir a mensagem
            toastMessage.textContent = message;
            
            // Mostrar a notificação
            toast.classList.remove('translate-y-[-100px]', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            
            // Remover após 3 segundos
            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-[-100px]', 'opacity-0');
            }, 3000);
        }
        
        window.onload = function() {
            // Scroll to the bottom of the messages container
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Create tooltip element
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.style.display = 'none';
            document.body.appendChild(tooltip);
            
            // Add event listeners to tooltip triggers
            const tooltipTriggers = document.querySelectorAll('.tooltip-trigger');
            tooltipTriggers.forEach(trigger => {
                trigger.addEventListener('mouseover', function(e) {
                    const tooltipText = this.getAttribute('data-tooltip');
                    if (!tooltipText) return;
                    
                    tooltip.textContent = tooltipText;
                    tooltip.style.display = 'block';
                    
                    // Position tooltip above the icon
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = (rect.left + (rect.width / 2)) + 'px';
                    tooltip.style.top = rect.top + 'px';
                });
                
                trigger.addEventListener('mouseout', function() {
                    tooltip.style.display = 'none';
                });
            });
            
            // Carregar script de tratamento de status tags para o botão CRM
            // Este script usa a classe .status-tag que também é aplicada ao botão "Enviar para CRM"
            const script = document.createElement('script');
            script.src = 'includes/lead/javascript/status_tag_handlers.js';
            document.body.appendChild(script);
        };
    </script>
</body>
</html>
