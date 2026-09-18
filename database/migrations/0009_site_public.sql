-- SeneBridge — Migration 0009 : Site public (articles, messages contact, demandes de projet)

CREATE TABLE articles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    title VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    excerpt VARCHAR(500) NULL DEFAULT NULL,
    body MEDIUMTEXT NOT NULL,
    cover_url VARCHAR(500) NULL DEFAULT NULL,
    status ENUM('brouillon','publie') NOT NULL DEFAULT 'brouillon',
    author_id BIGINT UNSIGNED NULL DEFAULT NULL,
    published_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY articles_public_id_unique (public_id),
    UNIQUE KEY articles_slug_unique (slug),
    KEY articles_status_index (status),
    KEY articles_author_id_index (author_id),
    CONSTRAINT articles_author_id_foreign FOREIGN KEY (author_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(40) NULL DEFAULT NULL,
    subject VARCHAR(191) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY contact_messages_public_id_unique (public_id),
    KEY contact_messages_email_index (email),
    KEY contact_messages_is_read_index (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    user_id BIGINT UNSIGNED NULL DEFAULT NULL,
    intent ENUM('je_cherche_un_bien','jai_un_projet','accompagnement') NOT NULL DEFAULT 'jai_un_projet',
    project_type ENUM('immobilier','gestion_projets','import_export','auto','conciergerie','investissement') NOT NULL DEFAULT 'immobilier',
    first_name VARCHAR(191) NOT NULL,
    last_name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(40) NULL DEFAULT NULL,
    locality VARCHAR(191) NULL DEFAULT NULL,
    budget DECIMAL(15,2) NULL DEFAULT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'XOF',
    description TEXT NULL DEFAULT NULL,
    status ENUM('nouveau','qualification','converti','archive') NOT NULL DEFAULT 'nouveau',
    handled_by BIGINT UNSIGNED NULL DEFAULT NULL,
    handled_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY project_requests_public_id_unique (public_id),
    KEY project_requests_user_id_index (user_id),
    KEY project_requests_status_index (status),
    KEY project_requests_intent_index (intent),
    CONSTRAINT project_requests_user_id_foreign FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT project_requests_handled_by_foreign FOREIGN KEY (handled_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;