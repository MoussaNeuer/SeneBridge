-- SeneBridge — Migration 0008 : Journal d'audit

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(60) NULL DEFAULT NULL,
    entity_id VARCHAR(60) NULL DEFAULT NULL,
    old_values JSON NULL DEFAULT NULL,
    new_values JSON NULL DEFAULT NULL,
    ip_address VARCHAR(45) NULL DEFAULT NULL,
    user_agent VARCHAR(255) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY audit_logs_user_id_index (user_id),
    KEY audit_logs_action_index (action),
    KEY audit_logs_entity_index (entity_type, entity_id),
    KEY audit_logs_created_at_index (created_at),
    CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;