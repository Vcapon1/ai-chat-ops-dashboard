
/**
 * Show a notification message
 * @param {string} message - Message to display
 * @param {string} type - 'success' or 'error'
 */
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white px-4 py-2 rounded shadow-lg animate-fade-in z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.replace('animate-fade-in', 'animate-fade-out');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}
