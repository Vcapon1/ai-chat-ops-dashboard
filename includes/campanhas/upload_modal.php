<!-- Modal de Upload de Lista com Opções de Validação -->
<div id="modal-import" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-gray-800">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-white">Nova Lista de Contatos</h3>
                <button onclick="closeModal('modal-import')" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="form-import" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="nome_lista" class="block text-sm font-medium text-gray-300 mb-2">Nome da Lista</label>
                    <input type="text" name="nome_lista" id="nome_lista" required 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ex: Leads Janeiro 2024">
                </div>
                
                <div>
                    <label for="arquivo_csv" class="block text-sm font-medium text-gray-300 mb-2">Arquivo CSV</label>
                    <input type="file" name="arquivo_csv" id="arquivo_csv" accept=".csv" required 
                           class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="mt-1 text-sm text-gray-400">Formato: nome, telefone (uma linha por contato)</p>
                </div>
                
                <!-- Preview e Mapeamento de Colunas (inicialmente oculto) -->
                <div id="csv-preview" class="hidden">
                    <div class="bg-gray-700 p-4 rounded-lg">
                        <h4 class="text-md font-medium text-white mb-3">Mapeamento de Colunas</h4>
                        <p class="text-sm text-gray-400 mb-3">Configure como cada coluna do seu CSV deve ser interpretada:</p>
                        
                        <div id="column-mapping" class="space-y-3">
                            <!-- Mapeamento será gerado dinamicamente pelo JavaScript -->
                        </div>
                    </div>
                </div>
                
                <!-- Opções de Validação -->
                <div class="bg-gray-700 p-4 rounded-lg">
                    <h4 class="text-md font-medium text-white mb-3">Opções de Validação</h4>
                    
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="validar_formato" name="validar_formato" value="true" checked
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded bg-gray-600">
                            <label for="validar_formato" class="ml-2 block text-sm text-gray-300">
                                Validar formato dos números para Brasil
                                <span class="text-gray-400 text-xs block">Adiciona código 55 se necessário e corrige formato brasileiro</span>
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="validar_whatsapp" name="validar_whatsapp" value="true"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded bg-gray-600">
                            <label for="validar_whatsapp" class="ml-2 block text-sm text-gray-300">
                                Validar existência no WhatsApp
                                <span class="text-gray-400 text-xs block">
                                    🪙 <strong>1 crédito por número validado</strong> - Recomendado para listas até 1.000 contatos
                                </span>
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="validar_nome_ia" name="validar_nome_ia" value="true"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded bg-gray-600">
                            <label for="validar_nome_ia" class="ml-2 block text-sm text-gray-300">
                                Validar nome real por IA
                                <span class="text-gray-400 text-xs block">
                                    🪙 <strong>1 crédito por nome validado</strong> - Detecta nomes reais vs. empresas/apelidos
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Aviso de Créditos -->
                    <div class="mt-3 p-3 bg-yellow-900 bg-opacity-50 border border-yellow-700 rounded-md">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <div class="text-sm text-yellow-300">
                                <p class="font-medium">Consumo de Créditos:</p>
                                <ul class="mt-1 text-xs space-y-1">
                                    <li>• Validação WhatsApp: 1 crédito por número</li>
                                    <li>• Validação nome IA: 1 crédito por nome</li>
                                    <li>• Envio de mensagem: 3 créditos por disparo</li>
                                    <li>• Mensagem personalizada IA: 2 créditos extras</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal('modal-import')" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <span class="loading-text">Importar Lista</span>
                        <span class="loading-spinner hidden">
                            <svg class="animate-spin h-4 w-4 text-white inline-block mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>