-- SeneBridge — Migration 0013 : index de performance sur le journal d'audit.

ALTER TABLE audit_logs
    ADD KEY audit_logs_user_created_index (user_id, created_at);