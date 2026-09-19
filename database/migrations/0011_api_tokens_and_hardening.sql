-- SeneBridge — Migration 0011 : API REST (tokens) + invalidation de sessions + index de performance

-- Tokens d'accès à l'API REST /api/v1 (seul le hash SHA-256 est stocké).
CREATE TABLE api_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL DEFAULT 'api',
    token_hash CHAR(64) NOT NULL COLLATE ascii_bin,
    last_used_at DATETIME NULL DEFAULT NULL,
    expires_at DATETIME NULL DEFAULT NULL,
    revoked_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY api_tokens_token_hash_unique (token_hash),
    KEY api_tokens_user_id_index (user_id),
    CONSTRAINT api_tokens_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Version de session : incrémentée au changement de mot de passe pour invalider
-- toutes les autres sessions (et les tokens API éventuellement actifs).
ALTER TABLE users
    ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0 AFTER language;

-- Index composés pour les requêtes de pilotage / API.
ALTER TABLE notifications
    ADD KEY notifications_user_read_index (user_id, is_read, created_at);

ALTER TABLE appointments
    ADD KEY appointments_requested_date_index (requested_date, status);

ALTER TABLE payments
    ADD KEY payments_payment_date_index (payment_date, status);