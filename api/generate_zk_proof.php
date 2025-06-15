<?php
/**
 * API endpoint for generating zero-knowledge proofs for data access
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
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Include required files
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
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
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate input
    if (!$data || !isset($data['attribute']) || !isset($data['value'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Load user
    $user = new User();
    if (!$user->loadById($userId)) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Check if user has an NFT token
    $tokenId = $user->getNftTokenId();
    if (!$tokenId) {
        http_response_code(400);
        echo json_encode(['error' => 'User does not have an NFT token']);
        exit;
    }
    
    // Generate zero-knowledge proof
    $blockchain = new Blockchain();
    $proof = $blockchain->generateZKProof($userId, $data['attribute'], $data['value']);
    
    if (!$proof) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate zero-knowledge proof']);
        exit;
    }
    
    // Log the proof generation
    $db = Database::getInstance();
    $db->insert('audit_logs', [
        'user_id' => $userId,
        'action' => 'generate_zk_proof',
        'details' => json_encode(['attribute' => $data['attribute']]),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Return success response with proof
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'proof' => $proof
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    
    // Log the error
    error_log("ZK proof generation error: " . $e->getMessage());
}