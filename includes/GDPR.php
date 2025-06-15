<?php
/**
 * GDPR compliance tools
 */
class GDPR {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Delete all user data (right to be forgotten)
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteAllUserData($userId) {
        try {
            $this->db->beginTransaction();
            
            // Delete biometric data
            $biometric = new Biometric();
            $biometric->deleteUserCredentials($userId);
            
            // Delete sharded data
            $sharding = new Sharding();
            $sharding->deleteUserData($userId);
            
            // Delete blockchain tokens
            $blockchain = new Blockchain();
            $blockchain->deleteUserTokens($userId);
            
            // Delete user account
            $user = new User();
            $user->loadById($userId);
            $user->deleteAccount();
            
            // Log the deletion for compliance
            $this->logDeletion($userId);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("GDPR deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Export all user data (right to data portability)
     * @param int $userId User ID
     * @return array|bool User data on success, false on failure
     */
    public function exportUserData($userId) {
        try {
            // Get user profile data
            $user = new User();
            if (!$user->loadById($userId)) {
                return false;
            }
            
            $userData = [
                'profile' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'first_name' => $user->getFirstName(),
                    'last_name' => $user->getLastName(),
                    'created_at' => $user->getCreated(),
                    'biometric_registered' => $user->isBiometricRegistered(),
                    'nft_token_id' => $user->getNftTokenId()
                ]
            ];
            
            // Get user's stored data
            $sharding = new Sharding();
            $storedData = $sharding->retrieveAndDecryptData($userId);
            if ($storedData) {
                $userData['stored_data'] = $storedData;
            }
            
            // Get token metadata if available
            $tokenId = $user->getNftTokenId();
            if ($tokenId) {
                $blockchain = new Blockchain();
                $metadata = $blockchain->getTokenMetadata($tokenId);
                if ($metadata) {
                    $userData['token_metadata'] = $metadata;
                }
            }
            
            // Get biometric credential IDs (not the actual biometric data)
            $biometricCredentials = $this->db->getRows(
                "SELECT credential_id, created_at FROM biometric_credentials WHERE user_id = ?", 
                [$userId]
            );
            if (!empty($biometricCredentials)) {
                $userData['biometric_credentials'] = $biometricCredentials;
            }
            
            // Log the export for compliance
            $this->logExport($userId);
            
            return $userData;
        } catch (Exception $e) {
            error_log("GDPR export error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user data (right to rectification)
     * @param int $userId User ID
     * @param array $newData New user data
     * @return bool Success status
     */
    public function updateUserData($userId, $newData) {
        try {
            $this->db->beginTransaction();
            
            // Update user profile data
            if (isset($newData['profile'])) {
                $profileData = [];
                
                if (isset($newData['profile']['email'])) {
                    $profileData['email'] = $newData['profile']['email'];
                }
                
                if (isset($newData['profile']['first_name'])) {
                    $profileData['first_name'] = $newData['profile']['first_name'];
                }
                
                if (isset($newData['profile']['last_name'])) {
                    $profileData['last_name'] = $newData['profile']['last_name'];
                }
                
                if (!empty($profileData)) {
                    $this->db->update('users', $profileData, 'id = ?', [$userId]);
                }
            }
            
            // Update stored data
            if (isset($newData['stored_data'])) {
                $sharding = new Sharding();
                $sharding->updateUserData($userId, $newData['stored_data']);
            }
            
            // Log the update for compliance
            $this->logUpdate($userId);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("GDPR update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log data deletion for compliance
     * @param int $userId User ID
     */
    private function logDeletion($userId) {
        $data = [
            'user_id' => $userId,
            'action' => 'deletion',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->db->insert('gdpr_logs', $data);
    }
    
    /**
     * Log data export for compliance
     * @param int $userId User ID
     */
    private function logExport($userId) {
        $data = [
            'user_id' => $userId,
            'action' => 'export',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->db->insert('gdpr_logs', $data);
    }
    
    /**
     * Log data update for compliance
     * @param int $userId User ID
     */
    private function logUpdate($userId) {
        $data = [
            'user_id' => $userId,
            'action' => 'update',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->db->insert('gdpr_logs', $data);
    }
    
    /**
     * Get GDPR activity logs for a user
     * @param int $userId User ID
     * @return array Activity logs
     */
    public function getUserActivityLogs($userId) {
        return $this->db->getRows(
            "SELECT action, timestamp, ip_address FROM gdpr_logs WHERE user_id = ? ORDER BY timestamp DESC", 
            [$userId]
        );
    }
}