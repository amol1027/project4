/**
 * Authentication JavaScript for Quantum-Resistant Data Vault
 */

$(document).ready(function() {
    // Register form submission
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        registerUser();
    });
    
    // Login form submission
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        loginUser();
    });
    
    // Logout button
    $('#logout-btn').on('click', function() {
        logout();
    });
    
    // Biometric login button
    $('#biometric-login-btn').on('click', function() {
        biometricLogin();
    });
});

/**
 * Register a new user
 */
function registerUser() {
    const email = $('#register-email').val();
    const firstName = $('#register-first-name').val();
    const lastName = $('#register-last-name').val();
    const password = $('#register-password').val();
    const confirmPassword = $('#register-confirm-password').val();
    
    // Validate passwords match
    if (password !== confirmPassword) {
        showError('Passwords do not match');
        return;
    }
    
    // Validate password strength
    if (!validatePassword(password)) {
        showError('Password must be at least 8 characters and include uppercase, lowercase, number, and special character');
        return;
    }
    
    // Send registration request
    $.ajax({
        url: '../api/register.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            email: email,
            first_name: firstName,
            last_name: lastName,
            password: password
        }),
        success: function(response) {
            if (response.success) {
                showSuccess('Registration successful! Please log in.');
                $('#register-modal').hide();
                $('#login-modal').show();
                $('#login-email').val(email);
            } else {
                showError(response.error || 'Registration failed');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Registration failed');
            } catch (e) {
                showError('Registration failed: ' + xhr.status);
            }
        }
    });
}

/**
 * Login a user
 */
function loginUser() {
    const email = $('#login-email').val();
    const password = $('#login-password').val();
    
    // Send login request
    $.ajax({
        url: '../api/login.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            email: email,
            password: password
        }),
        success: function(response) {
            if (response.success) {
                // Store token and user data
                sessionStorage.setItem('token', response.token);
                sessionStorage.setItem('user', JSON.stringify(response.user));
                
                // Update global variables
                currentUser = response.user;
                isAuthenticated = true;
                
                // Update UI
                updateUIForAuthenticatedUser();
                
                // Close modal
                $('#login-modal').hide();
                
                // Navigate to dashboard
                navigateTo('dashboard');
                
                // Check if biometric authentication is required
                if (response.requires_biometric) {
                    showBiometricPrompt();
                }
            } else {
                showError(response.error || 'Login failed');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Login failed');
            } catch (e) {
                showError('Login failed: ' + xhr.status);
            }
        }
    });
}

/**
 * Logout the current user
 */
function logout() {
    // Clear session storage
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('user');
    
    // Update global variables
    currentUser = null;
    isAuthenticated = false;
    
    // Update UI
    updateUIForLoggedOutUser();
    
    // Navigate to home
    navigateTo('home');
    
    showSuccess('Logged out successfully');
}

/**
 * Validate password strength
 * @param {string} password - The password to validate
 * @returns {boolean} Whether the password is strong enough
 */
function validatePassword(password) {
    // At least 8 characters
    if (password.length < 8) return false;
    
    // Contains uppercase
    if (!/[A-Z]/.test(password)) return false;
    
    // Contains lowercase
    if (!/[a-z]/.test(password)) return false;
    
    // Contains number
    if (!/[0-9]/.test(password)) return false;
    
    // Contains special character
    if (!/[^A-Za-z0-9]/.test(password)) return false;
    
    return true;
}

/**
 * Show biometric authentication prompt
 */
function showBiometricPrompt() {
    // Check if WebAuthn is supported
    if (!window.PublicKeyCredential) {
        showError('WebAuthn is not supported in this browser');
        return;
    }
    
    // Show biometric section in dashboard
    navigateTo('dashboard');
    $('.dashboard-nav[data-section="biometric"]').click();
    
    // Show message
    showSuccess('Please register your biometric credential for enhanced security');
}

/**
 * Perform biometric login
 */
