-- ============================================================
-- StayHub Addons Migration
-- ============================================================
USE stayhub;

-- ── Addon 3: Payment deadline fields ──────────────────────
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='reservations' AND COLUMN_NAME='expires_at'
)
ALTER TABLE reservations ADD expires_at DATETIME NULL;

-- Set expires_at for existing pending reservations (48h from created_at)
UPDATE reservations
SET expires_at = DATEADD(HOUR, 48, created_at)
WHERE status = 'pending' AND expires_at IS NULL;

-- ── Addon 3: Index for fast expiry queries ─────────────────
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name='ix_res_expires' AND object_id = OBJECT_ID('reservations'))
CREATE INDEX ix_res_expires ON reservations (status, expires_at);
