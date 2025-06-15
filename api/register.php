<?php
/**
 * API endpoint for user registration
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
    if (!$data || !isset($data['email']) || !isset($data['password']) || 
        !isset($data['first_name']) || !isset($data['last_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    // Validate password strength
    if (strlen($data['password']) < 8 || 
        !preg_match('/[A-Z]/', $data['password']) || 
        !preg_match('/[a-z]/', $data['password']) || 
        !preg_match('/[0-9]/', $data['password']) || 
        !preg_match('/[^A-Za-z0-9]/', $data['password'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character'
        ]);
        exit;
    }
    
    // Check if user already exists
    $db = Database::getInstance();
    $existingUser = $db->getRow("SELECT id FROM users WHERE email = ?", [$data['email']]);
    
    if ($existingUser) {
        http_response_code(409);
        echo json_encode(['error' => 'User with this email already exists']);
        exit;
    }
    
    // Register user
    $user = new User();
    $userId = $user->register([
        'email' => $data['email'],
        'password' => $data['password'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name']
    ]);
    
    if (!$userId) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to register user']);
        exit;
    }
    
    // Log the registration
    $db->insert('audit_logs', [
        'user_id' => $userId,
        'action' => 'user_registration',
        'details' => json_encode(['email' => $data['email']]),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User registered successfully',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    
    // Log the error
    error_log("Registration error: " . $e->getMessage());
}