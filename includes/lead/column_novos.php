
<?php
/**
 * "Novos Contatos" column component
 */
?>
<div class="bg-gray-800 rounded-lg p-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-blue-400">Novos Contatos</h2>
        <span id="novos-count" class="px-2 py-1 bg-blue-600/20 text-blue-400 text-sm rounded-full">
            <?php echo count($novosContatos); ?>
        </span>
    </div>
    <div id="novos-column" class="kanban-column space-y-4">
        <?php foreach ($novosContatos as $lead): ?>
            <div id="lead-card-<?php echo $lead['id']; ?>" class="lead-card bg-gray-700 p-4 rounded-lg">
                <div class="flex justify-between items-start">
                    <a href="lead_details.php?id=<?php echo $lead['id']; ?>" class="text-blue-400 hover:text-blue-300 font-medium">
                        <?php echo htmlspecialchars($lead['client_name']); ?>
                    </a>
                    <span class="text-xs text-gray-400">
                        <?php echo date('d/m/Y H:i', strtotime($lead['last_interaction'])); ?>
                    </span>
                </div>
                <div class="mt-2 text-gray-300 text-sm line-clamp-2">
                    <?php echo htmlspecialchars(substr($lead['client_intent'], 0, 100) . (strlen($lead['client_intent']) > 100 ? '...' : '')); ?>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <div class="flex space-x-1">
                        <?php if ($lead['interessado']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Interessado
                        </span>
                        <?php endif; ?>
                        <?php if (isset($lead['conversa_ativa']) && $lead['conversa_ativa']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Ativo
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="action-buttons">
                        <button 
                            class="send-to-crm-btn inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-600 text-white hover:bg-yellow-700 transition-colors"
                            data-client-id="<?php echo $lead['id']; ?>"
                        >
                            Enviar para CRM
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
