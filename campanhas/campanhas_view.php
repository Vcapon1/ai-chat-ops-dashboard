<?php
$id_session = $_SESSION['user_id']; // Este é o id da tabela sessions
$campanhas = getCampanhas($pdo_disparador, $id_session);
$listas = getListas($pdo_disparador, $id_session);
?>

<div class="bg-gray-800 rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-white">Campanhas de Reativação</h2>
        <?php if (!empty($listas)): ?>
            <button onclick="openModal('modal-campanha')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Nova Campanha</span>
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($listas)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-300">Você precisa de uma lista primeiro</h3>
            <p class="mt-1 text-sm text-gray-400">Crie uma lista de contatos antes de criar campanhas.</p>
            <div class="mt-6">
                <button onclick="showSection('listas')" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Ir para Listas
                </button>
            </div>
        </div>
    <?php elseif (empty($campanhas)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a2 2 0 012-2h2a2 2 0 012 2v6a3 3 0 01-3 3z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-300">Nenhuma campanha encontrada</h3>
            <p class="mt-1 text-sm text-gray-400">Crie sua primeira campanha de reativação de leads.</p>
            <div class="mt-6">
                <button onclick="openModal('modal-campanha')" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nova Campanha
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Campanha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Lista</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Agendamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Enviados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($campanhas as $campanha): ?>
                        <tr class="hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($campanha['nome_campanha']); ?></div>
                                <div class="text-sm text-gray-400">Criada em <?php echo date('d/m/Y', strtotime($campanha['data_criacao'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?php echo htmlspecialchars($campanha['nome_lista']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-300">
                                    <?php if (!empty($campanha['data_inicio'])): ?>
                                        <div class="font-medium">📅 <?php echo date('d/m/Y', strtotime($campanha['data_inicio'])); ?></div>
                                        <div class="text-xs text-gray-400">
                                            ⏰ <?php echo substr($campanha['hora_inicio'], 0, 5); ?> às <?php echo substr($campanha['hora_fim'], 0, 5); ?>
                                        </div>
                                        <?php if (!empty($campanha['dias_semana'])): ?>
                                            <?php 
                                            $dias_map = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 0 => 'Dom'];
                                            $dias_selecionados = json_decode($campanha['dias_semana'], true) ?: [];
                                            $dias_texto = array_map(function($dia) use ($dias_map) {
                                                return $dias_map[$dia] ?? '';
                                            }, $dias_selecionados);
                                            ?>
                                            <div class="text-xs text-gray-400">
                                                📋 <?php echo implode(', ', array_filter($dias_texto)); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-300"><?php echo date('d/m/Y H:i', strtotime($campanha['data_agendada'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($campanha['foi_disparada']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Enviada
                                    </span>
                                <?php else: ?>
                                    <?php if (strtotime($campanha['data_agendada']) > time()): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Agendada
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Enviando
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300">
                                    <?php echo $campanha['qtd_enviados']; ?>/<?php echo $campanha['qtd_programados']; ?>
                                    <?php if (isset($campanha['usar_ia']) && $campanha['usar_ia']): ?>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                            🤖 IA
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button onclick="verCampanha(<?php echo $campanha['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300">
                                    Ver Detalhes
                                </button>
                                <button onclick="duplicarCampanha(<?php echo $campanha['id']; ?>)" 
                                        class="text-green-400 hover:text-green-300">
                                    Duplicar
                                </button>
                                <?php if (!$campanha['foi_disparada']): ?>
                                    <button onclick="excluirCampanha(<?php echo $campanha['id']; ?>)" 
                                            class="text-red-400 hover:text-red-300">
                                        Excluir
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>