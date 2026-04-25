<?php
/**
 * Cron job para processar validação de WhatsApp
 * Deve ser executado a cada minuto
 */

require_once '../db.php';
require_once '../credits_manager.php';

// Log de início
error_log("=== INICIANDO VALIDAÇÃO WHATSAPP ===");

processarFilaValidacaoWhatsApp($pdo);

error_log("=== VALIDAÇÃO WHATSAPP FINALIZADA ===");
?>