-- SeneBridge — Migration 0003 : Workflows (étapes et historique)

CREATE TABLE project_steps (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(191) NOT NULL,
    description TEXT NULL DEFAULT NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('a_venir','en_cours','termine','bloque') NOT NULL DEFAULT 'a_venir',
    responsible_id BIGINT UNSIGNED NULL DEFAULT NULL,
    due_date DATE NULL DEFAULT NULL,
    started_at DATETIME NULL DEFAULT NULL,
    completed_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY project_steps_project_id_index (project_id),
    KEY project_steps_status_index (status),
    UNIQUE KEY project_steps_project_position_unique (project_id, position),
    CONSTRAINT project_steps_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT project_steps_responsible_id_foreign FOREIGN KEY (responsible_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_step_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_step_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    action VARCHAR(60) NOT NULL,
    comment TEXT NULL DEFAULT NULL,
    from_status VARCHAR(20) NULL DEFAULT NULL,
    to_status VARCHAR(20) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY project_step_history_step_id_index (project_step_id),
    KEY project_step_history_user_id_index (user_id),
    CONSTRAINT project_step_history_step_id_foreign FOREIGN KEY (project_step_id)
        REFERENCES project_steps (id) ON DELETE CASCADE,
    CONSTRAINT project_step_history_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;