
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/settings/navigation_buttons.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Buscar dados atuais do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

// Atualizar credenciais
if (isset($_POST['update_credentials'])) {
    $username = $_POST['username'];
    $email_p = $_POST['email_p'];

    try {
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->rowCount() > 0) {
            $error_message = "Este nome de usuário já está em uso";
        } else {
            $stmt = $pdo->prepare("UPDATE sessions SET username = ?, email_p = ? WHERE id = ?");
            $stmt->execute([$username, $email_p, $user_id]);
            $success_message = "Dados atualizados com sucesso!";
            
            // Recarregar dados após atualização
            $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
            $stmt->execute([$user_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar credenciais: " . $e->getMessage();
    }
}

// Atualizar senha
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_message = "As senhas não coincidem";
    } else {
        $hashed_current = md5($current_password);
        $stmt = $pdo->prepare("SELECT id FROM sessions WHERE id = ? AND password = ?");
        $stmt->execute([$user_id, $hashed_current]);
        
        if ($stmt->rowCount() > 0) {
            $hashed_new = md5($new_password);
            $stmt = $pdo->prepare("UPDATE sessions SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_new, $user_id]);
            $success_message = "Senha atualizada com sucesso!";
        } else {
            $error_message = "Senha atual incorreta";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    
    <div class="flex-1 p-8">
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-white">Perfil do Usuário</h1>
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

            <div class="space-y-8">
                <!-- Dados do Usuário -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-semibold text-blue-200 mb-4">Dados do Usuário</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-300 mb-2">Nome de Usuário</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($session['username'] ?? ''); ?>" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Email de Contato</label>
                            <input type="email" name="email_p" value="<?php echo htmlspecialchars($session['email_p'] ?? ''); ?>" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                        </div>
                        <button type="submit" name="update_credentials" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            Atualizar Dados
                        </button>
                    </form>
                </div>

                <!-- Alterar Senha -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
                    <h2 class="text-xl font-semibold text-blue-200 mb-4">Alterar Senha</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-gray-300 mb-2">Senha Atual</label>
                            <input type="password" name="current_password" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Nova Senha</label>
                            <input type="password" name="new_password" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Confirmar Nova Senha</label>
                            <input type="password" name="confirm_password" 
                                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white" required>
                        </div>
                        <button type="submit" name="update_password" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            Atualizar Senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
