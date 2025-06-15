<?php
/**
 * API endpoint for WebAuthn biometric registration
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

// Include required files
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Biometric.php';

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

// Get registration options
function getRegistrationOptions() {
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $user = new User();
    if (!$user->loadById($userId)) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $biometric = new Biometric();
    $options = $biometric->getRegistrationOptions($user);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'options' => $options
    ]);
}

// Verify registration response
function verifyRegistration() {
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['attestationResponse'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }
    
    $user = new User();
    if (!$user->loadById($userId)) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    $biometric = new Biometric();
    $result = $biometric->verifyRegistrationResponse($data['attestationResponse'], $user);
    
    if (!$result) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to verify registration']);
        exit;
    }
    
    // Update user's biometric registration status
    $user->updateBiometricRegistration(true);
    
    // Log the biometric registration
    $db = Database::getInstance();
    $db->insert('audit_logs', [
        'user_id' => $userId,
        'action' => 'biometric_registration',
        'details' => null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Biometric registration successful'
    ]);
}

// Route the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    getRegistrationOptions();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyRegistration();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}