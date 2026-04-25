<?php
session_start();

// Configuração do banco de dados
$db_config = [
    'host' => '10.180.0.150',
    'port' => '3306',
    'user' => 'vitor',
    'pass' => 'EduGMa8j9Q6pNwVX51Hh',
    'db'   => 'mariai'
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['db']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $hashed_password = md5($password);

    try {
        $stmt = $pdo->prepare("SELECT id, client_name, session_name FROM sessions WHERE username = :username AND password = :password");
        $stmt->execute([
            ':username' => $username,
            ':password' => $hashed_password
        ]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['client_name'];
            $_SESSION['session_name'] = $user['session_name'];
            
            $_SESSION['success_message'] = "Login realizado com sucesso! Redirecionando...";
            
            header('Location: conversas.php');
            exit;
        } else {
            $error = "Usuário ou senha inválidos";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Erro ao tentar fazer login. Por favor, tente novamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mar.IA Inteligência de Negócios</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md p-6 space-y-6 backdrop-blur-sm bg-white/10 rounded-lg shadow-xl">
            <div class="space-y-2 text-center">
                <h1 class="text-3xl font-bold tracking-tighter text-white">Mar.IA Inteligência de Negócios</h1>
                <p class="text-gray-400">Digite suas credenciais para acessar seu painel</p>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-2 rounded">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']); 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-2 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div class="space-y-2">
                    <input
                        type="text"
                        name="username"
                        placeholder="Usuário"
                        class="w-full px-3 py-2 bg-white/5 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>
                <div class="space-y-2">
                    <input
                        type="password"
                        name="password"
                        placeholder="Senha"
                        class="w-full px-3 py-2 bg-white/5 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                    Entrar
                </button>
            </form>

            <div class="pt-4 space-y-4">
                <div class="flex items-center justify-center space-x-4 text-sm text-gray-400">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span>Chat em tempo real</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span>Histórico</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>Gestão de clientes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