function biometricLogin() {
    const email = $('#login-email').val();
    
    if (!email) {
        showError('Please enter your email first');
        return;
    }
    
    // Get authentication options
    $.ajax({
        url: `../api/biometric_auth.php?email=${encodeURIComponent(email)}`,
        type: 'GET',
        success: function(response) {
            if (response.success && response.options) {
                // Convert base64 strings to ArrayBuffer
                const publicKey = preformatGetAssertReq(response.options);
                
                // Request biometric authentication
                navigator.credentials.get({ publicKey })
                    .then(assertion => {
                        // Format the assertion for sending to server
                        const assertionResponse = publicKeyCredentialToJSON(assertion);
                        
                        // Verify with server
                        verifyBiometricAssertion(assertionResponse, email);
                    })
                    .catch(error => {
                        console.error('Error with biometric authentication:', error);
                        showError('Biometric authentication failed');
                    });
            } else {
                showError(response.error || 'Failed to get authentication options');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Biometric authentication failed');
            } catch (e) {
                showError('Biometric authentication failed: ' + xhr.status);
            }
        }
    });
}

/**
 * Verify biometric assertion with server
 * @param {Object} assertionResponse - The formatted assertion response
 * @param {string} email - The user's email
 */
function verifyBiometricAssertion(assertionResponse, email) {
    $.ajax({
        url: '../api/biometric_auth.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            assertionResponse: assertionResponse,
            email: email
        }),
        success: function(response) {
            if (response.success) {
                // Store token and user data
                sessionStorage.setItem('token', response.token);
                sessionStorage.setItem('user', JSON.stringify(response.user));
                
                // Update global variables
                currentUser = response.user;
                isAuthenticated = true;
                
                // Update UI
                updateUIForAuthenticatedUser();
                
                // Close modal
                $('#login-modal').hide();
                
                // Navigate to dashboard
                navigateTo('dashboard');
                
                showSuccess('Biometric authentication successful');
            } else {
                showError(response.error || 'Biometric authentication failed');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Biometric authentication failed');
            } catch (e) {
                showError('Biometric authentication failed: ' + xhr.status);
            }
        }
    });
}

/**
 * Convert a PublicKeyCredential to JSON
 * @param {PublicKeyCredential} pubKeyCred - The credential to convert
 * @returns {Object} JSON representation of the credential
 */
function publicKeyCredentialToJSON(pubKeyCred) {
    if (pubKeyCred instanceof Array) {
        return pubKeyCred.map(publicKeyCredentialToJSON);
    }
    
    if (pubKeyCred instanceof ArrayBuffer) {
        return base64encode(pubKeyCred);
    }
    
    if (pubKeyCred instanceof Object) {
        const obj = {};
        
        for (const key in pubKeyCred) {
            obj[key] = publicKeyCredentialToJSON(pubKeyCred[key]);
        }
        
        return obj;
    }
    
    return pubKeyCred;
}

/**
 * Preformat get assertion request
 * @param {Object} request - The assertion request
 * @returns {Object} Formatted request
 */
function preformatGetAssertReq(request) {
    const allowCredentials = request.allowCredentials && request.allowCredentials.map(credential => {
        const cred = {
            type: credential.type,
            id: base64decode(credential.id)
        };
        
        if (credential.transports) {
            cred.transports = credential.transports;
        }
        
        return cred;
    });
    
    const publicKeyCredentialRequestOptions = {
        challenge: base64decode(request.challenge),
        timeout: request.timeout,
        rpId: request.rpId,
        allowCredentials,
        userVerification: request.userVerification
    };
    
    return publicKeyCredentialRequestOptions;
}

/**
 * Base64 encode an ArrayBuffer
 * @param {ArrayBuffer} buffer - The buffer to encode
 * @returns {string} Base64 encoded string
 */
function base64encode(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = '';
    
    for (const byte of bytes) {
        str += String.fromCharCode(byte);
    }
    
    return btoa(str);
}

/**
 * Base64 decode to ArrayBuffer
 * @param {string} base64 - The base64 string to decode
 * @returns {ArrayBuffer} Decoded array buffer
 */
function base64decode(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    
    return bytes.buffer;
}