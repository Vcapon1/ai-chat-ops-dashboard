<?php
$id_session = $_SESSION['user_id']; // Este é o id da tabela sessions
$listas = getListas($pdo_disparador, $id_session);
?>

<div class="bg-gray-800 rounded-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-white">Minhas Listas de Contatos</h2>
        <button onclick="openModal('modal-import')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Nova Lista</span>
        </button>
    </div>

    <?php if (empty($listas)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-300">Nenhuma lista encontrada</h3>
            <p class="mt-1 text-sm text-gray-400">Comece criando sua primeira lista de contatos.</p>
            <div class="mt-6">
                <button onclick="openModal('modal-import')" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nova Lista
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-700">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Contatos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Validação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Data de Criação</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($listas as $lista): ?>
                        <tr class="hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($lista['nome']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo number_format($lista['total_contatos']); ?> contatos
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($lista['validacao_concluida'] == 1): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✓ <?php echo number_format($lista['total_ativos']); ?> validados
                                    </span>
                                <?php elseif ($lista['validacao_iniciada'] == 1): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        ⏳ Validando...
                                    </span>
                                <?php else: ?>
                                    <button onclick="validarLista(<?php echo $lista['id']; ?>)" 
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200">
                                        Validar (<?php echo number_format($lista['total_contatos']); ?> créditos)
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                <?php echo date('d/m/Y H:i', strtotime($lista['data_criacao'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button onclick="verContatos(<?php echo $lista['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300">
                                    Ver Contatos
                                </button>
                                <button onclick="excluirLista(<?php echo $lista['id']; ?>)" 
                                        class="text-red-400 hover:text-red-300">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>