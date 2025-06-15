<?php
/**
 * API endpoint for retrieving data with quantum-resistant decryption and combining shards
 */

// Polyfill for getallheaders() if it doesn't exist
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Include required files
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Encryption.php';
require_once '../includes/Sharding.php';
require_once '../includes/Blockchain.php';

// Authentication middleware
function authenticate() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    $db = Database::getInstance();
    
    $session = $db->getRow(
        "SELECT user_id FROM sessions WHERE id = ? AND expires_at > NOW()", 
        [$token]
    );
    
    if (!$session) {
        return false;
    }
    
    // Update last activity
    $db->update('sessions', 
        ['last_activity' => date('Y-m-d H:i:s')], 
        'id = ?', 
        [$token]
    );
    
    return $session['user_id'];
}

try {
    // Authenticate user
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Load user
    $user = new User();
    if (!$user->loadById($userId)) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Check if biometric authentication is required
    if (REQUIRE_BIOMETRIC && !$user->isBiometricRegistered()) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Biometric authentication required but not registered',
            'requires_biometric_registration' => true
        ]);
        exit;
    }
    
    // Check if zero-knowledge proof is required
    if (REQUIRE_ZK_PROOF) {
        $zkProofProvided = isset($_GET['zk_proof']) ? $_GET['zk_proof'] : null;
        
        if (!$zkProofProvided) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Zero-knowledge proof required',
                'requires_zk_proof' => true
            ]);
            exit;
        }
        
        // Verify the proof
        $blockchain = new Blockchain();
        $proofValid = $blockchain->verifyZKProof(json_decode($zkProofProvided, true));
        
        if (!$proofValid) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid zero-knowledge proof']);
            exit;
        }
    }
    
    // Initialize sharding
    $sharding = new Sharding();
    
    // Retrieve and decrypt data
    $data = $sharding->retrieveAndDecryptData($userId);
    
    if ($data === false) {
        http_response_code(404);
        echo json_encode(['error' => 'No data found for this user']);
        exit;
    }
    
    // Log the data retrieval
    $db = Database::getInstance();
    $db->insert('audit_logs', [
        'user_id' => $userId,
        'action' => 'retrieve_data',
        'details' => json_encode(['data_size' => strlen(json_encode($data))]),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Return success response with data
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    
    // Log the error
    error_log("Data retrieval error: " . $e->getMessage());
}