/**
 * Data Management JavaScript for Quantum-Resistant Data Vault
 */

$(document).ready(function() {
    // Store data form submission
    $('#store-data-form').on('submit', function(e) {
        e.preventDefault();
        storeData();
    });
    
    // Retrieve data form submission
    $('#retrieve-data-form').on('submit', function(e) {
        e.preventDefault();
        retrieveData();
    });
    
    // Generate ZK proof form submission
    $('#generate-zk-proof-form').on('submit', function(e) {
        e.preventDefault();
        generateZKProof();
    });
});

/**
 * Store data in the quantum-resistant vault
 */
function storeData() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to store data');
        return;
    }
    
    const dataKey = $('#data-key').val();
    const dataValue = $('#data-value').val();
    const mintNFT = $('#mint-nft').is(':checked');
    
    if (!dataKey || !dataValue) {
        showError('Both key and value are required');
        return;
    }
    
    // Send store data request
    $.ajax({
        url: '../api/store_data.php',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        contentType: 'application/json',
        data: JSON.stringify({
            key: dataKey,
            value: dataValue,
            mint_nft: mintNFT
        }),
        success: function(response) {
            if (response.success) {
                showSuccess('Data stored successfully');
                
                // Clear form
                $('#data-key').val('');
                $('#data-value').val('');
                $('#mint-nft').prop('checked', false);
                
                // If NFT was minted, update user data
                if (response.token_id) {
                    const user = JSON.parse(sessionStorage.getItem('user'));
                    user.token_id = response.token_id;
                    sessionStorage.setItem('user', JSON.stringify(user));
                    
                    // Update UI to show token ID
                    $('#nft-token-id').text(response.token_id);
                    $('.nft-section').show();
                }
            } else {
                showError(response.error || 'Failed to store data');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to store data');
            } catch (e) {
                showError('Failed to store data: ' + xhr.status);
            }
        }
    });
}

/**
 * Retrieve data from the quantum-resistant vault
 */
function retrieveData() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to retrieve data');
        return;
    }
    
    const dataKey = $('#retrieve-key').val();
    const useZKProof = $('#use-zk-proof').is(':checked');
    const zkProof = useZKProof ? $('#zk-proof-value').val() : null;
    
    if (!dataKey) {
        showError('Data key is required');
        return;
    }
    
    // If ZK proof is required but not provided
    if (useZKProof && !zkProof) {
        showError('ZK proof is required when using proof-based access');
        return;
    }
    
    // Build request URL
    let url = `../api/retrieve_data.php?key=${encodeURIComponent(dataKey)}`;
    if (useZKProof) {
        url += `&use_zk_proof=true&zk_proof=${encodeURIComponent(zkProof)}`;
    }
    
    // Send retrieve data request
    $.ajax({
        url: url,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success) {
                // Display retrieved data
                $('#retrieved-data-container').show();
                $('#retrieved-data-key').text(dataKey);
                $('#retrieved-data-value').text(response.value);
                
                showSuccess('Data retrieved successfully');
            } else {
                showError(response.error || 'Failed to retrieve data');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to retrieve data');
            } catch (e) {
                showError('Failed to retrieve data: ' + xhr.status);
            }
        }
    });
}

/**
 * Generate a zero-knowledge proof for data access
 */
function generateZKProof() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to generate a ZK proof');
        return;
    }
    
    const attribute = $('#zk-attribute').val();
    const value = $('#zk-value').val();
    
    if (!attribute || !value) {
        showError('Both attribute and value are required');
        return;
    }
    
    // Send generate ZK proof request
    $.ajax({
        url: '../api/generate_zk_proof.php',
        type: 'POST',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        contentType: 'application/json',
        data: JSON.stringify({
            attribute: attribute,
            value: value
        }),
        success: function(response) {
            if (response.success) {
                // Display generated proof
                $('#zk-proof-result').show();
                $('#zk-proof-value').val(response.proof);
                $('#use-zk-proof').prop('checked', true);
                
                showSuccess('Zero-knowledge proof generated successfully');
            } else {
                showError(response.error || 'Failed to generate ZK proof');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to generate ZK proof');
            } catch (e) {
                showError('Failed to generate ZK proof: ' + xhr.status);
            }
        }
    });
}

/**
 * Load user's stored data keys
 */
function loadUserDataKeys() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        return;
    }
    
    // Send request to get user's data keys
    $.ajax({
        url: '../api/gdpr.php',
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success && response.data) {
                // Display data keys in the retrieve dropdown
                const dataKeys = response.data.stored_data || [];
                const $retrieveKey = $('#retrieve-key');
                
                $retrieveKey.empty();
                $retrieveKey.append('<option value="">Select a key</option>');
                
                dataKeys.forEach(key => {
                    $retrieveKey.append(`<option value="${key}">${key}</option>`);
                });
                
                // If no data keys, show message
                if (dataKeys.length === 0) {
                    $('#no-data-message').show();
                } else {
                    $('#no-data-message').hide();
                }
            }
        },
        error: function() {
            console.error('Failed to load user data keys');
        }
    });
}