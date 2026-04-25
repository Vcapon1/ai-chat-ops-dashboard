<?php
function getJavascript() {
    ob_start();
?>
<script>
<?php include 'javascript/notifications.js'; ?>
<?php include 'javascript/status_tag_handlers.js'; ?>
<?php include 'javascript/note_form_handler.js'; ?>
<?php include 'javascript/message_modal.js'; ?>
<?php include 'javascript/styles.js'; ?>
<?php include 'javascript/main.js'; ?>
</script>
<?php
    return ob_get_clean();
}

// Keep the existing function for backward compatibility but update its name
function getStatusTagJavascript() {
    return getJavascript();
}
?>
