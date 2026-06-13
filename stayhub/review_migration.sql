-- ============================================================
-- StayHub — Reviews Enhancement Migration (Features 1-4)
-- Run once against your stayhub database in SQL Server
-- ============================================================
USE stayhub;

-- Add missing columns to reviews table
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='reviews' AND COLUMN_NAME='title')
    ALTER TABLE reviews ADD title NVARCHAR(255) NULL;

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='reviews' AND COLUMN_NAME='photos')
    ALTER TABLE reviews ADD photos NVARCHAR(2000) NULL; -- JSON array of URLs

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='reviews' AND COLUMN_NAME='status')
    ALTER TABLE reviews ADD status NVARCHAR(20) DEFAULT 'pending';

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='reviews' AND COLUMN_NAME='is_featured')
    ALTER TABLE reviews ADD is_featured TINYINT DEFAULT 0;

-- Backfill existing reviews as 'approved' so they still show
UPDATE reviews SET status = 'approved' WHERE status IS NULL OR status = '';

PRINT 'Reviews table enhanced successfully.';
