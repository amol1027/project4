/**
 * GDPR Management JavaScript for Quantum-Resistant Data Vault
 */

$(document).ready(function() {
    // Export data button
    $('#export-data-btn').on('click', function() {
        exportUserData();
    });
    
    // Update profile form submission
    $('#update-profile-form').on('submit', function(e) {
        e.preventDefault();
        updateUserProfile();
    });
    
    // Delete account button
    $('#delete-account-btn').on('click', function() {
        showDeleteAccountConfirmation();
    });
    
    // Delete account confirmation form
    $('#delete-account-confirm-form').on('submit', function(e) {
        e.preventDefault();
        deleteUserAccount();
    });
});

/**
 * Export user data (GDPR right to data portability)
 */
function exportUserData() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to export your data');
        return;
    }
    
    // Send export data request
    $.ajax({
        url: '../api/gdpr.php',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success && response.data) {
                // Format data for display
                const formattedData = JSON.stringify(response.data, null, 2);
                
                // Create download link
                const blob = new Blob([formattedData], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'quantum_vault_data_export.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showSuccess('Data exported successfully');
            } else {
                showError(response.error || 'Failed to export data');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to export data');
            } catch (e) {
                showError('Failed to export data: ' + xhr.status);
            }
        }
    });
}

/**
 * Update user profile (GDPR right to rectification)
 */
function updateUserProfile() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to update your profile');
        return;
    }
    
    const firstName = $('#update-first-name').val();
    const lastName = $('#update-last-name').val();
    const currentPassword = $('#update-current-password').val();
    const newPassword = $('#update-new-password').val();
    const confirmPassword = $('#update-confirm-password').val();
    
    // Validate inputs
    if (!firstName || !lastName) {
        showError('First name and last name are required');
        return;
    }
    
    // Check if password change is requested
    const changePassword = newPassword.length > 0;
    
    // If changing password, validate
    if (changePassword) {
        if (!currentPassword) {
            showError('Current password is required to change password');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showError('New passwords do not match');
            return;
        }
        
        if (!validatePassword(newPassword)) {
            showError('New password must be at least 8 characters and include uppercase, lowercase, number, and special character');
            return;
        }
    }
    
    // Prepare data for update
    const updateData = {
        first_name: firstName,
        last_name: lastName
    };
    
    if (changePassword) {
        updateData.current_password = currentPassword;
        updateData.new_password = newPassword;
    }
    
    // Send update request
    $.ajax({
        url: '../api/gdpr.php',
        type: 'PUT',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        contentType: 'application/json',
        data: JSON.stringify(updateData),
        success: function(response) {
            if (response.success) {
                // Update user data in session
                const user = JSON.parse(sessionStorage.getItem('user'));
                user.first_name = firstName;
                user.last_name = lastName;
                sessionStorage.setItem('user', JSON.stringify(user));
                
                // Update UI
                $('#user-name').text(firstName + ' ' + lastName);
                
                // Clear password fields
                $('#update-current-password').val('');
                $('#update-new-password').val('');
                $('#update-confirm-password').val('');
                
                // Close modal
                $('#update-profile-modal').hide();
                
                showSuccess('Profile updated successfully');
            } else {
                showError(response.error || 'Failed to update profile');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to update profile');
            } catch (e) {
                showError('Failed to update profile: ' + xhr.status);
            }
        }
    });
}

/**
 * Show delete account confirmation modal
 */
function showDeleteAccountConfirmation() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to delete your account');
        return;
    }
    
    // Show confirmation modal
    $('#delete-account-modal').show();
}

/**
 * Delete user account (GDPR right to be forgotten)
 */
function deleteUserAccount() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to delete your account');
        return;
    }
    
    const confirmEmail = $('#delete-account-email').val();
    const confirmPassword = $('#delete-account-password').val();
    const confirmText = $('#delete-account-confirm').val();
    
    // Validate inputs
    if (!confirmEmail || !confirmPassword) {
        showError('Email and password are required');
        return;
    }
    
    if (confirmText !== 'DELETE') {
        showError('Please type DELETE to confirm');
        return;
    }
    
    // Send delete request
    $.ajax({
        url: '../api/gdpr.php',
        type: 'DELETE',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        contentType: 'application/json',
        data: JSON.stringify({
            email: confirmEmail,
            password: confirmPassword,
            confirmation: confirmText
        }),
        success: function(response) {
            if (response.success) {
                // Clear session storage
                sessionStorage.removeItem('token');
                sessionStorage.removeItem('user');
                
                // Update global variables
                currentUser = null;
                isAuthenticated = false;
                
                // Update UI
                updateUIForLoggedOutUser();
                
                // Close modal
                $('#delete-account-modal').hide();
                
                // Navigate to home
                navigateTo('home');
                
                showSuccess('Account deleted successfully');
            } else {
                showError(response.error || 'Failed to delete account');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to delete account');
            } catch (e) {
                showError('Failed to delete account: ' + xhr.status);
            }
        }
    });
}

/**
 * Load user activity logs
 */
function loadUserActivityLogs() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        return;
    }
    
    // Send request to get user's activity logs
    $.ajax({
        url: '../api/gdpr.php?logs=true',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success && response.logs) {
                // Display logs in the activity log section
                const $activityLogs = $('#activity-logs');
                $activityLogs.empty();
                
                if (response.logs.length === 0) {
                    $activityLogs.append('<p>No activity logs found.</p>');
                } else {
                    const $table = $('<table class="activity-log-table"></table>');
                    $table.append('<thead><tr><th>Action</th><th>Timestamp</th><th>Details</th></tr></thead>');
                    
                    const $tbody = $('<tbody></tbody>');
                    response.logs.forEach(log => {
                        const $row = $('<tr></tr>');
                        $row.append(`<td>${formatAction(log.action)}</td>`);
                        $row.append(`<td>${formatTimestamp(log.timestamp)}</td>`);
                        $row.append(`<td>${log.details || '-'}</td>`);
                        $tbody.append($row);
                    });
                    
                    $table.append($tbody);
                    $activityLogs.append($table);
                }
            }
        },
        error: function() {
            console.error('Failed to load activity logs');
        }
    });
}

/**
 * Format action for display
 * @param {string} action - The action name
 * @returns {string} Formatted action
 */
function formatAction(action) {
    // Convert snake_case to Title Case
    return action
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

/**
 * Format timestamp for display
 * @param {string} timestamp - The timestamp string
 * @returns {string} Formatted timestamp
 */
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}