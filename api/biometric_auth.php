<?php
/**
 * API endpoint for WebAuthn biometric authentication
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
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Biometric.php';

// Authentication middleware for session token
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

// Get authentication options
function getAuthenticationOptions() {
    // For authentication options, we need either a session token or an email
    $userId = authenticate();
    $email = isset($_GET['email']) ? $_GET['email'] : null;
    
    if (!$userId && !$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing user identifier']);
        exit;
    }
    
    $user = new User();
    
    if ($userId) {
        $loaded = $user->loadById($userId);
    } else {
        $loaded = $user->loadByEmail($email);
    }
    
    if (!$loaded) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    // Check if biometric is registered
    if (!$user->isBiometricRegistered()) {
        http_response_code(400);
        echo json_encode(['error' => 'Biometric authentication not registered for this user']);
        exit;
    }
    
    $biometric = new Biometric();
    $options = $biometric->getAuthenticationOptions($user);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
}

// Verify authentication response
function verifyAuthentication() {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['assertionResponse']) || !isset($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }
    
    $user = new User();
    if (!$user->loadByEmail($data['email'])) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $biometric = new Biometric();
    $result = $biometric->verifyAuthenticationResponse($data['assertionResponse'], $user);
    
    if (!$result) {
        // Log failed biometric authentication
        $db = Database::getInstance();
        $db->insert('audit_logs', [
            'user_id' => $user->getId(),
            'action' => 'failed_biometric_auth',
            'details' => null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Biometric authentication failed']);
        exit;
    }
    
    // Generate session token
    $sessionId = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    // Store session
    $db = Database::getInstance();
    $db->insert('sessions', [
        'id' => $sessionId,
        'user_id' => $user->getId(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => $expiresAt,
        'last_activity' => date('Y-m-d H:i:s')
    ]);
    
    // Log successful biometric authentication
    $db->insert('audit_logs', [
        'user_id' => $user->getId(),
        'action' => 'successful_biometric_auth',
        'details' => null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Biometric authentication successful',
        'token' => $sessionId,
        'user' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'biometric_registered' => true
        ],
        'expires_at' => $expiresAt
    ]);
}

// Route the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getAuthenticationOptions();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyAuthentication();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}