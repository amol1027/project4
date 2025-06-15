<?php
/**
 * Database setup script
 * 
 * This script initializes the database for the Quantum-Resistant Data Vault
 */

// Include configuration
require_once 'config.php';
require_once 'Database.php';

try {
    // Create PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    
    echo "Database created successfully.\n";
    
    // Read schema file
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
    
    echo "Database tables created successfully.\n";
    
    // Create admin user if it doesn't exist
    $db = Database::getInstance();
    $adminEmail = 'admin@example.com';
    
    $admin = $db->getRow("SELECT id FROM users WHERE email = ?", [$adminEmail]);
    
    if (!$admin) {
        // Include User class
        require_once 'User.php';
        
        $user = new User();
        $userId = $user->register([
            'email' => $adminEmail,
            'password' => 'Admin123!', // Change this in production
            'first_name' => 'Admin',
            'last_name' => 'User'
        ]);
        
        if ($userId) {
            echo "Admin user created successfully.\n";
        } else {
            echo "Failed to create admin user.\n";
        }
    } else {
        echo "Admin user already exists.\n";
    }
    
    echo "Database setup completed successfully.\n";
    
} catch (PDOException $e) {
    die("Database setup error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}