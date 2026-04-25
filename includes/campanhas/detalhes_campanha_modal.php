  
  <!-- Modal para Detalhes da Campanha -->
  <div id="modal-detalhes-campanha" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
      <div class="flex items-center justify-center min-h-screen p-4">
          <div class="bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center p-6 border-b border-gray-700">
                  <h2 class="text-xl font-bold text-white">Detalhes da Campanha</h2>
                  <button onclick="closeModal('modal-detalhes-campanha')" class="text-gray-400 hover:text-white">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                  </button>
              </div>
  
              <div class="p-6">
                  <div id="detalhes-content">
                      <!-- O conteúdo será carregado dinamicamente via JavaScript -->
                  </div>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Template para Campanha Agendada/Aguardando -->
  <template id="template-campanha-agendada">
      <div class="space-y-6">
          <!-- Cabeçalho com Status -->
          <div class="bg-gray-700 rounded-lg p-4">
              <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-white campanha-nome"></h3>
                  <span class="campanha-status-badge"></span>
              </div>
          </div>
  
          <!-- Grid de Informações -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Informações Básicas -->
              <div class="bg-gray-700 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-300 mb-3">📋 INFORMAÇÕES GERAIS</h4>
                  <div class="space-y-2">
                      <div class="flex justify-between">
                          <span class="text-gray-400">Lista:</span>
                          <span class="text-white campanha-lista"></span>
                      </div>
                      <div class="flex justify-between">
                          <span class="text-gray-400">Total de Contatos:</span>
                          <span class="text-white campanha-total-contatos"></span>
                      </div>
                      <div class="flex justify-between">
                          <span class="text-gray-400">Contatos Válidos:</span>
                          <span class="text-green-400 campanha-contatos-validos"></span>
                      </div>
                  </div>
              </div>
  
              <!-- Agendamento -->
              <div class="bg-gray-700 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-300 mb-3">⏰ AGENDAMENTO</h4>
                  <div class="space-y-2">
                      <div class="flex justify-between">
                          <span class="text-gray-400">Data Prevista:</span>
                          <span class="text-white campanha-data-prevista"></span>
                      </div>
                      <div class="flex justify-between">
                          <span class="text-gray-400">Horário:</span>
                          <span class="text-white campanha-horario"></span>
                      </div>
                      <div class="flex justify-between campanha-dias-container">
                          <span class="text-gray-400">Dias da Semana:</span>
                          <span class="text-white campanha-dias"></span>
                      </div>
                  </div>
              </div>
          </div>
  
          <!-- IA e Configurações -->
          <div class="bg-gray-700 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-300 mb-3">🤖 CONFIGURAÇÕES</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex justify-between">
                      <span class="text-gray-400">Usar IA:</span>
                      <span class="campanha-ia-status"></span>
                  </div>
                  <div class="flex justify-between">
                      <span class="text-gray-400">Criada em:</span>
                      <span class="text-white campanha-data-criacao"></span>
                  </div>
              </div>
          </div>
  
          <!-- Mensagem -->
          <div class="bg-gray-700 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-300 mb-3">💬 MENSAGEM</h4>
              <div class="bg-gray-800 rounded p-3">
                  <p class="text-gray-300 whitespace-pre-wrap campanha-mensagem"></p>
              </div>
          </div>
      </div>
  </template>
  
  <!-- Template para Campanha Enviada -->
  <template id="template-campanha-enviada">
      <div class="space-y-6">
          <!-- Cabeçalho com Status -->
          <div class="bg-gray-700 rounded-lg p-4">
              <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-white campanha-nome"></h3>
                  <span class="campanha-status-badge"></span>
              </div>
          </div>
  
          <!-- Estatísticas Principais -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-blue-600/20 border border-blue-500/30 rounded-lg p-4 text-center">
                  <div class="text-2xl font-bold text-blue-400 campanha-total-enviados"></div>
                  <div class="text-sm text-gray-300">Mensagens Enviadas</div>
              </div>
              <div class="bg-green-600/20 border border-green-500/30 rounded-lg p-4 text-center">
                  <div class="text-2xl font-bold text-green-400 campanha-total-responderam">-</div>
                  <div class="text-sm text-gray-300">Responderam</div>
                  <div class="text-xs text-gray-400">(Em desenvolvimento)</div>
              </div>
              <div class="bg-purple-600/20 border border-purple-500/30 rounded-lg p-4 text-center">
                  <div class="text-2xl font-bold text-purple-400 campanha-leads-gerados">-</div>
                  <div class="text-sm text-gray-300">Leads Gerados</div>
                  <div class="text-xs text-gray-400">(Em desenvolvimento)</div>
              </div>
          </div>
  
          <!-- Taxa de Entrega -->
          <div class="bg-gray-700 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-300 mb-3">📊 PERFORMANCE</h4>
              <div class="space-y-4">
                  <div>
                      <div class="flex justify-between text-sm mb-2">
                          <span class="text-gray-400">Taxa de Entrega</span>
                          <span class="text-white campanha-taxa-entrega"></span>
                      </div>
                      <div class="w-full bg-gray-600 rounded-full h-2">
                          <div class="bg-blue-500 h-2 rounded-full campanha-barra-entrega" style="width: 0%"></div>
                      </div>
                  </div>
              </div>
          </div>
  
          <!-- Informações da Campanha -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-gray-700 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-300 mb-3">📋 INFORMAÇÕES</h4>
                  <div class="space-y-2">
                      <div class="flex justify-between">
                          <span class="text-gray-400">Lista:</span>
                          <span class="text-white campanha-lista"></span>
                      </div>
                      <div class="flex justify-between">
                          <span class="text-gray-400">Enviada em:</span>
                          <span class="text-white campanha-data-envio"></span>
                      </div>
                      <div class="flex justify-between">
                          <span class="text-gray-400">Duração:</span>
                          <span class="text-white campanha-duracao">-</span>
                      </div>
                  </div>
              </div>
  
              <div class="bg-gray-700 rounded-lg p-4">
                  <h4 class="text-sm font-medium text-gray-300 mb-3">⚙️ CONFIGURAÇÕES</h4>
                  <div class="space-y-2">
                      <div class="flex justify-between">
                          <span class="text-gray-400">IA Utilizada:</span>
                          <span class="campanha-ia-status"></span>
                      </div>
                      <div class="flex justify-between">
                          <span class="text-gray-400">Criada em:</span>
                          <span class="text-white campanha-data-criacao"></span>
                      </div>
                  </div>
              </div>
          </div>
  
          <!-- Mensagem Enviada -->
          <div class="bg-gray-700 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-300 mb-3">💬 MENSAGEM ENVIADA</h4>
              <div class="bg-gray-800 rounded p-3">
                  <p class="text-gray-300 whitespace-pre-wrap campanha-mensagem"></p>
              </div>
          </div>
      </div>
  </template>
