
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/settings/prompts_form.php';
require_once 'includes/settings/navigation_buttons.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Buscar dados atuais da sessão
try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

// Atualizar configurações
if (isset($_POST['update_settings'])) {
    try {
        $stmt = $pdo->prepare("UPDATE sessions SET 
            prompt = ?, 
            prompt_saudacao = ?, 
            prompt_lead = ?,
            prompt_descarte = ?,
            assist_name = ?, 
            n_adm = ? 
            WHERE id = ?");
        $stmt->execute([
            $_POST['prompt'],
            $_POST['prompt_saudacao'],
            $_POST['prompt_lead'],
            $_POST['prompt_descarte'],
            $_POST['assist_name'],
            preg_replace('/\D/', '', $_POST['n_adm']), // Remove caracteres não numéricos
            $user_id
        ]);
        $success_message = "Configurações atualizadas com sucesso!";
        
        // Recarregar dados após atualização
        $stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
        $stmt->execute([$user_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar configurações: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    
    <div class="flex-1 p-8">
        <div class="max-w-4xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-white">Configurações</h1>
                <?php renderNavigationButtons(); ?>
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

            <?php renderPromptsForm($session); ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
