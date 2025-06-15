<?php
/**
 * Configuration settings for the Quantum-Resistant Data Vault
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change in production
define('DB_PASS', '');     // Change in production
define('DB_NAME', 'quantum_vault');

// Application settings
define('APP_NAME', 'Quantum-Resistant Data Vault');
define('APP_URL', 'http://localhost/project4/');
define('APP_VERSION', '1.0.0');

// Security settings
define('HASH_ALGO', 'sha512'); // For non-quantum resistant hashing
define('PEPPER', 'change_this_to_a_random_string_in_production'); // Additional security for password hashing

// Quantum-resistant encryption settings
define('KYBER_STRENGTH', 1024); // Kyber-1024 (highest security level)
define('DILITHIUM_STRENGTH', 4);  // CRYSTALS-Dilithium level 4

// WebAuthn settings
define('WEBAUTHN_RP_ID', 'localhost');
define('WEBAUTHN_RP_NAME', 'Quantum-Resistant Data Vault');
define('WEBAUTHN_RP_ICON', APP_URL . 'public/img/logo.png');

// Blockchain settings
define('BLOCKCHAIN_NETWORK', 'polygon-mumbai'); // Polygon testnet
define('BLOCKCHAIN_CONTRACT', '0x0000000000000000000000000000000000000000'); // Replace with actual contract address
define('BLOCKCHAIN_API_KEY', 'your_api_key_here'); // Replace with actual API key

// Error reporting
ini_set('display_errors', 1); // Set to 0 in production
ini_set('display_startup_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Session lifetime (in seconds)
define('SESSION_LIFETIME', 3600); // 1 hour

// Set timezone
date_default_timezone_set('UTC');