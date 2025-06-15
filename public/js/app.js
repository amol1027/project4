/**
 * Main application JavaScript for Quantum-Resistant Data Vault
 */

// Global variables
let currentUser = null;
let isAuthenticated = false;

// DOM ready
$(document).ready(function() {
    // Initialize the application
    initApp();
    
    // Navigation event listeners
    setupNavigation();
    
    // Modal event listeners
    setupModals();
    
    // Check if user is already logged in (from session storage)
    checkAuthStatus();
});

/**
 * Initialize the application
 */
function initApp() {
    console.log('Initializing Quantum-Resistant Data Vault application...');
    
    // Set up dashboard navigation
    $('.dashboard-nav').on('click', function(e) {
        e.preventDefault();
        
        const section = $(this).data('section');
        
        // Update active nav
        $('.dashboard-nav').removeClass('active');
        $(this).addClass('active');
        
        // Show selected section
        $('.dashboard-section').removeClass('active');
        $(`#${section}-section`).addClass('active');
    });
    
    // Learn more button
    $('#learn-more-btn').on('click', function() {
        navigateTo('about');
    });
    
    // Get started button
    $('#get-started-btn').on('click', function() {
        if (isAuthenticated) {
            navigateTo('dashboard');
        } else {
            $('#register-modal').show();
        }
    });
}

/**
 * Set up navigation between pages
 */
function setupNavigation() {
    // Main navigation
    $('.nav-link').on('click', function(e) {
        e.preventDefault();
        
        const page = $(this).data('page');
        navigateTo(page);
    });
}

/**
 * Navigate to a specific page
 * @param {string} page - The page to navigate to
 */
function navigateTo(page) {
    // Hide all pages
    $('.page').removeClass('active');
    
    // Show selected page
    $(`#${page}-page`).addClass('active');
    
    // Update active nav
    $('.nav-link').removeClass('active');
    $(`.nav-link[data-page="${page}"]`).addClass('active');
    
    // Special case for dashboard - check authentication
    if (page === 'dashboard' && !isAuthenticated) {
        navigateTo('home');
        $('#login-modal').show();
        return;
    }
}

/**
 * Set up modal dialogs
 */
function setupModals() {
    // Open login modal
    $('#login-btn').on('click', function() {
        $('#login-modal').show();
    });
    
    // Open register modal
    $('#register-btn').on('click', function() {
        $('#register-modal').show();
    });
    
    // Close modals when clicking the X
    $('.close-modal').on('click', function() {
        $(this).closest('.modal').hide();
    });
    
    // Close modals when clicking outside
    $('.modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Update profile button
    $('#update-data-btn').on('click', function() {
        // Populate form with current user data
        if (currentUser) {
            $('#update-email').val(currentUser.email);
            $('#update-first-name').val(currentUser.first_name);
            $('#update-last-name').val(currentUser.last_name);
        }
        
        $('#update-profile-modal').show();
    });
    
    // Delete account button
    $('#delete-account-btn').on('click', function() {
        $('#delete-confirm-modal').show();
    });
    
    // Cancel delete
    $('#cancel-delete-btn').on('click', function() {
        $('#delete-confirm-modal').hide();
    });
}

/**
 * Check if user is already authenticated
 */
function checkAuthStatus() {
    const token = sessionStorage.getItem('token');
    const userData = sessionStorage.getItem('user');
    
    if (token && userData) {
        try {
            currentUser = JSON.parse(userData);
            isAuthenticated = true;
            updateUIForAuthenticatedUser();
            
            // Load user data
            loadUserData();
        } catch (e) {
            console.error('Error parsing stored user data:', e);
            logout();
        }
    }
}

/**
 * Update UI for authenticated user
 */
function updateUIForAuthenticatedUser() {
    // Update header
    $('#login-btn, #register-btn').hide();
    $('#user-menu').removeClass('hidden');
    $('#user-name').text(`${currentUser.first_name} ${currentUser.last_name}`);
    
    // Enable dashboard link
    $('#dashboard-link').parent().show();
    
    // Update biometric status
    if (currentUser.biometric_registered) {
        $('#biometric-status').text('Registered').css('color', 'green');
        $('#register-biometric-btn').text('Update Biometric');
    } else {
        $('#biometric-status').text('Not Registered').css('color', 'red');
    }
}

/**
 * Update UI for logged out user
 */
function updateUIForLoggedOutUser() {
    // Update header
    $('#login-btn, #register-btn').show();
    $('#user-menu').addClass('hidden');
    
    // Disable dashboard link
    $('#dashboard-link').parent().hide();
    
    // Navigate to home if on dashboard
    if ($('#dashboard-page').hasClass('active')) {
        navigateTo('home');
    }
}

/**
 * Load user data for dashboard
 */
function loadUserData() {
    // Load activity logs
    loadActivityLogs();
    
    // Load NFT token ID if available
    if (currentUser.nft_token_id) {
        $('#nft-token-id').text(currentUser.nft_token_id);
    }
}

/**
 * Load user activity logs
 */
function loadActivityLogs() {
    const token = sessionStorage.getItem('token');
    
    if (!token) return;
    
    $.ajax({
        url: '../api/gdpr.php?logs=true',
        type: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`
        },
        success: function(response) {
            if (response.success && response.logs) {
                displayActivityLogs(response.logs);
            }
        },
        error: function(xhr) {
            console.error('Error loading activity logs:', xhr.responseText);
        }
    });
}

/**
 * Display activity logs in the table
 * @param {Array} logs - The activity logs to display
 */
function displayActivityLogs(logs) {
    const tbody = $('#activity-logs-body');
    tbody.empty();
    
    if (logs.length === 0) {
        tbody.append('<tr><td colspan="3">No activity logs found</td></tr>');
        return;
    }
    
    logs.forEach(log => {
        const row = $('<tr></tr>');
        row.append(`<td>${formatAction(log.action)}</td>`);
        row.append(`<td>${formatTimestamp(log.timestamp)}</td>`);
        row.append(`<td>${log.ip_address}</td>`);
        tbody.append(row);
    });
}

/**
 * Format action for display
 * @param {string} action - The action to format
 * @returns {string} Formatted action
 */
function formatAction(action) {
    // Convert snake_case to Title Case with spaces
    return action
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

/**
 * Format timestamp for display
 * @param {string} timestamp - The timestamp to format
 * @returns {string} Formatted timestamp
 */
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}

/**
 * Show an error message
 * @param {string} message - The error message to display
 */
function showError(message) {
    alert(message); // Simple alert for now, could be improved with a custom modal
}

/**
 * Show a success message
 * @param {string} message - The success message to display
 */
function showSuccess(message) {
    alert(message); // Simple alert for now, could be improved with a custom modal
}