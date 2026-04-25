<?php
$id_session = $_SESSION['user_id']; // Este é o id da tabela sessions
$stats = getEstatisticas($pdo_disparador, $id_session);
?>

<div class="space-y-6">
    <!-- Cards de Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-400 truncate">Total de Listas</dt>
                        <dd class="text-lg font-medium text-white"><?php echo number_format($stats['total_listas']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-400 truncate">Total de Contatos</dt>
                        <dd class="text-lg font-medium text-white"><?php echo number_format($stats['total_contatos']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a2 2 0 012-2h2a2 2 0 012 2v6a3 3 0 01-3 3z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-400 truncate">Total de Campanhas</dt>
                        <dd class="text-lg font-medium text-white"><?php echo number_format($stats['total_campanhas']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-400 truncate">Campanhas Disparadas</dt>
                        <dd class="text-lg font-medium text-white"><?php echo number_format($stats['campanhas_disparadas']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0v16a2 2 0 002 2h6a2 2 0 002-2V4M7 4H5a2 2 0 00-2 2v14a2 2 0 002 2h2M7 4v16m4-8l3-3m0 0l-3-3m3 3H9"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-400 truncate">Total Programado</dt>
                        <dd class="text-lg font-medium text-white"><?php echo number_format($stats['total_programado']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-400 truncate">Total Enviado</dt>
                        <dd class="text-lg font-medium text-white"><?php echo number_format($stats['total_enviado']); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Taxa de Conversão -->
    <div class="bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-white mb-4">Taxa de Entrega</h3>
        <div class="flex items-center">
            <div class="flex-1">
                <?php 
                $taxa_entrega = $stats['total_programado'] > 0 ? ($stats['total_enviado'] / $stats['total_programado']) * 100 : 0;
                ?>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-400">Enviado</span>
                    <span class="text-white"><?php echo number_format($taxa_entrega, 1); ?>%</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $taxa_entrega; ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Atividades -->
    <div class="bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-white mb-4">Últimas Campanhas</h3>
        <?php 
        $campanhas_recentes = getCampanhas($pdo_disparador, $id_session);
        $campanhas_recentes = array_slice($campanhas_recentes, 0, 5); // Mostrar apenas as 5 mais recentes
        ?>
        
        <?php if (empty($campanhas_recentes)): ?>
            <p class="text-gray-400">Nenhuma campanha encontrada.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($campanhas_recentes as $campanha): ?>
                    <div class="flex justify-between items-center py-2 border-b border-gray-700 last:border-b-0">
                        <div>
                            <p class="text-white font-medium"><?php echo htmlspecialchars($campanha['nome_campanha']); ?></p>
                            <p class="text-sm text-gray-400">
                                <?php echo htmlspecialchars($campanha['nome_lista']); ?> • 
                                <?php echo date('d/m/Y H:i', strtotime($campanha['data_agendada'])); ?>
                            </p>
                        </div>
                        <div>
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
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>