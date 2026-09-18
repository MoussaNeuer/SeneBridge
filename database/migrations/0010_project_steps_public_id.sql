ALTER TABLE project_steps
    ADD COLUMN public_id CHAR(26) NULL DEFAULT NULL AFTER id,
    ADD UNIQUE KEY project_steps_public_id_unique (public_id);