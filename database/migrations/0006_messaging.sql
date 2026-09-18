-- SeneBridge — Migration 0006 : Messagerie

CREATE TABLE conversations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(191) NULL DEFAULT NULL,
    status ENUM('ouverte','archive') NOT NULL DEFAULT 'ouverte',
    created_by BIGINT UNSIGNED NOT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY conversations_public_id_unique (public_id),
    KEY conversations_project_id_index (project_id),
    KEY conversations_client_id_index (client_id),
    CONSTRAINT conversations_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT conversations_client_id_foreign FOREIGN KEY (client_id)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE conversation_participants (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    last_read_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY conversation_participants_conversation_user_unique (conversation_id, user_id),
    KEY conversation_participants_user_id_index (user_id),
    CONSTRAINT conversation_participants_conversation_id_foreign FOREIGN KEY (conversation_id)
        REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT conversation_participants_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY messages_public_id_unique (public_id),
    KEY messages_conversation_id_index (conversation_id),
    KEY messages_sender_id_index (sender_id),
    KEY messages_is_read_index (is_read),
    CONSTRAINT messages_conversation_id_foreign FOREIGN KEY (conversation_id)
        REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT messages_sender_id_foreign FOREIGN KEY (sender_id)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE message_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    message_id BIGINT UNSIGNED NOT NULL,
    internal_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL DEFAULT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY message_attachments_message_id_index (message_id),
    CONSTRAINT message_attachments_message_id_foreign FOREIGN KEY (message_id)
        REFERENCES messages (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;