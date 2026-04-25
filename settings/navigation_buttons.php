
<?php
function renderNavigationButtons($active = '') {
    $buttons = [
        'settings' => 'Configurações Básicas',
        'intencoes' => 'Intenções',
        'sdr' => 'Instruções SDR',
        'duvidas' => 'Dúvidas',
        'objecoes' => 'Objeções',
        'images' => 'Imagens',
        'followup' => 'Follow Up'
    ];
    
    echo '<div class="flex flex-wrap gap-2 mb-6">';
    
    foreach ($buttons as $key => $label) {
        $isActive = ($active == $key);
        $bgClass = $isActive ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600';
        
        echo '<a href="' . getPageUrl($key) . '" class="' . $bgClass . ' text-white px-4 py-2 rounded transition-colors">' . $label . '</a>';
    }
    
    echo '</div>';
}

function getPageUrl($key) {
    switch ($key) {
        case 'settings':
            return 'settings.php';
        case 'sdr':
            return 'sdr-config.php';
        case 'duvidas':
            return 'duvidas-config.php';
        case 'objecoes':
            return 'objecoes-config.php';
        case 'images':
            return 'images-config.php';
        case 'followup':
            return 'followup-config.php';
        case 'intencoes':
            return 'intencoes-config.php';
        default:
            return '#';
    }
}
?>
