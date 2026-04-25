<?php
function renderMainCard($lead, $formatPhoneNumber) {
    ob_start();
?>
<div class="bg-gray-800 rounded-lg shadow-xl p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h2 class="text-xl font-semibold mb-4 text-blue-200">
                <?php echo htmlspecialchars($lead['client_name'] ?? 'Nome não informado'); ?>
            </h2>
            <p class="text-gray-400 mb-2">
                Telefone: <?php echo $formatPhoneNumber($lead['client_number']); ?>
            </p>
            <p class="text-gray-400 mb-2">
                Criado em: <?php echo date('d/m/Y H:i', strtotime($lead['created_at'])); ?>
            </p>
            <p class="text-gray-400">
                Última interação: <?php echo date('d/m/Y H:i', strtotime($lead['last_interaction'])); ?>
            </p>
            
            <!-- Ver Conversa button -->
            <a href="messages_in.php?client_id=<?php echo $lead['id']; ?>" 
               class="mt-4 inline-flex items-center px-3 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" 
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                     class="lucide lucide-message-square mr-2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Ver Conversa
            </a>
        </div>
        <div>
            <h3 class="text-lg font-semibold mb-3 text-blue-200">Status:</h3>
            <div class="flex flex-wrap gap-2 mb-4">
                <!-- Bot ON/OFF Tag (Clicável) -->
                <span 
                    class="status-tag px-3 py-1.5 rounded-full text-sm cursor-pointer border transition-all flex items-center gap-2
                    <?php echo isset($lead['conversa_ativa']) && $lead['conversa_ativa'] == 1 ? 'bg-green-600/20 text-green-400 border-green-500/30' : 'bg-gray-600/20 text-gray-400 border-gray-500/30'; ?>"
                    data-action="toggle_active_conversation"
                    data-value="<?php echo isset($lead['conversa_ativa']) ? $lead['conversa_ativa'] : '0'; ?>"
                    data-lead-id="<?php echo $lead['id']; ?>"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                    Bot <?php echo isset($lead['conversa_ativa']) && $lead['conversa_ativa'] == 1 ? 'ON' : 'OFF'; ?>
                </span>
                
                <!-- Follow-up Tag (Clicável) -->
                <span 
                    class="status-tag px-3 py-1.5 rounded-full text-sm cursor-pointer border transition-all flex items-center gap-2
                    <?php echo isset($lead['conversa_descartada']) && $lead['conversa_descartada'] == 0 ? 'bg-blue-600/20 text-blue-400 border-blue-500/30' : 'bg-amber-600/20 text-amber-400 border-amber-500/30'; ?>"
                    data-action="toggle_followup"
                    data-value="<?php echo isset($lead['conversa_descartada']) ? $lead['conversa_descartada'] : '0'; ?>"
                    data-lead-id="<?php echo $lead['id']; ?>"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    Follow-up <?php echo isset($lead['conversa_descartada']) && $lead['conversa_descartada'] == 0 ? 'OFF' : 'ON'; ?>
                </span>
                
                <!-- Enviar para CRM (Clicável ou static) -->
                <?php if (isset($lead['enviado_crm']) && $lead['enviado_crm'] == 1): ?>
                    <span class="px-3 py-1.5 bg-green-600/20 text-green-400 rounded-full text-sm border border-green-500/30 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        No CRM
                    </span>
                <?php else: ?>
                    <span 
                        class="status-tag px-3 py-1.5 bg-purple-600/20 text-purple-400 rounded-full text-sm cursor-pointer border border-purple-500/30 hover:bg-purple-600/30 flex items-center gap-2"
                        data-action="create_lead_crm"
                        data-value="0"
                        data-lead-id="<?php echo $lead['id']; ?>"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        Enviar para CRM
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
    return ob_get_clean();
}

function renderIntentionCard($lead) {
    ob_start();
?>
<div class="bg-gray-800 rounded-lg shadow-xl p-6">
    <h3 class="text-lg font-semibold mb-4 text-blue-200">Intenção do Cliente</h3>
    <p class="text-gray-300 whitespace-pre-line">
        <?php echo nl2br(htmlspecialchars($lead['client_intent'] ?? 'Não informado')); ?>
    </p>
</div>
<?php
    return ob_get_clean();
}

function renderQuestionsCard($sdrQuestions) {
    ob_start();
?>
<div class="bg-gray-800 rounded-lg shadow-xl p-6">
    <h3 class="text-lg font-semibold mb-4 text-blue-200">Perguntas e Respostas SDR</h3>
    
    <?php if (count($sdrQuestions) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($sdrQuestions as $question): ?>
                <div class="bg-gray-700/50 rounded-lg p-4">
                    <h4 class="font-medium text-blue-300 mb-2">
                        <?php echo htmlspecialchars($question['pergunta']); ?>
                    </h4>
                    <?php if (!empty($question['resposta'])): ?>
                        <p class="text-gray-300">
                            <?php echo nl2br(htmlspecialchars($question['resposta'])); ?>
                        </p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Sem resposta</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-400">Nenhuma pergunta registrada para este lead.</p>
    <?php endif; ?>
</div>
<?php
    return ob_get_clean();
}

function renderCadenceCard($lead) {
    if (empty($lead['CADENCIA_SDR'])) {
        return '';
    }
    
    ob_start();
?>
<div class="bg-gray-800 rounded-lg shadow-xl p-6">
    <h3 class="text-lg font-semibold mb-4 text-blue-200">Cadência SDR</h3>
    <p class="text-gray-300 whitespace-pre-line">
        <?php echo nl2br(htmlspecialchars($lead['CADENCIA_SDR'])); ?>
    </p>
</div>
<?php
    return ob_get_clean();
}

function renderNotesCard($leadNotes, $lead_id) {
    ob_start();
?>
<div class="bg-gray-800 rounded-lg shadow-xl p-6">
    <h3 class="text-lg font-semibold mb-4 text-blue-200">Anotações</h3>
    
    <!-- Form to add new notes -->
    <form id="add-note-form" class="mb-6">
        <input type="hidden" name="ajax_action" value="add_note">
        <input type="hidden" name="client_id" value="<?php echo $lead_id; ?>">
        <div class="flex flex-col space-y-2">
            <textarea 
                name="note_content" 
                id="note-content" 
                rows="3" 
                class="w-full bg-gray-700 border border-gray-600 rounded p-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Adicionar uma anotação sobre este lead..."
                required
            ></textarea>
            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                >
                    Salvar Anotação
                </button>
            </div>
        </div>
    </form>
    
    <!-- Display existing notes -->
    <div id="notes-container" class="space-y-4">
        <?php if (count($leadNotes) > 0): ?>
            <?php foreach ($leadNotes as $note): ?>
                <div class="bg-gray-700/50 rounded-lg p-4">
                    <p class="text-gray-300 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($note['note'])); ?></p>
                    <p class="text-xs text-gray-500 mt-2">
                        <?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-400" id="no-notes-message">Nenhuma anotação registrada para este lead.</p>
        <?php endif; ?>
    </div>
</div>
<?php
    return ob_get_clean();
}
?>
