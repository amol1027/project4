<?php
/**
 * Blockchain API Endpoint
 * Handles NFT token metadata retrieval
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/User.php';
require_once '../includes/Blockchain.php';

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

// Set content type to JSON
header('Content-Type: application/json');

// Authentication middleware
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Validate session token
session_start();
$db = Database::getInstance();

$stmt = $db->prepare("SELECT user_id FROM sessions WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
    exit;
}

$userId = $session['user_id'];
$user = new User();
$user->loadById($userId);

if (!$user->getId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Initialize blockchain
$blockchain = new Blockchain();

// Handle GET request for token metadata
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if token_id is provided
    if (!isset($_GET['token_id']) || empty($_GET['token_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token ID is required']);
        exit;
    }
    
    $tokenId = $_GET['token_id'];
    
    // Verify the token belongs to the user
    if ($user->getTokenId() !== $tokenId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You do not have permission to access this token']);
        exit;
    }
    
    // Get token metadata
    $metadata = $blockchain->getTokenMetadata($tokenId);
    
    if ($metadata) {
        // Log the action
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, 'view_token_metadata', ?)");
        $stmt->execute([$userId, "Viewed metadata for token ID: $tokenId"]);
        
        echo json_encode(['success' => true, 'metadata' => $metadata]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Token metadata not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}