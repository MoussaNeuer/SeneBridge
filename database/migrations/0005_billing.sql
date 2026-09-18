-- SeneBridge — Migration 0005 : Facturation et paiements (Wave Business MVP)

CREATE TABLE invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    number VARCHAR(30) NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'XOF',
    issue_date DATE NOT NULL,
    due_date DATE NULL DEFAULT NULL,
    status ENUM('brouillon','envoyee','partielle','payee','en_retard','annulee') NOT NULL DEFAULT 'brouillon',
    description TEXT NULL DEFAULT NULL,
    paid_at DATETIME NULL DEFAULT NULL,
    created_by BIGINT UNSIGNED NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY invoices_public_id_unique (public_id),
    UNIQUE KEY invoices_number_unique (number),
    KEY invoices_client_id_index (client_id),
    KEY invoices_project_id_index (project_id),
    KEY invoices_status_index (status),
    CONSTRAINT invoices_client_id_foreign FOREIGN KEY (client_id)
        REFERENCES users (id),
    CONSTRAINT invoices_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT invoices_created_by_foreign FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COLLATE ascii_bin,
    invoice_id BIGINT UNSIGNED NULL DEFAULT NULL,
    project_id BIGINT UNSIGNED NULL DEFAULT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'XOF',
    payment_date DATE NOT NULL,
    method ENUM('wave_business','bank_transfer','cash','other') NOT NULL DEFAULT 'other',
    reference VARCHAR(191) NULL DEFAULT NULL,
    status ENUM('en_cours','valide','rejete','rembourse') NOT NULL DEFAULT 'en_cours',
    receipt_internal_name VARCHAR(255) NULL DEFAULT NULL,
    notes TEXT NULL DEFAULT NULL,
    recorded_by BIGINT UNSIGNED NULL DEFAULT NULL,
    validated_at DATETIME NULL DEFAULT NULL,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY payments_public_id_unique (public_id),
    KEY payments_invoice_id_index (invoice_id),
    KEY payments_project_id_index (project_id),
    KEY payments_client_id_index (client_id),
    KEY payments_status_index (status),
    CONSTRAINT payments_invoice_id_foreign FOREIGN KEY (invoice_id)
        REFERENCES invoices (id) ON DELETE SET NULL,
    CONSTRAINT payments_project_id_foreign FOREIGN KEY (project_id)
        REFERENCES projects (id) ON DELETE SET NULL,
    CONSTRAINT payments_client_id_foreign FOREIGN KEY (client_id)
        REFERENCES users (id),
    CONSTRAINT payments_recorded_by_foreign FOREIGN KEY (recorded_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;