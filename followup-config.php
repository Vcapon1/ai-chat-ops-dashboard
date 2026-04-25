
<?php
session_start();
require_once 'includes/db.php';

// Se não está logado, redireciona
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Carrega dados atuais
try {
    $stmt = $pdo->prepare("SELECT fu_20m, fu_20m_prompt, fu_1d, fu_1d_prompt, fu_15d, fu_15d_prompt FROM sessions WHERE id = ?");
    $stmt->execute([$user_id]);
    $followup = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

// Atualiza caso haja POST
if (isset($_POST['update_followup'])) {
    try {
        $stmt = $pdo->prepare("UPDATE sessions SET 
            fu_20m = ?, 
            fu_20m_prompt = ?, 
            fu_1d = ?, 
            fu_1d_prompt = ?, 
            fu_15d = ?, 
            fu_15d_prompt = ?
            WHERE id = ?");
        $stmt->execute([
            isset($_POST['fu_20m']) ? 1 : 0,
            trim($_POST['fu_20m_prompt']),
            isset($_POST['fu_1d']) ? 1 : 0,
            trim($_POST['fu_1d_prompt']),
            isset($_POST['fu_15d']) ? 1 : 0,
            trim($_POST['fu_15d_prompt']),
            $user_id
        ]);
        $success_message = "Follow Up atualizado com sucesso!";
        // Recarrega os dados
        $stmt = $pdo->prepare("SELECT fu_20m, fu_20m_prompt, fu_1d, fu_1d_prompt, fu_15d, fu_15d_prompt FROM sessions WHERE id = ?");
        $stmt->execute([$user_id]);
        $followup = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erro ao atualizar: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
?>

<div class="flex min-h-screen bg-gray-900">
    <?php require_once 'includes/menu.php'; ?>
    <div class="flex-1 p-8">
        <div class="max-w-3xl mx-auto space-y-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-white">Configurações — Follow Up</h1>
                <?php 
                  require_once 'includes/settings/navigation_buttons.php';
                  renderNavigationButtons('followup');
                ?>
            </div>
            <?php if ($success_message): ?>
                <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-2 rounded">
                  <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-2 rounded">
                  <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-8 bg-gray-800 p-6 rounded-lg ">
                <input type="hidden" name="update_followup" value="1" />

                <!-- 20 MIN -->
                <div>
                    <label class="flex items-center space-x-4">
                        <input type="checkbox" name="fu_20m" value="1" 
                            <?= !empty($followup['fu_20m']) ? 'checked' : '' ?> 
                            class="form-checkbox h-5 w-5 text-green-500">
                        <span class="text-white font-medium">Ativar Follow Up de 20 minutos</span>
                    </label>
                    <textarea name="fu_20m_prompt" rows="2" class="mt-2 w-full rounded px-2 py-1 bg-gray-700 text-white" placeholder="Instrução para Follow Up de 20 minutos"><?= htmlspecialchars($followup['fu_20m_prompt'] ?? '') ?></textarea>
                </div>

                <!-- 1 DIA -->
                <div>
                    <label class="flex items-center space-x-4">
                        <input type="checkbox" name="fu_1d" value="1" 
                            <?= !empty($followup['fu_1d']) ? 'checked' : '' ?> 
                            class="form-checkbox h-5 w-5 text-green-500">
                        <span class="text-white font-medium">Ativar Follow Up de 1 dia</span>
                    </label>
                    <textarea name="fu_1d_prompt" rows="2" class="mt-2 w-full rounded px-2 py-1 bg-gray-700 text-white" placeholder="Instrução para Follow Up de 1 dia"><?= htmlspecialchars($followup['fu_1d_prompt'] ?? '') ?></textarea>
                </div>
                
                <!-- 15 DIAS -->
                <div>
                    <label class="flex items-center space-x-4">
                        <input type="checkbox" name="fu_15d" value="1"
                            <?= !empty($followup['fu_15d']) ? 'checked' : '' ?> 
                            class="form-checkbox h-5 w-5 text-green-500">
                        <span class="text-white font-medium">Ativar Follow Up de 15 dias</span>
                    </label>
                    <textarea name="fu_15d_prompt" rows="2" class="mt-2 w-full rounded px-2 py-1 bg-gray-700 text-white" placeholder="Instrução para Follow Up de 15 dias"><?= htmlspecialchars($followup['fu_15d_prompt'] ?? '') ?></textarea>
                </div>

                <div>
                    <button type="submit" class="px-6 py-2 rounded bg-green-500 hover:bg-green-600 text-white font-bold transition-colors">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
