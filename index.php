
<?php
session_start();

// Pega a página requisitada via GET
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Roteamento
switch ($page) {
    case 'dashboard':
        require 'dashboard.php';
        break;
    case 'conversas':
        require 'conversas.php';
        break;
    case 'leads':
        require 'leads.php';
        break;
    case 'settings':
        require 'settings.php';
        break;
    case 'profile':
        require 'profile.php';
        break;
    case 'sdr-config':
        require 'sdr-config.php';
        break;
    case 'duvidas-config':
        require 'duvidas-config.php';
        break;
    case 'objecoes-config':
        require 'objecoes-config.php';
        break;
    case 'images-config':
        require 'images-config.php';
        break;
    case 'intencoes-config':
        require 'intencoes-config.php';
        break;
    case 'campanhas':
        require 'campanhas.php';
        break;
    default:
        // Página 404
        header("HTTP/1.0 404 Not Found");
        require 'pages/404.php';
        break;
}
