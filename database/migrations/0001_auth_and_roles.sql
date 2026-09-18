-- SeneBridge — Migration 0001 : Identité, authentification et rôles
-- Statut : appliquée par database/migrator

CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    name VARCHAR(60) NOT NULL,
    label VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL DEFAULT NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY roles_public_id_unique (public_id),
    UNIQUE KEY roles_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    name VARCHAR(100) NOT NULL,
    label VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY permissions_public_id_unique (public_id),
    UNIQUE KEY permissions_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    KEY role_permissions_permission_id_index (permission_id),
    CONSTRAINT role_permissions_role_id_foreign FOREIGN KEY (role_id)
        REFERENCES roles (id) ON DELETE CASCADE,
    CONSTRAINT role_permissions_permission_id_foreign FOREIGN KEY (permission_id)
        REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    role_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(60) NOT NULL,
    last_name VARCHAR(60) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) NULL DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'pending', 'suspended') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL DEFAULT NULL,
    locality VARCHAR(191) NULL DEFAULT NULL,
    language VARCHAR(10) NOT NULL DEFAULT 'fr',
    timezone VARCHAR(60) NOT NULL DEFAULT 'Africa/Dakar',
    last_login_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_public_id_unique (public_id),
    UNIQUE KEY users_email_unique (email),
    UNIQUE KEY users_phone_unique (phone),
    KEY users_role_id_index (role_id),
    KEY users_status_index (status),
    CONSTRAINT users_role_id_foreign FOREIGN KEY (role_id)
        REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL COLLATE ascii_bin,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email_verification_tokens_token_hash_unique (token_hash),
    KEY email_verification_tokens_user_id_index (user_id),
    KEY email_verification_tokens_expires_at_index (expires_at),
    CONSTRAINT email_verification_tokens_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL COLLATE ascii_bin,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY password_reset_tokens_token_hash_unique (token_hash),
    KEY password_reset_tokens_user_id_index (user_id),
    KEY password_reset_tokens_expires_at_index (expires_at),
    CONSTRAINT password_reset_tokens_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    token_hash CHAR(64) NULL DEFAULT NULL COLLATE ascii_bin,
    ip_address VARCHAR(45) NULL DEFAULT NULL,
    user_agent VARCHAR(255) NULL DEFAULT NULL,
    is_valid TINYINT(1) NOT NULL DEFAULT 1,
    expired_at DATETIME NULL DEFAULT NULL,
    last_activity_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY sessions_public_id_unique (public_id),
    KEY sessions_user_id_index (user_id),
    KEY sessions_is_valid_index (is_valid),
    CONSTRAINT sessions_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(191) NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    reset_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY rate_limits_key_unique (`key`),
    KEY rate_limits_reset_at_index (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;