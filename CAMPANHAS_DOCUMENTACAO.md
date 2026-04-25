# Documentação do Módulo de Campanhas

## 📋 Visão Geral
Sistema completo de gerenciamento de campanhas de reativação com controle de listas, disparo automatizado e rastreamento de resultados.

## 🗄️ Estrutura do Banco de Dados

### Tabela: `dis_campanhas`
Campos principais:
- `nome_tabela_envio`: Nome da tabela específica de envios (dis_envios_campanha_X)
- `data_disparo_inicio`: Início do processo de envio
- `data_disparo_fim`: Finalização do envio
- `cancelamento_ativo`: Se opt-out está habilitado

### Tabelas Dinâmicas: `dis_envios_campanha_X`
Uma tabela é criada para cada campanha com os campos:
- `telefone`, `nome`, `mensagem`: Dados do contato
- `enviado`, `lido`, `respondido`, `cancelado`: Status de entrega
- `data_envio`: Timestamp do envio

### Tabela: `dis_blacklist_optout`
Contatos que cancelaram o recebimento:
- `telefone`, `id_session`, `id_campanha`, `data_cancelamento`

## 🔧 Funcionalidades Implementadas

### ✅ Validação de Uso de Listas
- Listas usadas nos últimos 30 dias são bloqueadas automaticamente
- Interface mostra status visual das listas indisponíveis
- Validação no backend impede criação de campanhas com listas bloqueadas

### ✅ Criação Automática de Tabelas de Envio
Ao criar uma campanha:
1. Gera tabela `dis_envios_campanha_<id>`
2. Copia contatos da lista original
3. Exclui automaticamente contatos em blacklist
4. Atualiza quantidade programada na campanha

### ✅ Sistema de Blacklist Global
- Contatos que respondem com palavras de cancelamento são bloqueados
- Blacklist é respeitada em futuras campanhas
- Registro histórico de cancelamentos por sessão

### ✅ Relatórios Detalhados
Modal de relatório mostra:
- Total de contatos, enviados, lidos, respondidos, cancelados
- Taxas de conversão (entrega, leitura, resposta)
- Informações gerais da campanha

## 🚀 Scripts de Processamento

### `dispatcher_example.php`
Script para integração com sistemas externos (n8n, cron):
- Processa filas de envio de campanhas
- Atualiza status de entrega em tempo real
- Gerencia respostas e cancelamentos
- Controla delay entre envios

## 📡 APIs Disponíveis

### Endpoints AJAX:
- `get_listas`: Lista com validação de uso recente
- `create_campanha`: Criação com validação e geração de tabelas
- `get_relatorio_campanha`: Estatísticas detalhadas
- `marcar_cancelamento`: Registro de opt-out

## 🔄 Fluxo de Trabalho

### 1. Importação de Lista
Cliente importa CSV → Sistema cria `dis_lista_contatos_X`

### 2. Criação de Campanha
- Validação de lista disponível (30 dias)
- Criação de `dis_envios_campanha_X`
- Exclusão automática de blacklist
- Agendamento da campanha

### 3. Disparo (Sistema Externo)
- Script processa `dis_envios_campanha_X`
- Integra com WhatsApp/SMS API
- Atualiza status linha por linha
- Registra timestamps de envio

### 4. Processamento de Respostas
- Monitor de respostas em tempo real
- Detecção automática de cancelamentos
- Atualização de blacklist global
- Métricas de engajamento

## 🛡️ Segurança e Isolamento

### Por Cliente (id_session):
- Tabelas de contatos isoladas
- Campanhas separadas por sessão
- Blacklist específica por cliente
- Validações sempre incluem id_session

### Controles de Acesso:
- Validação de sessão em todas as operações
- Queries sempre filtradas por id_session
- Proteção contra acesso cruzado de dados

## 📊 Métricas Disponíveis

### Por Campanha:
- Taxa de entrega (enviados/programados)
- Taxa de leitura (lidos/enviados)
- Taxa de resposta (respondidos/enviados)
- Taxa de cancelamento (cancelados/enviados)

### Globais (Dashboard):
- Total de campanhas disparadas
- Volume total de mensagens
- Efetividade geral das campanhas
- Crescimento do blacklist

## 🔧 Configuração e Deploy

### 1. Executar Migração:
```
https://seudominio.com/execute_migration.php
```

### 2. Configurar Sistema de Disparo:
- Integrar `dispatcher_example.php` com n8n/cron
- Configurar webhook para respostas
- Definir intervalos de processamento

### 3. Monitoramento:
- Logs automáticos de erros
- Métricas em tempo real
- Alertas de problemas de entrega

## 🎯 Próximos Passos Sugeridos

1. **Integração WhatsApp Business API**
2. **Dashboard em tempo real** 
3. **Agendamento avançado** (horários específicos)
4. **Templates de mensagem** pré-definidos
5. **Análise de sentimento** das respostas
6. **Exportação de relatórios** em PDF/Excel