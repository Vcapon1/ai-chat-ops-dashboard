<?php
function renderLeadCard($lead) {
    include_once 'utils.php';
    $formattedPhone = formatPhoneNumber($lead['client_number']);
    $truncatedIntent = '';
    
    if (!is_null($lead['client_intent'])) {
        $truncatedIntent = strlen($lead['client_intent']) > 100 ? 
            substr($lead['client_intent'], 0, 100) . '...' : 
            $lead['client_intent'];
    }
    ?>
    <div class="bg-gray-800/50 backdrop-blur-sm border border-gray-700/50 rounded-lg p-3 hover:bg-gray-800/70 transition-all duration-200 shadow-lg">
        <div class="flex justify-between items-start gap-2 mb-2">
            <div>
                <h3 class="text-lg font-medium text-blue-200"><?php echo htmlspecialchars($lead['client_name'] ?? 'Nome não informado'); ?></h3>
                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($formattedPhone); ?></p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <?php if ($lead['interessado']): ?>
                    <span class="px-2 py-0.5 bg-green-600/20 text-green-400 text-xs rounded-full border border-green-500/30">
                        Interessado
                    </span>
                <?php endif; ?>
                <?php if ($lead['desinteressado']): ?>
                    <span class="px-2 py-0.5 bg-red-600/20 text-red-400 text-xs rounded-full border border-red-500/30">
                        Não Interessado
                    </span>
                <?php endif; ?>
                <?php if (isset($lead['crm']) && $lead['crm']): ?>
                    <span class="px-2 py-0.5 bg-blue-600/20 text-blue-400 text-xs rounded-full border border-blue-500/30">
                        CRM
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="space-y-2 mb-3">
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400">Lead Score:</span>
                <div class="flex-1 bg-gray-700/50 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: <?php echo $lead['lead_score']; ?>%"></div>
                </div>
                <span class="text-xs text-gray-400"><?php echo $lead['lead_score']; ?>%</span>
            </div>
            <p class="text-xs text-gray-400">Última interação: <?php echo date('d/m/Y H:i', strtotime($lead['last_interaction'])); ?></p>
        </div>

        <?php if ($truncatedIntent): ?>
        <div class="mb-3">
            <h4 class="text-xs font-medium mb-1 text-blue-300">Intenção do Cliente:</h4>
            <p class="text-sm text-gray-300 leading-relaxed"><?php echo htmlspecialchars($truncatedIntent); ?></p>
        </div>
        <?php endif; ?>

        <div class="flex justify-end">
            <a href="lead_details.php?id=<?php echo $lead['id']; ?>" 
               class="px-3 py-1.5 bg-blue-600/20 text-blue-400 rounded-md hover:bg-blue-600/30 transition-colors text-sm border border-blue-500/30">
                Ver Detalhes
            </a>
        </div>
    </div>
    <?php
}
?>