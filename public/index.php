<?php
// Include configuration
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/favicon.png">
</head>
<body>
    <div class="app-container">
        <header>
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <h1><?php echo APP_NAME; ?></h1>
            </div>
            <nav id="main-nav">
                <ul>
                    <li><a href="#" class="nav-link" data-page="home">Home</a></li>
                    <li><a href="#" class="nav-link" data-page="dashboard" id="dashboard-link">Dashboard</a></li>
                    <li><a href="#" class="nav-link" data-page="about">About</a></li>
                </ul>
            </nav>
            <div class="user-controls">
                <button id="login-btn" class="btn">Login</button>
                <button id="register-btn" class="btn btn-primary">Register</button>
                <div id="user-menu" class="hidden">
                    <span id="user-name"></span>
                    <button id="logout-btn" class="btn">Logout</button>
                </div>
            </div>
        </header>
        
        <main id="main-content">
            <!-- Home Page -->
            <section id="home-page" class="page active">
                <div class="hero">
                    <h2>Quantum-Resistant Data Security</h2>
                    <p>Protect your sensitive data with cutting-edge quantum-resistant encryption, biometric authentication, and blockchain verification.</p>
                    <div class="cta-buttons">
                        <button id="learn-more-btn" class="btn btn-primary">Learn More</button>
                        <button id="get-started-btn" class="btn btn-secondary">Get Started</button>
                    </div>
                </div>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-lock"></i>
                        <h3>Quantum-Resistant Encryption</h3>
                        <p>Your data is protected with post-quantum cryptographic algorithms designed to withstand attacks from quantum computers.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-fingerprint"></i>
                        <h3>Biometric Authentication</h3>
                        <p>Secure access to your data using WebAuthn biometric authentication with your fingerprint or face recognition.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-cubes"></i>
                        <h3>Blockchain Verification</h3>
                        <p>Data ownership and integrity verified through blockchain technology with NFT tokens and zero-knowledge proofs.</p>
                    </div>
                    <div class="feature">
                        <i class="fas fa-puzzle-piece"></i>
                        <h3>Data Sharding</h3>
                        <p>Your data is split into encrypted shards, making unauthorized access virtually impossible.</p>
                    </div>
                </div>
            </section>
            
            <!-- Dashboard Page -->
            <section id="dashboard-page" class="page">
                <div class="dashboard-container">
                    <div class="sidebar">
                        <ul>
                            <li><a href="#" class="dashboard-nav active" data-section="data-vault">Data Vault</a></li>
                            <li><a href="#" class="dashboard-nav" data-section="biometric">Biometric Settings</a></li>
                            <li><a href="#" class="dashboard-nav" data-section="blockchain">Blockchain</a></li>
                            <li><a href="#" class="dashboard-nav" data-section="gdpr">GDPR Controls</a></li>
                        </ul>
                    </div>
                    
                    <div class="dashboard-content">
                        <!-- Data Vault Section -->
                        <div id="data-vault-section" class="dashboard-section active">
                            <h2>Your Secure Data Vault</h2>
                            <div class="data-controls">
                                <div class="data-input">
                                    <h3>Store New Data</h3>
                                    <textarea id="data-input" placeholder="Enter sensitive data to encrypt and store..."></textarea>
                                    <button id="store-data-btn" class="btn btn-primary">Encrypt & Store</button>
                                </div>
                                
                                <div class="data-output">
                                    <h3>Retrieve Your Data</h3>
                                    <div id="data-output" class="data-display">No data retrieved yet</div>
                                    <button id="retrieve-data-btn" class="btn btn-secondary">Decrypt & Retrieve</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Biometric Settings Section -->
                        <div id="biometric-section" class="dashboard-section">
                            <h2>Biometric Authentication</h2>
                            <div class="biometric-status">
                                <p>Status: <span id="biometric-status">Not Registered</span></p>
                            </div>
                            <div class="biometric-controls">
                                <button id="register-biometric-btn" class="btn btn-primary">Register Biometric</button>
                                <button id="test-biometric-btn" class="btn btn-secondary">Test Authentication</button>
                            </div>
                            <div class="biometric-info">
                                <h3>How it works</h3>
                                <p>Biometric authentication uses WebAuthn to securely verify your identity using fingerprints, face recognition, or security keys. Your biometric data never leaves your device.</p>
                            </div>
                        </div>
                        
                        <!-- Blockchain Section -->
                        <div id="blockchain-section" class="dashboard-section">
                            <h2>Blockchain Integration</h2>
                            <div class="nft-status">
                                <h3>Your NFT Token</h3>
                                <p>Token ID: <span id="nft-token-id">Not minted yet</span></p>
                            </div>
                            <div class="zk-proof-controls">
                                <h3>Zero-Knowledge Proofs</h3>
                                <div class="zk-form">
                                    <div class="form-group">
                                        <label for="zk-attribute">Attribute:</label>
                                        <input type="text" id="zk-attribute" placeholder="e.g., age">
                                    </div>
                                    <div class="form-group">
                                        <label for="zk-value">Value:</label>
                                        <input type="text" id="zk-value" placeholder="e.g., over18">
                                    </div>
                                    <button id="generate-zk-proof-btn" class="btn btn-primary">Generate Proof</button>
                                </div>
                                <div id="zk-proof-result" class="hidden">
                                    <h4>Generated Proof:</h4>
                                    <pre id="zk-proof-display"></pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- GDPR Controls Section -->
                        <div id="gdpr-section" class="dashboard-section">
                            <h2>GDPR Data Controls</h2>
                            <div class="gdpr-controls">
                                <div class="gdpr-control">
                                    <h3>Export Your Data</h3>
                                    <p>Download all your personal data in a machine-readable format.</p>
                                    <button id="export-data-btn" class="btn btn-secondary">Export Data</button>
                                </div>
                                <div class="gdpr-control">
                                    <h3>Update Your Data</h3>
                                    <p>Modify your personal information.</p>
                                    <button id="update-data-btn" class="btn btn-secondary">Update Profile</button>
                                </div>
                                <div class="gdpr-control">
                                    <h3>Delete Your Account</h3>
                                    <p>Permanently delete all your data and account information.</p>
                                    <button id="delete-account-btn" class="btn btn-danger">Delete Account</button>
                                </div>
                            </div>
                            <div class="gdpr-logs">
                                <h3>Activity Logs</h3>
                                <table id="activity-logs-table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Timestamp</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activity-logs-body">
                                        <!-- Logs will be populated here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- About Page -->
            <section id="about-page" class="page">
                <div class="about-content">
                    <h2>About <?php echo APP_NAME; ?></h2>
                    <p>The Quantum-Resistant Data Vault is a cutting-edge secure data storage solution designed to protect your sensitive information against both current and future threats, including those posed by quantum computers.</p>
                    
                    <h3>Our Technology</h3>
                    <div class="tech-stack">
                        <div class="tech">
                            <h4>Post-Quantum Cryptography</h4>
                            <p>We implement lattice-based cryptographic algorithms that are resistant to quantum computing attacks, ensuring your data remains secure even as quantum computing advances.</p>
                        </div>
                        <div class="tech">
                            <h4>WebAuthn Biometric Authentication</h4>
                            <p>Using the Web Authentication API, we enable passwordless authentication through biometrics (fingerprint, face recognition) or security keys, providing both security and convenience.</p>
                        </div>
                        <div class="tech">
                            <h4>Blockchain Integration</h4>
                            <p>We leverage blockchain technology to create immutable records of data ownership and enable zero-knowledge proofs for privacy-preserving verification.</p>
                        </div>
                        <div class="tech">
                            <h4>Data Sharding</h4>
                            <p>Your data is split into multiple encrypted shards, distributed securely to prevent unauthorized access even if some shards are compromised.</p>
                        </div>
                    </div>
                    
                    <h3>GDPR Compliance</h3>
                    <p>We are fully compliant with the General Data Protection Regulation (GDPR), providing you with complete control over your personal data:</p>
                    <ul>
                        <li>Right to access - Export all your data in a machine-readable format</li>
                        <li>Right to rectification - Update your personal information at any time</li>
                        <li>Right to be forgotten - Permanently delete all your data from our systems</li>
                    </ul>
                    
                    <h3>Security Commitment</h3>
                    <p>We are committed to maintaining the highest standards of security and privacy. Our system undergoes regular security audits and penetration testing to ensure your data remains protected.</p>
                </div>
            </section>
        </main>
        
        <!-- Modals -->
        <!-- Login Modal -->
        <div id="login-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Login</h2>
                <form id="login-form">
                    <div class="form-group">
                        <label for="login-email">Email:</label>
                        <input type="email" id="login-email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password:</label>
                        <input type="password" id="login-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                <div id="biometric-login-container" class="hidden">
                    <hr>
                    <h3>Biometric Login</h3>
                    <p>Use your registered biometric credential to login securely.</p>
                    <button id="biometric-login-btn" class="btn btn-secondary">
                        <i class="fas fa-fingerprint"></i> Login with Biometric
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Register Modal -->
        <div id="register-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Register</h2>
                <form id="register-form">
                    <div class="form-group">
                        <label for="register-email">Email:</label>
                        <input type="email" id="register-email" required>
                    </div>
                    <div class="form-group">
                        <label for="register-first-name">First Name:</label>
                        <input type="text" id="register-first-name" required>
                    </div>
                    <div class="form-group">
                        <label for="register-last-name">Last Name:</label>
                        <input type="text" id="register-last-name" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Password:</label>
                        <input type="password" id="register-password" required>
                        <small>Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                    </div>
                    <div class="form-group">
                        <label for="register-confirm-password">Confirm Password:</label>
                        <input type="password" id="register-confirm-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
        </div>
        
        <!-- Update Profile Modal -->
        <div id="update-profile-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Update Profile</h2>
                <form id="update-profile-form">
                    <div class="form-group">
                        <label for="update-email">Email:</label>
                        <input type="email" id="update-email">
                    </div>
                    <div class="form-group">
                        <label for="update-first-name">First Name:</label>
                        <input type="text" id="update-first-name">
                    </div>
                    <div class="form-group">
                        <label for="update-last-name">Last Name:</label>
                        <input type="text" id="update-last-name">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <!-- Delete Account Confirmation Modal -->
        <div id="delete-confirm-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Delete Account</h2>
                <p>Are you sure you want to permanently delete your account and all associated data? This action cannot be undone.</p>
                <div class="modal-buttons">
                    <button id="confirm-delete-btn" class="btn btn-danger">Yes, Delete My Account</button>
                    <button id="cancel-delete-btn" class="btn">Cancel</button>
                </div>
            </div>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact</a>
            </div>
        </footer>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/app.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/biometric.js"></script>
    <script src="js/data.js"></script>
    <script src="js/blockchain.js"></script>
    <script src="js/gdpr.js"></script>
</body>
</html>