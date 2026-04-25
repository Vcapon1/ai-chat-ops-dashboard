<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mar.IA - Inteligência de Negócios</title>
    <link rel="icon" type="image/png" href="/lovable-uploads/7b59c624-1595-4389-b475-b34578eabe44.png" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900">
    <header class="bg-gray-800 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <img 
                        src="/lovable-uploads/4073a9a7-ae09-48a9-b9d3-fbc7b8b3d626.png" 
                        alt="Mar.IA Intelligence" 
                        class="h-8 w-auto"
                    />
                    <h1 class="text-white text-xl font-bold">Mar.IA Intelligence</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <?php 
                    require_once __DIR__ . '/credits_manager.php';
                    $creditos = obterCreditos($pdo, $_SESSION['user_id']);
                    ?>
                    <div class="flex items-center bg-gradient-to-r from-green-600 to-green-500 text-white px-4 py-2 rounded-lg shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        <span class="font-bold"><?php echo number_format($creditos); ?></span>
                        <span class="ml-1 text-sm opacity-90">créditos</span>
                    </div>
                    <span class="text-gray-300">Olá, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                    <a href="logout.php" class="text-red-400 hover:text-red-300">Sair</a>
                </div>
            </div>
        </div>
    </header>