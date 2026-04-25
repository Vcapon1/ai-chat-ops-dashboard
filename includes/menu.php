
<aside class="w-64 bg-gray-800 min-h-screen">
    <nav class="mt-5 px-2">
        <a href="index.php?page=dashboard" class="group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>
        <a href="index.php?page=conversas" class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Conversas
        </a>
        <a href="index.php?page=leads" class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Leads
        </a>
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']);
        $settings_class = $current_page === 'settings.php' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-700';
        $profile_class = $current_page === 'profile.php' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-700';
        $integracao_class = $current_page === 'integracao.php' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white hover:bg-gray-700';
        ?>
        <a href="<?php echo $current_page === 'profile.php' ? '#' : 'profile.php'; ?>" class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md <?php echo $profile_class; ?> focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Perfil
        </a>
        <a href="<?php echo $current_page === 'settings.php' ? '#' : 'settings.php'; ?>" class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md <?php echo $settings_class; ?> focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Configurações
        </a>
        <a href="<?php echo $current_page === 'integracao.php' ? '#' : 'integracao.php'; ?>" class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md <?php echo $integracao_class; ?> focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Integração
        </a>
        <a href="campanhas.php" class="mt-1 group flex items-center px-2 py-2 text-base leading-6 font-medium rounded-md text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none focus:bg-gray-700 transition ease-in-out duration-150">
            <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a2 2 0 012-2h2a2 2 0 012 2v6a3 3 0 01-3 3z"/>
            </svg>
            Campanhas
        </a>
    </nav>
</aside>
