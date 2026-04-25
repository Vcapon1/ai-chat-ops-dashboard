
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Buscar dados atuais da integração CV
try {
    $stmt = $pdo->prepare("SELECT * FROM integrators_cv WHERE id_session = ?");
    $stmt->execute([$user_id]);
    $cv_config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

// Buscar dados atuais da integração Meta
try {
    $stmt = $pdo->prepare("SELECT * FROM integrators_meta WHERE id_session = ?");
    $stmt->execute([$user_id]);
    $meta_config = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados Meta: " . $e->getMessage();
}

// Buscar templates de mensagem
try {
    $stmt = $pdo->prepare("SELECT * FROM message_templates WHERE id_session = ? ORDER BY template_name");
    $stmt->execute([$user_id]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $templates = [];
}

// Atualizar configurações de integração CV
if (isset($_POST['update_cv_config'])) {
    try {
        // Verificar se já existe configuração
        $stmt = $pdo->prepare("SELECT id FROM integrators_cv WHERE id_session = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // Atualizar configuração existente
            $stmt = $pdo->prepare("UPDATE integrators_cv SET 
                url = ?, 
                key_token = ?, 
                cod_emp = ?,
                email = ?,
                midia_principal = ?,
                conversao = ?
                WHERE id_session = ?");
            $stmt->execute([
                $_POST['url'],
                $_POST['key_token'],
                $_POST['cod_emp'],
                $_POST['email'],
                $_POST['midia_principal'],
                $_POST['conversao'],
                $user_id
            ]);
        } else {
            // Inserir nova configuração
            $stmt = $pdo->prepare("INSERT INTO integrators_cv (
                id_session, url, key_token, cod_emp, email, midia_principal, conversao
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $_POST['url'],
                $_POST['key_token'],
                $_POST['cod_emp'],
                $_POST['email'],
                $_POST['midia_principal'],
                $_POST['conversao']
            ]);
        }
        
        $success_message = "Configurações de integração atualizadas com sucesso!";
        
        // Recarregar dados após atualização
        $stmt = $pdo->prepare("SELECT * FROM integrators_cv WHERE id_session = ?");
        $stmt->execute([$user_id]);
        $cv_config = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar configurações: " . $e->getMessage();
    }
}

// Atualizar configurações de integração Meta
if (isset($_POST['update_meta_config'])) {
    try {
        // Verificar se já existe configuração
        $stmt = $pdo->prepare("SELECT id FROM integrators_meta WHERE id_session = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            // Atualizar configuração existente
            $stmt = $pdo->prepare("UPDATE integrators_meta SET 
                access_token = ?, 
                phone_id = ?, 
                whatsapp_business_account_id = ?,
                webhook_token = ?,
                app_secret = ?
                WHERE id_session = ?");
            $stmt->execute([
                $_POST['access_token'],
                $_POST['phone_id'],
                $_POST['whatsapp_business_account_id'],
                $_POST['webhook_token'] ?? null,
                $_POST['app_secret'] ?? null,
                $user_id
            ]);
        } else {
            // Inserir nova configuração
            $stmt = $pdo->prepare("INSERT INTO integrators_meta (
                id_session, access_token, phone_id, whatsapp_business_account_id, webhook_token, app_secret
            ) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $_POST['access_token'],
                $_POST['phone_id'],
                $_POST['whatsapp_business_account_id'],
                $_POST['webhook_token'] ?? null,
                $_POST['app_secret'] ?? null
            ]);
        }
        
        // Atualizar tipo de API na sessão
        $stmt = $pdo->prepare("UPDATE sessions SET api_type = 'meta' WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $success_message = "Configurações da Meta API atualizadas com sucesso!";
        
        // Recarregar dados após atualização
        $stmt = $pdo->prepare("SELECT * FROM integrators_meta WHERE id_session = ?");
        $stmt->execute([$user_id]);
        $meta_config = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar configurações Meta: " . $e->getMessage();
    }
}

// Criar novo template
if (isset($_POST['create_template'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO message_templates (
            id_session, template_name, template_language, template_category, template_body, template_parameters
        ) VALUES (?, ?, ?, ?, ?, ?)");
        
        $parameters = null;
        if (!empty($_POST['template_parameters'])) {
            $parameters = json_encode(explode(',', $_POST['template_parameters']));
        }
        
        $stmt->execute([
            $user_id,
            $_POST['template_name'],
            $_POST['template_language'] ?? 'pt_BR',
            $_POST['template_category'] ?? 'MARKETING',
            $_POST['template_body'],
            $parameters
        ]);
        
        $success_message = "Template criado com sucesso!";
        
        // Recarregar templates
        $stmt = $pdo->prepare("SELECT * FROM message_templates WHERE id_session = ? ORDER BY template_name");
        $stmt->execute([$user_id]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "Erro ao criar template: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    
    <div class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-white">Configurações de Integração</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <!-- Abas de Navegação -->
                <div class="flex space-x-1 rounded-xl bg-gray-900 p-1 mb-6">
                    <button onclick="showTab('cv')" id="tab-cv" class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 text-blue-500 bg-gray-700 shadow">
                        Integração CV
                    </button>
                    <button onclick="showTab('meta')" id="tab-meta" class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 text-gray-400 hover:text-white hover:bg-gray-700">
                        Meta API (WhatsApp)
                    </button>
                    <button onclick="showTab('templates')" id="tab-templates" class="w-full rounded-lg py-2.5 text-sm font-medium leading-5 text-gray-400 hover:text-white hover:bg-gray-700">
                        Templates
                    </button>
                </div>

                <!-- Aba CV -->
                <div id="content-cv" class="tab-content">
                    <h2 class="text-xl font-semibold text-white mb-4">Integração CV</h2>
                    
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="url" class="block text-sm font-medium text-gray-300 mb-1">URL da API</label>
                                <input 
                                    type="text" 
                                    name="url" 
                                    id="url" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($cv_config['url'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="key_token" class="block text-sm font-medium text-gray-300 mb-1">Token</label>
                                <input 
                                    type="text" 
                                    name="key_token" 
                                    id="key_token" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($cv_config['key_token'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="cod_emp" class="block text-sm font-medium text-gray-300 mb-1">Código do Empreendimento</label>
                                <input 
                                    type="text" 
                                    name="cod_emp" 
                                    id="cod_emp" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($cv_config['cod_emp'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    id="email" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($cv_config['email'] ?? ''); ?>"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="midia_principal" class="block text-sm font-medium text-gray-300 mb-1">Mídia Principal</label>
                                <input 
                                    type="text" 
                                    name="midia_principal" 
                                    id="midia_principal" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($cv_config['midia_principal'] ?? 'Bot WhatsApp'); ?>"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="conversao" class="block text-sm font-medium text-gray-300 mb-1">Conversão</label>
                                <input 
                                    type="text" 
                                    name="conversao" 
                                    id="conversao" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($cv_config['conversao'] ?? 'Mídia Paga | Bot WhatsApp'); ?>"
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                name="update_cv_config" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                            >
                                Salvar Configurações CV
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Aba Meta API -->
                <div id="content-meta" class="tab-content hidden">
                    <h2 class="text-xl font-semibold text-white mb-4">Meta API (WhatsApp Business)</h2>
                    
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="access_token" class="block text-sm font-medium text-gray-300 mb-1">Access Token</label>
                                <input 
                                    type="text" 
                                    name="access_token" 
                                    id="access_token" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($meta_config['access_token'] ?? ''); ?>"
                                    placeholder="EAAxxxxxxxxxx..."
                                    required
                                >
                                <p class="text-xs text-gray-400 mt-1">Token de acesso permanente da sua aplicação Meta</p>
                            </div>
                            
                            <div>
                                <label for="phone_id" class="block text-sm font-medium text-gray-300 mb-1">Phone Number ID</label>
                                <input 
                                    type="text" 
                                    name="phone_id" 
                                    id="phone_id" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($meta_config['phone_id'] ?? ''); ?>"
                                    placeholder="1234567890123456"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="whatsapp_business_account_id" class="block text-sm font-medium text-gray-300 mb-1">WhatsApp Business Account ID</label>
                                <input 
                                    type="text" 
                                    name="whatsapp_business_account_id" 
                                    id="whatsapp_business_account_id" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($meta_config['whatsapp_business_account_id'] ?? ''); ?>"
                                    placeholder="1234567890123456"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="webhook_token" class="block text-sm font-medium text-gray-300 mb-1">Webhook Token (opcional)</label>
                                <input 
                                    type="text" 
                                    name="webhook_token" 
                                    id="webhook_token" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($meta_config['webhook_token'] ?? ''); ?>"
                                    placeholder="token_secreto_webhook"
                                >
                            </div>
                            
                            <div>
                                <label for="app_secret" class="block text-sm font-medium text-gray-300 mb-1">App Secret (opcional)</label>
                                <input 
                                    type="text" 
                                    name="app_secret" 
                                    id="app_secret" 
                                    class="w-full bg-gray-700 border border-gray-600 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    value="<?php echo htmlspecialchars($meta_config['app_secret'] ?? ''); ?>"
                                    placeholder="abc123def456..."
                                >
                            </div>
                        </div>
                        
                        <div class="bg-blue-500/10 border-l-4 border-blue-500 p-4">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm text-blue-300">
                                        <strong>Importante:</strong> Para usar a Meta API, você precisará de templates aprovados pelo WhatsApp para enviar mensagens.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                name="update_meta_config" 
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition"
                            >
                                Salvar Configurações Meta
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Aba Templates -->
                <div id="content-templates" class="tab-content hidden">
                    <h2 class="text-xl font-semibold text-white mb-4">Templates de Mensagem</h2>
                    
                    <!-- Formulário para criar novo template -->
                    <div class="bg-gray-700 rounded-lg p-4 mb-6">
                        <h3 class="text-lg font-medium text-white mb-4">Criar Novo Template</h3>
                        <form method="POST" action="" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="template_name" class="block text-sm font-medium text-gray-300 mb-1">Nome do Template</label>
                                    <input 
                                        type="text" 
                                        name="template_name" 
                                        id="template_name" 
                                        class="w-full bg-gray-600 border border-gray-500 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                        placeholder="ex: reativacao_lead"
                                        required
                                    >
                                </div>
                                
                                <div>
                                    <label for="template_language" class="block text-sm font-medium text-gray-300 mb-1">Idioma</label>
                                    <select name="template_language" id="template_language" class="w-full bg-gray-600 border border-gray-500 rounded-md py-2 px-3 text-white">
                                        <option value="pt_BR">Português (Brasil)</option>
                                        <option value="en_US">English (US)</option>
                                        <option value="es_ES">Español</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="template_category" class="block text-sm font-medium text-gray-300 mb-1">Categoria</label>
                                    <select name="template_category" id="template_category" class="w-full bg-gray-600 border border-gray-500 rounded-md py-2 px-3 text-white">
                                        <option value="MARKETING">Marketing</option>
                                        <option value="UTILITY">Utility</option>
                                        <option value="AUTHENTICATION">Authentication</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label for="template_body" class="block text-sm font-medium text-gray-300 mb-1">Corpo da Mensagem</label>
                                <textarea 
                                    name="template_body" 
                                    id="template_body" 
                                    rows="4"
                                    class="w-full bg-gray-600 border border-gray-500 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    placeholder="Olá {{1}}, temos uma oferta especial para você!"
                                    required
                                ></textarea>
                                <p class="text-xs text-gray-400 mt-1">Use {{1}}, {{2}}, etc. para parâmetros variáveis</p>
                            </div>
                            
                            <div>
                                <label for="template_parameters" class="block text-sm font-medium text-gray-300 mb-1">Parâmetros (opcional)</label>
                                <input 
                                    type="text" 
                                    name="template_parameters" 
                                    id="template_parameters" 
                                    class="w-full bg-gray-600 border border-gray-500 rounded-md py-2 px-3 text-white placeholder-gray-400" 
                                    placeholder="nome,produto,preco"
                                >
                                <p class="text-xs text-gray-400 mt-1">Separe os nomes dos parâmetros por vírgula</p>
                            </div>
                            
                            <div class="flex justify-end">
                                <button 
                                    type="submit" 
                                    name="create_template" 
                                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition"
                                >
                                    Criar Template
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Lista de templates existentes -->
                    <?php if (!empty($templates)): ?>
                        <div class="bg-gray-700 rounded-lg overflow-hidden">
                            <div class="px-6 py-4 bg-gray-600">
                                <h3 class="text-lg font-medium text-white">Templates Criados</h3>
                            </div>
                            <div class="divide-y divide-gray-600">
                                <?php foreach ($templates as $template): ?>
                                    <div class="px-6 py-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h4 class="text-sm font-medium text-white"><?php echo htmlspecialchars($template['template_name']); ?></h4>
                                                <p class="text-sm text-gray-400 mt-1"><?php echo htmlspecialchars($template['template_body']); ?></p>
                                                <div class="flex items-center space-x-4 mt-2">
                                                    <span class="text-xs text-gray-500">Idioma: <?php echo htmlspecialchars($template['template_language']); ?></span>
                                                    <span class="text-xs text-gray-500">Categoria: <?php echo htmlspecialchars($template['template_category']); ?></span>
                                                    <span class="text-xs px-2 py-1 rounded-full <?php echo $template['template_status'] === 'APPROVED' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'; ?>">
                                                        <?php echo htmlspecialchars($template['template_status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400">
                                <p class="text-sm">Nenhum template criado ainda.</p>
                                <p class="text-xs mt-1">Crie templates para usar com a Meta API.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function showTab(tabName) {
                // Esconder todos os conteúdos
                const contents = document.querySelectorAll('.tab-content');
                contents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Remover classe ativa de todas as abas
                const tabs = document.querySelectorAll('[id^="tab-"]');
                tabs.forEach(tab => {
                    tab.classList.remove('bg-gray-700', 'text-blue-500', 'shadow');
                    tab.classList.add('text-gray-400', 'hover:text-white', 'hover:bg-gray-700');
                });
                
                // Mostrar conteúdo da aba selecionada
                document.getElementById('content-' + tabName).classList.remove('hidden');
                
                // Ativar aba selecionada
                const activeTab = document.getElementById('tab-' + tabName);
                activeTab.classList.add('bg-gray-700', 'text-blue-500', 'shadow');
                activeTab.classList.remove('text-gray-400', 'hover:text-white', 'hover:bg-gray-700');
            }
            </script>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
