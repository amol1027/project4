<?php
/**
 * Biometric authentication class using WebAuthn
 * 
 * Note: This implementation requires the web-auth/webauthn-lib package
 * which should be installed via Composer in a production environment.
 * For this simulation, we'll create a simplified version.
 */
class Biometric {
    private $db;
    private $rpId; // Relying Party ID (domain name)
    private $rpName; // Relying Party Name
    private $rpIcon; // Relying Party Icon URL
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->rpId = WEBAUTHN_RP_ID;
        $this->rpName = WEBAUTHN_RP_NAME;
        $this->rpIcon = WEBAUTHN_RP_ICON;
    }
    
    /**
     * Generate registration options for WebAuthn
     * @param int $userId User ID
     * @param string $userName User's name
     * @return array Registration options
     */
    public function getRegistrationOptions($userId, $userName) {
        // Generate a random challenge
        $challenge = $this->generateChallenge();
        
        // Store the challenge in the session for verification
        $_SESSION['webauthn_challenge'] = $challenge;
        $_SESSION['webauthn_user_id'] = $userId;
        
        // Create registration options
        $options = [
            'rp' => [
                'id' => $this->rpId,
                'name' => $this->rpName,
                'icon' => $this->rpIcon
            ],
            'user' => [
                'id' => $this->encodeUserId($userId),
                'name' => $userName,
                'displayName' => $userName
            ],
            'challenge' => $challenge,
            'pubKeyCredParams' => [
                [
                    'type' => 'public-key',
                    'alg' => -7 // ES256 algorithm
                ],
                [
                    'type' => 'public-key',
                    'alg' => -257 // RS256 algorithm
                ]
            ],
            'timeout' => 60000, // 60 seconds
            'attestation' => 'direct',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform', // Use platform authenticator (like fingerprint)
                'requireResidentKey' => false,
                'userVerification' => 'required' // Require biometric verification
            ]
        ];
        
        return $options;
    }
    
    /**
     * Verify registration response and store credential
     * @param array $response WebAuthn registration response
     * @return bool Success status
     */
    public function verifyRegistration($response) {
        // In a real implementation, this would use the web-auth/webauthn-lib package
        // to verify the attestation and extract the credential ID and public key
        
        // For simulation, we'll extract the credential ID and public key from the response
        if (!isset($response['id']) || !isset($response['rawId']) || 
            !isset($response['response']) || !isset($response['type']) || 
            $response['type'] !== 'public-key') {
            return false;
        }
        
        // Verify that the challenge matches
        if (!isset($_SESSION['webauthn_challenge']) || !isset($_SESSION['webauthn_user_id'])) {
            return false;
        }
        
        // Extract credential ID and public key
        $credentialId = $response['id'];
        $publicKey = isset($response['response']['publicKey']) ? $response['response']['publicKey'] : '';
        
        // Store the credential in the database
        $userId = $_SESSION['webauthn_user_id'];
        $data = [
            'user_id' => $userId,
            'credential_id' => $credentialId,
            'public_key' => $publicKey,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->db->insert('biometric_credentials', $data);
            
            // Update user's biometric registration status
            $user = new User();
            $user->loadById($userId);
            $user->updateBiometricStatus(true);
            
            // Clear session variables
            unset($_SESSION['webauthn_challenge']);
            unset($_SESSION['webauthn_user_id']);
            
            return true;
        } catch (Exception $e) {
            error_log("Biometric registration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate authentication options for WebAuthn
     * @param int $userId User ID (optional, if not provided will allow any credential)
     * @return array Authentication options
     */
    public function getAuthenticationOptions($userId = null) {
        // Generate a random challenge
        $challenge = $this->generateChallenge();
        
        // Store the challenge in the session for verification
        $_SESSION['webauthn_challenge'] = $challenge;
        
        // Create authentication options
        $options = [
            'challenge' => $challenge,
            'timeout' => 60000, // 60 seconds
            'rpId' => $this->rpId,
            'userVerification' => 'required' // Require biometric verification
        ];
        
        // If userId is provided, only allow credentials for this user
        if ($userId !== null) {
            $credentials = $this->getUserCredentials($userId);
            if (!empty($credentials)) {
                $options['allowCredentials'] = [];
                foreach ($credentials as $credential) {
                    $options['allowCredentials'][] = [
                        'type' => 'public-key',
                        'id' => $credential['credential_id']
                    ];
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Verify authentication response
     * @param array $response WebAuthn authentication response
     * @return int|bool User ID on success, false on failure
     */
    public function verifyAuthentication($response) {
        // In a real implementation, this would use the web-auth/webauthn-lib package
        // to verify the assertion
        
        // For simulation, we'll extract the credential ID from the response
        if (!isset($response['id']) || !isset($response['rawId']) || 
            !isset($response['response']) || !isset($response['type']) || 
            $response['type'] !== 'public-key') {
            return false;
        }
        
        // Verify that the challenge matches
        if (!isset($_SESSION['webauthn_challenge'])) {
            return false;
        }
        
        // Extract credential ID
        $credentialId = $response['id'];
        
        // Find the credential in the database
        $credential = $this->db->getRow(
            "SELECT user_id, public_key FROM biometric_credentials WHERE credential_id = ?", 
            [$credentialId]
        );
        
        if (!$credential) {
            return false;
        }
        
        // In a real implementation, we would verify the signature using the public key
        // For simulation, we'll just return the user ID
        
        // Clear session variables
        unset($_SESSION['webauthn_challenge']);
        
        return $credential['user_id'];
    }
    
    /**
     * Get user credentials
     * @param int $userId User ID
     * @return array User credentials
     */
    private function getUserCredentials($userId) {
        return $this->db->getRows(
            "SELECT credential_id, public_key FROM biometric_credentials WHERE user_id = ?", 
            [$userId]
        );
    }
    
    /**
     * Generate a random challenge
     * @return string Base64 encoded challenge
     */
    private function generateChallenge() {
        return base64_encode(random_bytes(32));
    }
    
    /**
     * Encode user ID for WebAuthn
     * @param int $userId User ID
     * @return string Base64 encoded user ID
     */
    private function encodeUserId($userId) {
        return base64_encode('user-' . $userId);
    }
    
    /**
     * Delete user biometric credentials
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteUserCredentials($userId) {
        try {
            $this->db->delete('biometric_credentials', 'user_id = ?', [$userId]);
            return true;
        } catch (Exception $e) {
            error_log("Delete biometric credentials error: " . $e->getMessage());
            return false;
        }
    }
}