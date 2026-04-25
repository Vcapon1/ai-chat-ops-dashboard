
<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';
include 'includes/lead_card.php';
include 'includes/utils.php';
include 'includes/lead/lead_display.php'; // New file for display logic
include 'includes/lead/ajax_handlers.php'; // This includes the existing AJAX handlers

// Check for authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Process filters and retrieve leads
$leads_data = getLeadsData($pdo, $_SESSION['user_id'], $_GET);

// Extract data for display
$novosContatos = $leads_data['novosContatos'];
$leadsNoCRM = $leads_data['leadsNoCRM'];
$leadsDescartados = $leads_data['leadsDescartados'];

// Include the UI components
include 'includes/lead/ui_components.php';
?>
