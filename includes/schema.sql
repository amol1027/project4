-- Database schema for Quantum-Resistant Data Vault

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `biometric_registered` tinyint(1) DEFAULT 0,
  `nft_token_id` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Biometric credentials table
CREATE TABLE IF NOT EXISTS `biometric_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` text NOT NULL,
  `attestation_type` varchar(50) NOT NULL,
  `sign_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `last_used` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id` (`credential_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `biometric_credentials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data shards table
CREATE TABLE IF NOT EXISTS `data_shards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shard_id` varchar(64) NOT NULL,
  `shard_data` text NOT NULL,
  `shard_index` int(11) NOT NULL,
  `total_shards` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_shard` (`user_id`, `shard_id`, `shard_index`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `data_shards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Encryption keys table
CREATE TABLE IF NOT EXISTS `encryption_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `key_type` varchar(50) NOT NULL,
  `encrypted_key` text NOT NULL,
  `public_key` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `encryption_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blockchain tokens table
CREATE TABLE IF NOT EXISTS `blockchain_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_id` varchar(255) NOT NULL,
  `contract_address` varchar(255) NOT NULL,
  `network` varchar(50) NOT NULL,
  `metadata` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_id` (`token_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `blockchain_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zero-knowledge proofs table
CREATE TABLE IF NOT EXISTS `zk_proofs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_id` varchar(255) NOT NULL,
  `attribute` varchar(100) NOT NULL,
  `proof_data` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token_id` (`token_id`),
  CONSTRAINT `zk_proofs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `zk_proofs_ibfk_2` FOREIGN KEY (`token_id`) REFERENCES `blockchain_tokens` (`token_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GDPR logs table
CREATE TABLE IF NOT EXISTS `gdpr_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `timestamp` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit logs table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;