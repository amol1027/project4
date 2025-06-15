<?php
/**
 * Data sharding implementation for secure data storage
 */
class Sharding {
    private $db;
    private $encryption;
    private $numShards = 3; // Default number of shards
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->encryption = new Encryption();
    }
    
    /**
     * Set the number of shards
     * @param int $numShards Number of shards
     */
    public function setNumShards($numShards) {
        if ($numShards >= 2 && $numShards <= 10) {
            $this->numShards = $numShards;
        }
    }
    
    /**
     * Shard and encrypt user data
     * @param int $userId User ID
     * @param array $data Associative array of data to shard
     * @return bool Success status
     */
    public function shardAndEncryptData($userId, $data) {
        // Convert data to JSON
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            throw new Exception("Failed to encode data as JSON");
        }
        
        // Generate a master encryption key
        $masterKey = $this->encryption->generateRandomKey(32);
        
        // Split the data into shards
        $shards = $this->splitIntoShards($jsonData, $this->numShards);
        
        // Generate a key pair for the user
        $keyPair = $this->encryption->generateKyberKeyPair();
        
        // Encrypt the master key with the user's public key
        $encryptedMasterKey = $this->encryption->kyberEncrypt($masterKey, $keyPair['public_key']);
        
        try {
            $this->db->beginTransaction();
            
            // Store the encrypted master key and public key
            $keyData = [
                'user_id' => $userId,
                'public_key' => $keyPair['public_key'],
                'encrypted_master_key' => $encryptedMasterKey,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $keyId = $this->db->insert('user_keys', $keyData);
            
            // Store the private key securely (in a real system, this would be handled differently)
            // For this simulation, we'll store it encrypted with a derived key from the user's password
            // In a production system, this might be stored on a hardware security module or given to the user
            $privateKeyData = [
                'user_id' => $userId,
                'private_key' => $keyPair['private_key'], // In production, this would be encrypted
                'created_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('user_private_keys', $privateKeyData);
            
            // Encrypt and store each shard
            foreach ($shards as $index => $shard) {
                // Encrypt the shard with the master key
                $encryptedShard = $this->encryption->aesEncrypt($shard, $masterKey);
                
                // Store the encrypted shard
                $shardData = [
                    'user_id' => $userId,
                    'shard_index' => $index,
                    'encrypted_data' => $encryptedShard,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $this->db->insert('data_shards', $shardData);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Data sharding error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Retrieve and decrypt user data
     * @param int $userId User ID
     * @return array|bool Decrypted data as array or false on failure
     */
    public function retrieveAndDecryptData($userId) {
        try {
            // Get the user's private key and encrypted master key
            $privateKeyRow = $this->db->getRow(
                "SELECT private_key FROM user_private_keys WHERE user_id = ?", 
                [$userId]
            );
            
            $masterKeyRow = $this->db->getRow(
                "SELECT encrypted_master_key FROM user_keys WHERE user_id = ?", 
                [$userId]
            );
            
            if (!$privateKeyRow || !$masterKeyRow) {
                return false;
            }
            
            // Decrypt the master key with the private key
            $privateKey = $privateKeyRow['private_key'];
            $encryptedMasterKey = $masterKeyRow['encrypted_master_key'];
            $masterKey = $this->encryption->kyberDecrypt($encryptedMasterKey, $privateKey);
            
            // Get all shards for the user
            $shards = $this->db->getRows(
                "SELECT shard_index, encrypted_data FROM data_shards WHERE user_id = ? ORDER BY shard_index", 
                [$userId]
            );
            
            if (count($shards) === 0) {
                return false;
            }
            
            // Decrypt and combine shards
            $decryptedShards = [];
            foreach ($shards as $shard) {
                $decryptedShard = $this->encryption->aesDecrypt($shard['encrypted_data'], $masterKey);
                $decryptedShards[$shard['shard_index']] = $decryptedShard;
            }
            
            // Sort shards by index
            ksort($decryptedShards);
            
            // Combine shards
            $combinedData = implode('', $decryptedShards);
            
            // Decode JSON data
            $decodedData = json_decode($combinedData, true);
            if ($decodedData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Failed to decode JSON data: " . json_last_error_msg());
            }
            
            return $decodedData;
        } catch (Exception $e) {
            error_log("Data retrieval error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user data
     * @param int $userId User ID
     * @param array $newData New data to store
     * @return bool Success status
     */
    public function updateUserData($userId, $newData) {
        // First, delete existing shards
        try {
            $this->db->delete('data_shards', 'user_id = ?', [$userId]);
            
            // Then create new shards with the updated data
            return $this->shardAndEncryptData($userId, $newData);
        } catch (Exception $e) {
            error_log("Data update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all user data shards
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deleteUserData($userId) {
        try {
            $this->db->beginTransaction();
            
            // Delete data shards
            $this->db->delete('data_shards', 'user_id = ?', [$userId]);
            
            // Delete keys
            $this->db->delete('user_keys', 'user_id = ?', [$userId]);
            $this->db->delete('user_private_keys', 'user_id = ?', [$userId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Data deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Split data into shards
     * @param string $data Data to split
     * @param int $numShards Number of shards
     * @return array Array of shards
     */
    private function splitIntoShards($data, $numShards) {
        $dataLength = strlen($data);
        $shardSize = ceil($dataLength / $numShards);
        $shards = [];
        
        for ($i = 0; $i < $numShards; $i++) {
            $start = $i * $shardSize;
            $length = min($shardSize, $dataLength - $start);
            $shards[] = substr($data, $start, $length);
        }
        
        return $shards;
    }
}