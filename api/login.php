<?php
/**
 * API endpoint for user login
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validate input
    if (!$data || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing email or password']);
        exit;
    }
    
    // Load user
    $user = new User();
    $loaded = $user->loadByEmail($data['email']);
    
    if (!$loaded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    
    // Verify password
    if (!$user->verifyPassword($data['password'])) {
        // Log failed login attempt
        $db = Database::getInstance();
        $db->insert('audit_logs', [
            'user_id' => $user->getId(),
            'action' => 'failed_login',
            'details' => json_encode(['email' => $data['email']]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
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
    
    // Log successful login
    $db->insert('audit_logs', [
        'user_id' => $user->getId(),
        'action' => 'successful_login',
        'details' => null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Check if biometric authentication is registered
    $biometricRegistered = $user->isBiometricRegistered();
    
    // Return success response with token
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $sessionId,
        'user' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'biometric_registered' => $biometricRegistered
        ],
        'expires_at' => $expiresAt,
        'requires_biometric' => $biometricRegistered
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    
    // Log the error
    error_log("Login error: " . $e->getMessage());
}