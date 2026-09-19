-- SeneBridge — Migration 0012 : index composites pour les chemins d'accès chauds
-- (messagerie, listes du back-office).

ALTER TABLE messages
    DROP KEY messages_is_read_index,
    ADD KEY messages_conversation_read_index (conversation_id, is_read, created_at),
    ADD KEY messages_sender_created_index (sender_id, created_at);

ALTER TABLE conversations
    DROP KEY conversations_client_id_index,
    ADD KEY conversations_client_status_index (client_id, status, updated_at);

ALTER TABLE projects
    DROP KEY projects_status_index,
    ADD KEY projects_client_status_index (client_id, status);