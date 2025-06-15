/**
 * Biometric Management JavaScript for Quantum-Resistant Data Vault
 */

$(document).ready(function() {
    // Register biometric button
    $('#register-biometric-btn').on('click', function() {
        registerBiometric();
    });
    
    // Delete biometric button
    $('#delete-biometric-btn').on('click', function() {
        deleteBiometric();
    });
});

/**
 * Register a biometric credential
 */
function registerBiometric() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to register biometrics');
        return;
    }
    
    // Check if WebAuthn is supported
    if (!window.PublicKeyCredential) {
        showError('WebAuthn is not supported in this browser');
        return;
    }
    
    // Get registration options
    $.ajax({
        url: '../api/biometric_register.php',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success && response.options) {
                // Convert base64 strings to ArrayBuffer
                const publicKey = preformatMakeCredReq(response.options);
                
                // Request biometric registration
                navigator.credentials.create({ publicKey })
                    .then(attestation => {
                        // Format the attestation for sending to server
                        const attestationResponse = publicKeyCredentialToJSON(attestation);
                        
                        // Verify with server
                        verifyBiometricRegistration(attestationResponse);
                    })
                    .catch(error => {
                        console.error('Error with biometric registration:', error);
                        showError('Biometric registration failed');
                    });
            } else {
                showError(response.error || 'Failed to get registration options');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to get registration options');
            } catch (e) {
                showError('Failed to get registration options: ' + xhr.status);
            }
        }
    });
}

/**
 * Verify biometric registration with server
 * @param {Object} attestationResponse - The formatted attestation response
 */
function verifyBiometricRegistration(attestationResponse) {
    $.ajax({
        url: '../api/biometric_register.php',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        contentType: 'application/json',
        data: JSON.stringify({
            attestationResponse: attestationResponse
        }),
        success: function(response) {
            if (response.success) {
                // Update user data
                const user = JSON.parse(sessionStorage.getItem('user'));
                user.has_biometric = true;
                sessionStorage.setItem('user', JSON.stringify(user));
                
                // Update UI
                updateBiometricUI(true);
                
                showSuccess('Biometric registration successful');
            } else {
                showError(response.error || 'Biometric registration failed');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Biometric registration failed');
            } catch (e) {
                showError('Biometric registration failed: ' + xhr.status);
            }
        }
    });
}

/**
 * Delete biometric credential
 */
function deleteBiometric() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to delete biometrics');
        return;
    }
    
    // Confirm deletion
    if (!confirm('Are you sure you want to delete your biometric credential? This action cannot be undone.')) {
        return;
    }
    
    // Send delete request
    $.ajax({
        url: '../api/biometric_register.php',
        type: 'DELETE',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success) {
                // Update user data
                const user = JSON.parse(sessionStorage.getItem('user'));
                user.has_biometric = false;
                sessionStorage.setItem('user', JSON.stringify(user));
                
                // Update UI
                updateBiometricUI(false);
                
                showSuccess('Biometric credential deleted successfully');
            } else {
                showError(response.error || 'Failed to delete biometric credential');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to delete biometric credential');
            } catch (e) {
                showError('Failed to delete biometric credential: ' + xhr.status);
            }
        }
    });
}

/**
 * Update biometric UI based on registration status
 * @param {boolean} hasRegistered - Whether the user has registered biometrics
 */
function updateBiometricUI(hasRegistered) {
    if (hasRegistered) {
        $('#biometric-registered').show();
        $('#biometric-not-registered').hide();
        $('#biometric-login-btn').show();
    } else {
        $('#biometric-registered').hide();
        $('#biometric-not-registered').show();
        $('#biometric-login-btn').hide();
    }
}

/**
 * Preformat make credential request
 * @param {Object} request - The credential request
 * @returns {Object} Formatted request
 */
function preformatMakeCredReq(request) {
    const excludeCredentials = request.excludeCredentials && request.excludeCredentials.map(credential => {
        return {
            type: credential.type,
            id: base64decode(credential.id)
        };
    });
    
    const publicKeyCredentialCreationOptions = {
        challenge: base64decode(request.challenge),
        rp: request.rp,
        user: {
            id: base64decode(request.user.id),
            name: request.user.name,
            displayName: request.user.displayName
        },
        pubKeyCredParams: request.pubKeyCredParams,
        timeout: request.timeout,
        excludeCredentials,
        authenticatorSelection: request.authenticatorSelection,
        attestation: request.attestation
    };
    
    return publicKeyCredentialCreationOptions;
}

/**
 * Check if user has biometric registered and update UI
 */
function checkBiometricStatus() {
    if (isAuthenticated && currentUser) {
        updateBiometricUI(currentUser.has_biometric);
    } else {
        updateBiometricUI(false);
    }
}