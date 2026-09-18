-- SeneBridge — Migration 0004 : Médias et documents (stockage privé)

CREATE TABLE media (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    property_id BIGINT UNSIGNED NULL DEFAULT NULL,
    uploader_id BIGINT UNSIGNED NOT NULL,
    type ENUM('image','video','document') NOT NULL DEFAULT 'image',
    category VARCHAR(60) NULL DEFAULT NULL,
    internal_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL DEFAULT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    width INT UNSIGNED NULL DEFAULT NULL,
    height INT UNSIGNED NULL DEFAULT NULL,
    alt_text VARCHAR(255) NULL DEFAULT NULL,
    visibility ENUM('private','client','admin') NOT NULL DEFAULT 'private',
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY media_public_id_unique (public_id),
    KEY media_project_id_index (project_id),
    KEY media_property_id_index (property_id),
    KEY media_uploader_id_index (uploader_id),
    KEY media_visibility_index (visibility),
    CONSTRAINT media_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT media_property_id_foreign FOREIGN KEY (property_id)
        REFERENCES properties (id) ON DELETE CASCADE,
    CONSTRAINT media_uploader_id_foreign FOREIGN KEY (uploader_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    property_id BIGINT UNSIGNED NULL DEFAULT NULL,
    uploader_id BIGINT UNSIGNED NOT NULL,
    category ENUM('contrat','rapport','justificatif','recu','facture','autre') NOT NULL DEFAULT 'autre',
    internal_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL DEFAULT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    visibility ENUM('private','client','admin') NOT NULL DEFAULT 'private',
    status ENUM('brouillon','final','archive') NOT NULL DEFAULT 'brouillon',
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY documents_public_id_unique (public_id),
    KEY documents_project_id_index (project_id),
    KEY documents_property_id_index (property_id),
    KEY documents_uploader_id_index (uploader_id),
    KEY documents_visibility_index (visibility),
    CONSTRAINT documents_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE CASCADE,
    CONSTRAINT documents_property_id_foreign FOREIGN KEY (property_id)
        REFERENCES properties (id) ON DELETE CASCADE,
    CONSTRAINT documents_uploader_id_foreign FOREIGN KEY (uploader_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    internal_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL DEFAULT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY document_versions_document_version_unique (document_id, version_number),
    KEY document_versions_user_id_index (user_id),
    CONSTRAINT document_versions_document_id_foreign FOREIGN KEY (document_id)
        REFERENCES documents (id) ON DELETE CASCADE,
    CONSTRAINT document_versions_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;