-- SeneBridge — Migration 0002 : Projets et biens

CREATE TABLE projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    client_id BIGINT UNSIGNED NOT NULL,
    reference VARCHAR(30) NOT NULL,
    name VARCHAR(191) NOT NULL,
    type ENUM('immobilier','gestion_projets','import_export','auto','conciergerie','investissement') NOT NULL DEFAULT 'immobilier',
    description TEXT NULL DEFAULT NULL,
    locality VARCHAR(191) NULL DEFAULT NULL,
    status ENUM('en_cours','bloque','termine','archive') NOT NULL DEFAULT 'en_cours',
    budget DECIMAL(15,2) NULL DEFAULT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'XOF',
    counselor_id BIGINT UNSIGNED NULL DEFAULT NULL,
    start_date DATE NULL DEFAULT NULL,
    expected_end_date DATE NULL DEFAULT NULL,
    closed_at DATETIME NULL DEFAULT NULL,
    archived_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY projects_public_id_unique (public_id),
    UNIQUE KEY projects_reference_unique (reference),
    KEY projects_client_id_index (client_id),
    KEY projects_counselor_id_index (counselor_id),
    KEY projects_status_index (status),
    CONSTRAINT projects_client_id_foreign FOREIGN KEY (client_id)
        REFERENCES users (id),
    CONSTRAINT projects_counselor_id_foreign FOREIGN KEY (counselor_id)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE properties (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    owner_client_id BIGINT UNSIGNED NULL DEFAULT NULL,
    type ENUM('terrain','appartement','maison','villa','local_commercial','autre') NOT NULL DEFAULT 'autre',
    name VARCHAR(191) NOT NULL,
    description TEXT NULL DEFAULT NULL,
    features JSON NULL DEFAULT NULL,
    locality VARCHAR(191) NULL DEFAULT NULL,
    price DECIMAL(15,2) NULL DEFAULT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'XOF',
    status ENUM('disponible','sous_offre','reserve','vendu','loue','archive') NOT NULL DEFAULT 'disponible',
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY properties_public_id_unique (public_id),
    KEY properties_project_id_index (project_id),
    KEY properties_owner_client_id_index (owner_client_id),
    KEY properties_status_index (status),
    CONSTRAINT properties_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT properties_owner_client_id_foreign FOREIGN KEY (owner_client_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;