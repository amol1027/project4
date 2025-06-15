/**
 * Blockchain JavaScript for Quantum-Resistant Data Vault
 */

$(document).ready(function() {
    // View NFT metadata button
    $('#view-nft-metadata-btn').on('click', function() {
        viewNFTMetadata();
    });
});

/**
 * View NFT token metadata
 */
function viewNFTMetadata() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        showError('You must be logged in to view NFT metadata');
        return;
    }
    
    // Get user data
    const user = JSON.parse(sessionStorage.getItem('user'));
    
    // Check if user has NFT token
    if (!user.token_id) {
        showError('You do not have an NFT token. Store data with the "Mint NFT" option to create one.');
        return;
    }
    
    // Send request to get token metadata
    $.ajax({
        url: `../api/blockchain.php?token_id=${encodeURIComponent(user.token_id)}`,
        type: 'GET',
        headers: {
            'Authorization': 'Bearer ' + sessionStorage.getItem('token')
        },
        success: function(response) {
            if (response.success && response.metadata) {
                // Display metadata
                displayNFTMetadata(response.metadata);
            } else {
                showError(response.error || 'Failed to retrieve NFT metadata');
            }
        },
        error: function(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                showError(response.error || 'Failed to retrieve NFT metadata');
            } catch (e) {
                showError('Failed to retrieve NFT metadata: ' + xhr.status);
            }
        }
    });
}

/**
 * Display NFT metadata in the UI
 * @param {Object} metadata - The NFT metadata
 */
function displayNFTMetadata(metadata) {
    // Create metadata display
    const $metadataContainer = $('#nft-metadata-container');
    $metadataContainer.empty();
    
    // Create metadata card
    const $card = $('<div class="nft-card"></div>');
    
    // Add NFT image if available
    if (metadata.image) {
        $card.append(`<div class="nft-image"><img src="${metadata.image}" alt="NFT Image"></div>`);
    } else {
        // Default image
        $card.append('<div class="nft-image"><div class="nft-placeholder">QV</div></div>');
    }
    
    // Add metadata details
    const $details = $('<div class="nft-details"></div>');
    
    // Add name and description
    $details.append(`<h3>${metadata.name || 'Quantum Vault NFT'}</h3>`);
    $details.append(`<p>${metadata.description || 'A quantum-resistant NFT token for secure data access'}</p>`);
    
    // Add attributes
    if (metadata.attributes && metadata.attributes.length > 0) {
        const $attributes = $('<div class="nft-attributes"></div>');
        $attributes.append('<h4>Attributes</h4>');
        
        const $attrList = $('<ul></ul>');
        metadata.attributes.forEach(attr => {
            $attrList.append(`<li><strong>${attr.trait_type}:</strong> ${attr.value}</li>`);
        });
        
        $attributes.append($attrList);
        $details.append($attributes);
    }
    
    // Add blockchain details
    $details.append('<div class="nft-blockchain-details"></div>');
    $details.append(`<p><strong>Token ID:</strong> ${metadata.token_id || user.token_id}</p>`);
    $details.append(`<p><strong>Created:</strong> ${formatTimestamp(metadata.created_at)}</p>`);
    
    // Add details to card
    $card.append($details);
    
    // Add card to container
    $metadataContainer.append($card);
    $metadataContainer.show();
}

/**
 * Load user's NFT token information
 */
function loadUserNFTToken() {
    // Check authentication
    if (!isAuthenticated || !sessionStorage.getItem('token')) {
        return;
    }
    
    // Get user data
    const user = JSON.parse(sessionStorage.getItem('user'));
    
    // Check if user has NFT token
    if (user.token_id) {
        // Update UI to show token ID
        $('#nft-token-id').text(user.token_id);
        $('.nft-section').show();
    } else {
        $('.nft-section').hide();
    }
}

/**
 * Format timestamp for display
 * @param {string} timestamp - The timestamp string
 * @returns {string} Formatted timestamp
 */
function formatTimestamp(timestamp) {
    if (!timestamp) return 'Unknown';
    
    const date = new Date(timestamp);
    return date.toLocaleString();
}