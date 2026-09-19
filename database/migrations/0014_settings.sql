-- SeneBridge — Migration 0014 : Paramètres de la plateforme (hub Paramètres)

CREATE TABLE settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(120) NOT NULL,
    `value` LONGTEXT NULL,
    `type` ENUM('string','int','bool','email','url','json','secret','timezone') NOT NULL DEFAULT 'string',
    `section` VARCHAR(60) NOT NULL DEFAULT 'general',
    label VARCHAR(190) NULL DEFAULT NULL,
    description TEXT NULL,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL DEFAULT NULL,
    updated_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY settings_key_unique (`key`),
    KEY settings_section_index (`section`),
    CONSTRAINT settings_updated_by_foreign FOREIGN KEY (updated_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;