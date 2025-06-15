/**
 * Utility JavaScript for Quantum-Resistant Data Vault
 */

/**
 * Show error message
 * @param {string} message - The error message to display
 */
function showError(message) {
    const $errorAlert = $('#error-alert');
    $errorAlert.text(message);
    $errorAlert.show();
    
    // Hide after 5 seconds
    setTimeout(() => {
        $errorAlert.hide();
    }, 5000);
}

/**
 * Show success message
 * @param {string} message - The success message to display
 */
function showSuccess(message) {
    const $successAlert = $('#success-alert');
    $successAlert.text(message);
    $successAlert.show();
    
    // Hide after 5 seconds
    setTimeout(() => {
        $successAlert.hide();
    }, 5000);
}

/**
 * Initialize modals
 */
function initModals() {
    // Close modal when clicking the close button or outside the modal
    $('.modal-close, .modal-background').on('click', function() {
        $(this).closest('.modal').hide();
    });
    
    // Prevent modal content clicks from closing the modal
    $('.modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Show login modal
    $('#login-btn').on('click', function() {
        $('#login-modal').show();
    });
    
    // Show register modal
    $('#register-btn').on('click', function() {
        $('#register-modal').show();
    });
    
    // Show update profile modal
    $('#update-profile-btn').on('click', function() {
        // Pre-fill form with current user data
        const user = JSON.parse(sessionStorage.getItem('user'));
        if (user) {
            $('#update-first-name').val(user.first_name);
            $('#update-last-name').val(user.last_name);
        }
        
        $('#update-profile-modal').show();
    });
}

/**
 * Update UI for authenticated user
 */
function updateUIForAuthenticatedUser() {
    const user = JSON.parse(sessionStorage.getItem('user'));
    
    // Show authenticated elements
    $('.authenticated').show();
    $('.unauthenticated').hide();
    
    // Update user name
    $('#user-name').text(user.first_name + ' ' + user.last_name);
    
    // Check biometric status
    checkBiometricStatus();
    
    // Load user data
    loadUserDataKeys();
    loadUserNFTToken();
    loadUserActivityLogs();
}

/**
 * Update UI for logged out user
 */
function updateUIForLoggedOutUser() {
    // Show unauthenticated elements
    $('.authenticated').hide();
    $('.unauthenticated').show();
    
    // Clear user name
    $('#user-name').text('');
}

/**
 * Check authentication status on page load
 */
function checkAuthStatus() {
    const token = sessionStorage.getItem('token');
    const user = sessionStorage.getItem('user');
    
    if (token && user) {
        // Set global variables
        currentUser = JSON.parse(user);
        isAuthenticated = true;
        
        // Update UI
        updateUIForAuthenticatedUser();
    } else {
        // Clear any lingering data
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('user');
        
        // Set global variables
        currentUser = null;
        isAuthenticated = false;
        
        // Update UI
        updateUIForLoggedOutUser();
    }
}

/**
 * Format file size for display
 * @param {number} bytes - The file size in bytes
 * @returns {string} Formatted file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Generate a random string of specified length
 * @param {number} length - The length of the string to generate
 * @returns {string} Random string
 */
function generateRandomString(length = 16) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    return result;
}

/**
 * Truncate text to specified length
 * @param {string} text - The text to truncate
 * @param {number} maxLength - The maximum length
 * @returns {string} Truncated text
 */
function truncateText(text, maxLength = 50) {
    if (!text || text.length <= maxLength) return text;
    
    return text.substring(0, maxLength) + '...';
}