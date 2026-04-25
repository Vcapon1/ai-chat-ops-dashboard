<?php
require_once __DIR__ . '/../credits_manager.php';

$id_session = $_SESSION['user_id'];
$creditos = obterCreditos($pdo, $id_session);
$historico = obterHistoricoCreditos($pdo, $id_session, 20);
?>

<div class="bg-gray-800 rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-white">Gerenciar Créditos</h2>
        <div class="flex items-center bg-gradient-to-r from-green-600 to-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <div>
                <div class="text-2xl font-bold"><?php echo number_format($creditos); ?></div>
                <div class="text-sm opacity-90">créditos disponíveis</div>
            </div>
        </div>
    </div>

    <!-- Tabela de Preços -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-medium text-white mb-4">💰 Tabela de Preços</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-gray-600">
                    <span class="text-gray-300">📱 Envio de mensagem</span>
                    <span class="text-white font-medium">3 créditos</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-600">
                    <span class="text-gray-300">✅ Validação WhatsApp</span>
                    <span class="text-white font-medium">1 crédito</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-300">🤖 Mensagem personalizada IA</span>
                    <span class="text-white font-medium">+2 créditos</span>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-medium text-white mb-4">🛒 Comprar Créditos</h3>
            <div class="text-center">
                <p class="text-gray-300 mb-4">Em breve: sistema de compra de créditos via PIX</p>
                <button disabled class="w-full px-4 py-2 bg-gray-600 text-gray-400 rounded-lg cursor-not-allowed">
                    Comprar Créditos (Em Breve)
                </button>
            </div>
        </div>
    </div>

    <!-- Histórico de Transações -->
    <div class="bg-gray-700 rounded-lg p-4">
        <h3 class="text-lg font-medium text-white mb-4">📊 Histórico de Transações</h3>
        
        <?php if (empty($historico)): ?>
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-300">Nenhuma transação encontrada</h3>
                <p class="mt-1 text-sm text-gray-400">Suas transações aparecerão aqui.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Descrição</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Créditos</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        <?php foreach ($historico as $transacao): ?>
                            <tr class="hover:bg-gray-600">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo date('d/m/Y H:i', strtotime($transacao['created_at'])); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <?php
                                    $icons = [
                                        'envio_mensagem' => '📱',
                                        'validacao_whatsapp' => '✅',
                                        'mensagem_ia' => '🤖',
                                        'compra' => '🛒',
                                        'bonus' => '🎁'
                                    ];
                                    $tipos = [
                                        'envio_mensagem' => 'Envio',
                                        'validacao_whatsapp' => 'Validação',
                                        'mensagem_ia' => 'IA',
                                        'compra' => 'Compra',
                                        'bonus' => 'Bônus'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?php echo $icons[$transacao['tipo']] ?? ''; ?> <?php echo $tipos[$transacao['tipo']] ?? $transacao['tipo']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-300">
                                    <?php echo htmlspecialchars($transacao['descricao'] ?? '-'); ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($transacao['quantidade'] > 0): ?>
                                        <span class="text-green-400">+<?php echo $transacao['quantidade']; ?></span>
                                    <?php else: ?>
                                        <span class="text-red-400"><?php echo $transacao['quantidade']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo number_format($transacao['saldo_atual']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>