-- Migration to support custom URLs
-- Run this script to update existing databases to support longer share tokens

-- Modify the share_token column to support custom URLs up to 20 characters
ALTER TABLE notes MODIFY COLUMN share_token VARCHAR(20) NULL;

-- Add unique index on share_token to ensure no duplicates
-- (Drop first if it exists)
DROP INDEX IF EXISTS idx_notes_share_token ON notes;
CREATE UNIQUE INDEX idx_notes_share_token ON notes(share_token);

-- Show updated table structure
DESCRIBE notes;