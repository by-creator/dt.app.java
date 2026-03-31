-- Alter audit_logs table to increase controller_action column size
ALTER TABLE audit_logs MODIFY COLUMN controller_action TEXT;