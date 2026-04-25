<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once 'includes/db.php';

// Get selected month and year (default to current)
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Buscar estatísticas do banco de dados
try {
    // Get available months for the selector
    $sql = "SELECT DISTINCT 
            MONTH(created_at) as month,
            YEAR(created_at) as year
            FROM clients 
            WHERE session_id = ?
            ORDER BY year DESC, month DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $available_months = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total de conversas do mês selecionado
    $sql = "SELECT COUNT(DISTINCT c.id) as total 
            FROM clients c
            WHERE c.session_id = ? 
            AND MONTH(c.created_at) = ?
            AND YEAR(c.created_at) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $selected_month, $selected_year]);
    $totalConversas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Conversas ativas (com interação no mês selecionado)
    $sql = "SELECT COUNT(DISTINCT c.id) as total
            FROM clients c
            INNER JOIN mensagens m ON m.client_id = c.id
            WHERE c.session_id = ?
            AND MONTH(m.created_at) = ?
            AND YEAR(m.created_at) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $selected_month, $selected_year]);
    $conversasAtivas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calcular porcentagem para a régua (máximo 500)
    $percentualAtivas = min(($conversasAtivas / 500) * 100, 100);

} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $totalConversas = 0;
    $conversasAtivas = 0;
    $percentualAtivas = 0;
    $available_months = [];
}

include 'includes/header.php';
?>

<div class="flex">
    <?php include 'includes/menu.php'; ?>

    <!-- Conteúdo Principal -->
    <main class="flex-1 p-8">
        <!-- Cabeçalho com Seletor de Mês -->
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-white">Dashboard</h2>
            <form method="GET" class="flex items-center gap-4">
                <select name="month" 
                        class="bg-gray-800 text-white border border-gray-700 rounded-lg px-4 py-2"
                        onchange="this.form.submit()">
                    <?php foreach ($available_months as $month): ?>
                        <?php 
                        $month_name = date('F Y', mktime(0, 0, 0, $month['month'], 1, $month['year']));
                        $selected = ($month['month'] == $selected_month && $month['year'] == $selected_year) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $month['month']; ?>" <?php echo $selected; ?>>
                            <?php echo $month_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Total de Conversas -->
            <div class="bg-gray-800 rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-white">Total de Conversas</h3>
                    <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <p class="mt-4 text-3xl font-bold text-white"><?php echo $totalConversas; ?></p>
                <p class="text-sm text-gray-400">
                    <?php echo date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?>
                </p>
            </div>

            <!-- Conversas Ativas -->
            <div class="bg-gray-800 rounded-lg shadow-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-white">Conversas Ativas</h3>
                    <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white mb-2"><?php echo $conversasAtivas; ?></p>
                <!-- Régua de Progresso -->
                <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
                    <div class="bg-green-600 h-2.5 rounded-full transition-all duration-300"
                         style="width: <?php echo $percentualAtivas; ?>%">
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-400">
                    <span>0</span>
                    <span>500 máx</span>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>