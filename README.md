# Quantum-Resistant Data Vault

A secure data storage system implementing quantum-resistant cryptography with biometric authentication and blockchain token linkage.

## Features

- **Quantum-Resistant Encryption**: Uses Kyber-1024 and CRYSTALS-Dilithium algorithms
- **Biometric Authentication**: Implements WebAuthn for secure biometric verification
- **Zero-Knowledge Proofs**: Allows selective attribute disclosure without revealing all data
- **Blockchain Integration**: Links user data to ERC-721 tokens on Polygon
- **Data Sharding**: Splits and encrypts user data across multiple storage locations
- **GDPR Compliance**: Built-in tools for data deletion and user rights management

## Project Structure

```
/
├── api/                  # API endpoints
│   ├── register.php      # User registration
│   ├── login.php         # Authentication
│   ├── store-biometric.php # Biometric data storage
│   ├── shard.php         # Data sharding operations
│   └── delete_user.php   # GDPR-compliant deletion
├── includes/             # Core functionality
│   ├── config.php        # Configuration settings
│   ├── Database.php      # Database connection
│   ├── User.php          # User model
│   ├── Encryption.php    # Quantum-resistant encryption
│   ├── Biometric.php     # Biometric authentication
│   ├── Sharding.php      # Data sharding implementation
│   ├── Blockchain.php    # Blockchain integration
│   └── GDPR.php          # GDPR compliance tools
├── public/               # Public-facing files
│   ├── index.php         # Entry point
│   ├── register.php      # Registration page
│   ├── login.php         # Login page
│   ├── dashboard.php     # User dashboard
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   │   ├── biometric.js  # Biometric enrollment
│   │   └── vault.js      # NFT minting and vault operations
│   └── img/              # Images
└── vendor/               # Dependencies
```

## Installation

1. Clone the repository
2. Set up XAMPP environment
3. Import the database schema
4. Configure environment variables
5. Install dependencies

## Technologies Used

- **Backend**: PHP 8.0+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Encryption**: Kyber-1024, CRYSTALS-Dilithium
- **Authentication**: WebAuthn
- **Blockchain**: Polygon (Ethereum L2)
- **Zero-Knowledge Proofs**: ZoKrates

## Security Features

- Post-quantum cryptographic algorithms
- Biometric verification
- Zero-knowledge proofs for selective disclosure
- Data sharding and distributed storage
- Blockchain-based access control
- GDPR compliance tools

## License

MIT