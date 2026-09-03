-- Tecnodata CRM V6.4
ALTER TABLE activities MODIFY result ENUM('falou','nao_atendeu','interessado','sem_interesse','acordo') NOT NULL;
-- Para instalações existentes, use preferencialmente public/upgrade-v6.4.php,
-- que adiciona colunas de forma idempotente e reconstrói o pending_amount da cobrança.
