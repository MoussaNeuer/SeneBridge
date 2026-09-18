-- SeneBridge — Migration 0007 : Notifications et rendez-vous

CREATE TABLE notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    type VARCHAR(60) NOT NULL,
    title VARCHAR(191) NOT NULL,
    body TEXT NULL DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY notifications_public_id_unique (public_id),
    KEY notifications_user_id_index (user_id),
    KEY notifications_project_id_index (project_id),
    KEY notifications_is_read_index (is_read),
    CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT notifications_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    counselor_id BIGINT UNSIGNED NOT NULL,
    requested_date DATE NOT NULL,
    requested_time TIME NOT NULL,
    motive VARCHAR(255) NULL DEFAULT NULL,
    status ENUM('demande','confirme','annule','termine') NOT NULL DEFAULT 'demande',
    notes TEXT NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY appointments_public_id_unique (public_id),
    KEY appointments_client_id_index (client_id),
    KEY appointments_counselor_id_index (counselor_id),
    KEY appointments_project_id_index (project_id),
    KEY appointments_status_index (status),
    CONSTRAINT appointments_client_id_foreign FOREIGN KEY (client_id)
        REFERENCES users (id),
    CONSTRAINT appointments_counselor_id_foreign FOREIGN KEY (counselor_id)
        REFERENCES users (id),
    CONSTRAINT appointments_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;