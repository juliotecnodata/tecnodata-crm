-- Tecnodata CRM V6.3
ALTER TABLE tasks ADD COLUMN pre_notified_at DATETIME NULL AFTER status;
ALTER TABLE tasks ADD COLUMN due_notified_at DATETIME NULL AFTER pre_notified_at;
ALTER TABLE tasks ADD COLUMN reminder_notified_at DATETIME NULL AFTER due_notified_at;
