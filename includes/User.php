<?php
/**
 * User model for handling user-related operations
 */
class User {
    private $db;
    private $id;
    private $email;
    private $firstName;
    private $lastName;
    private $created;
    private $biometricRegistered;
    private $nftTokenId;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Load user by ID
     * @param int $id User ID
     * @return bool Success status
     */
    public function loadById($id) {
        $row = $this->db->getRow("SELECT * FROM users WHERE id = ?", [$id]);
        
        if ($row) {
            $this->setUserData($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Load user by email
     * @param string $email User email
     * @return bool Success status
     */
    public function loadByEmail($email) {
        $row = $this->db->getRow("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($row) {
            $this->setUserData($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Set user data from database row
     * @param array $row User data from database
     */
    private function setUserData($row) {
        $this->id = $row['id'];
        $this->email = $row['email'];
        $this->firstName = $row['first_name'];
        $this->lastName = $row['last_name'];
        $this->created = $row['created_at'];
        $this->biometricRegistered = (bool)$row['biometric_registered'];
        $this->nftTokenId = $row['nft_token_id'];
    }
    
    /**
     * Register a new user
     * @param string $email User email
     * @param string $password User password
     * @param string $firstName User first name
     * @param string $lastName User last name
     * @return int|bool User ID on success, false on failure
     */
    public function register($email, $password, $firstName, $lastName) {
        // Check if email already exists
        if ($this->emailExists($email)) {
            return false;
        }
        
        // Hash password with quantum-resistant approach
        $passwordHash = $this->hashPassword($password);
        
        // Insert user data
        $data = [
            'email' => $email,
            'password_hash' => $passwordHash,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'created_at' => date('Y-m-d H:i:s'),
            'biometric_registered' => 0,
            'nft_token_id' => null
        ];
        
        try {
            $userId = $this->db->insert('users', $data);
            $this->loadById($userId);
            return $userId;
        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if email already exists
     * @param string $email User email
     * @return bool True if email exists
     */
    public function emailExists($email) {
        $row = $this->db->getRow("SELECT id FROM users WHERE email = ?", [$email]);
        return !empty($row);
    }
    
    /**
     * Hash password with quantum-resistant approach
     * @param string $password Plain text password
     * @return string Hashed password
     */
    private function hashPassword($password) {
        // Add pepper to password before hashing
        $pepperedPassword = $password . PEPPER;
        
        // Use Argon2id for password hashing (considered quantum-resistant)
        return password_hash($pepperedPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 2          // 2 threads
        ]);
    }
    
    /**
     * Verify password
     * @param string $password Plain text password
     * @return bool True if password is correct
     */
    public function verifyPassword($password) {
        if (!$this->id) {
            return false;
        }
        
        $row = $this->db->getRow("SELECT password_hash FROM users WHERE id = ?", [$this->id]);
        if (!$row) {
            return false;
        }
        
        // Add pepper to password before verification
        $pepperedPassword = $password . PEPPER;
        
        return password_verify($pepperedPassword, $row['password_hash']);
    }
    
    /**
     * Update biometric registration status
     * @param bool $status Biometric registration status
     * @return bool Success status
     */
    public function updateBiometricStatus($status) {
        if (!$this->id) {
            return false;
        }
        
        $result = $this->db->update('users', 
            ['biometric_registered' => $status ? 1 : 0], 
            'id = ?', 
            [$this->id]
        );
        
        if ($result) {
            $this->biometricRegistered = $status;
            return true;
        }
        
        return false;
    }
    
    /**
     * Update NFT token ID
     * @param string $tokenId NFT token ID
     * @return bool Success status
     */
    public function updateNftTokenId($tokenId) {
        if (!$this->id) {
            return false;
        }
        
        $result = $this->db->update('users', 
            ['nft_token_id' => $tokenId], 
            'id = ?', 
            [$this->id]
        );
        
        if ($result) {
            $this->nftTokenId = $tokenId;
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete user account (GDPR compliance)
     * @return bool Success status
     */
    public function deleteAccount() {
        if (!$this->id) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Delete user biometric data
            $this->db->delete('biometric_credentials', 'user_id = ?', [$this->id]);
            
            // Delete user data shards
            $this->db->delete('data_shards', 'user_id = ?', [$this->id]);
            
            // Delete user account
            $this->db->delete('users', 'id = ?', [$this->id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("User deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getFirstName() { return $this->firstName; }
    public function getLastName() { return $this->lastName; }
    public function getCreated() { return $this->created; }
    public function isBiometricRegistered() { return $this->biometricRegistered; }
    public function getNftTokenId() { return $this->nftTokenId; }
}