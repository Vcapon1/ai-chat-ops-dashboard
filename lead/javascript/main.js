
/**
 * Main entry point for lead details JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    setupStatusTagHandlers();
    setupNoteFormHandler();
    setupMessageModal();
    addStyles();
    
    // Add console message for debugging
    console.log('Lead details JavaScript initialized successfully');
});
