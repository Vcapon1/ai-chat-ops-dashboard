
<?php
/**
 * "Enviados para CRM" column component
 */
?>
<div class="bg-gray-800 rounded-lg p-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-green-400">Enviados para CRM</h2>
        <span id="crm-count" class="px-2 py-1 bg-green-600/20 text-green-400 text-sm rounded-full">
            <?php echo count($leadsNoCRM); ?>
        </span>
    </div>
    <div id="crm-column" class="kanban-column space-y-4">
        <?php foreach ($leadsNoCRM as $lead): ?>
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
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            No CRM
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
