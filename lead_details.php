
<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';
include 'includes/utils.php';
include 'includes/lead/lead_data.php';
include 'includes/lead/cards.php';
include 'includes/lead/styles.php';
include 'includes/lead/javascript.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: leads.php');
    exit;
}

$lead_id = $_GET['id'];
$session_id = $_SESSION['user_id'];

// Handle AJAX requests - handle this before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // Call ajax handlers and make sure to exit after processing
    include 'includes/lead/ajax_handlers.php';
    exit; // This is important to ensure no HTML is output after AJAX response
}

// Check if lead_criado and conversa_descartada columns exist, create if they don't
try {
    // Try to query to see if the columns exist
    $columnCheck = $pdo->query("SHOW COLUMNS FROM clients LIKE 'lead_criado'");
    if ($columnCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE clients ADD COLUMN lead_criado TINYINT(1) DEFAULT 0");
    }
    
    $columnCheck = $pdo->query("SHOW COLUMNS FROM clients LIKE 'conversa_descartada'");
    if ($columnCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE clients ADD COLUMN conversa_descartada TINYINT(1) DEFAULT 0");
    }
    
    // Check if lead_notes table exists, create if it doesn't
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'lead_notes'");
    if ($tableCheck->rowCount() == 0) {
        $pdo->exec("CREATE TABLE lead_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            session_id INT NOT NULL,
            note TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX (client_id, session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        error_log("Created lead_notes table");
    }
} catch (PDOException $e) {
    // Log error but continue execution
    error_log("Erro ao verificar/criar colunas ou tabelas: " . $e->getMessage());
}

// Get lead data
$lead = getLead($lead_id, $session_id, $pdo);
if (!$lead) {
    header('Location: leads.php');
    exit;
}

// Get SDR questions
$sdrQuestions = getSdrQuestions($lead_id, $session_id, $pdo);

// Get lead notes
$leadNotes = getLeadNotes($lead_id, $session_id, $pdo);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Detalhes do Lead - Dashboard</title>
    <?php echo getLeadDetailsStyles(); ?>
    <?php echo getJavascript(); ?>
</head>
<body class="bg-gray-900 text-white">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/menu.php'; ?>

        <!-- Conteúdo Principal -->
        <div class="flex-1 overflow-hidden">
            <!-- Header fixo -->
            <header class="bg-gray-800 shadow-lg h-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex justify-between items-center">
                    <h1 class="text-xl font-semibold text-blue-200">Detalhes do Lead</h1>
                    <a href="leads.php" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors">
                        Voltar para Lista
                    </a>
                </div>
            </header>

            <!-- Área de conteúdo com scroll -->
            <main class="scroll-container p-6 pb-24">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Cards -->
                    <?php echo renderMainCard($lead, 'formatPhoneNumber'); ?>
                    <?php echo renderIntentionCard($lead); ?>
                    <?php echo renderQuestionsCard($sdrQuestions); ?>
                    <?php echo renderNotesCard($leadNotes, $lead_id); ?>
                    <?php echo renderCadenceCard($lead); ?>
                    
                    <!-- Spacer element to ensure proper scrolling to the bottom -->
                    <div class="content-bottom-spacer h-20"></div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para mensagens -->
    <div id="mensagens-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-gray-800 rounded-lg w-11/12 max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b border-gray-700">
                <h3 class="text-xl font-semibold text-white">Mensagens</h3>
                <button id="fechar-modal" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4" id="mensagens-container">
                <p class="text-center text-gray-400">Carregando mensagens...</p>
            </div>
        </div>
    </div>
</body>
</html>
