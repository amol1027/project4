<?php
/**
 * API endpoint for GDPR compliance operations
 * - Export user data (right to data portability)
 * - Update user data (right to rectification)
 * - Delete user data (right to be forgotten)
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
header('Access-Control-Allow-Methods: GET, PUT, DELETE');
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
require_once '../includes/GDPR.php';

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

// Export user data (right to data portability)
function exportUserData() {
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $gdpr = new GDPR();
    $userData = $gdpr->exportUserData($userId);
    
    if (!$userData) {
        http_response_code(404);
        echo json_encode(['error' => 'No data found for this user']);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $userData
    ]);
}

// Update user data (right to rectification)
function updateUserData() {
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request data']);
        exit;
    }
    
    $gdpr = new GDPR();
    $result = $gdpr->updateUserData($userId, $data);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user data']);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User data updated successfully'
    ]);
}

// Delete user data (right to be forgotten)
function deleteUserData() {
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // For account deletion, require password confirmation
    $headers = getallheaders();
    $confirmHeader = isset($headers['X-Confirm-Delete']) ? $headers['X-Confirm-Delete'] : '';
    
    if (empty($confirmHeader) || $confirmHeader !== 'true') {
        http_response_code(400);
        echo json_encode(['error' => 'Deletion confirmation required']);
        exit;
    }
    
    $gdpr = new GDPR();
    $result = $gdpr->deleteAllUserData($userId);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user data']);
        exit;
    }
    
    // Invalidate all sessions for this user
    $db = Database::getInstance();
    $db->delete('sessions', 'user_id = ?', [$userId]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'User data deleted successfully'
    ]);
}

// Get user activity logs
function getUserActivityLogs() {
    $userId = authenticate();
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $gdpr = new GDPR();
    $logs = $gdpr->getUserActivityLogs($userId);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
}

// Route the request based on method
try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['logs'])) {
                getUserActivityLogs();
            } else {
                exportUserData();
            }
            break;
            
        case 'PUT':
            updateUserData();
            break;
            
        case 'DELETE':
            deleteUserData();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    
    // Log the error
    error_log("GDPR operation error: " . $e->getMessage());
}