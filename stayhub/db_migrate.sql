-- ============================================================
-- StayHub DB Migration — Features 8–15
-- Run this once against your stayhub database
-- ============================================================
USE stayhub;

-- ── Feature 8: Reviews & Ratings ──────────────────────────
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='reviews' AND xtype='U')
CREATE TABLE reviews (
    id            INT IDENTITY(1,1) PRIMARY KEY,
    listing_id    INT NOT NULL,
    user_id       INT NOT NULL,
    reservation_id INT NOT NULL,
    rating        TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment       NVARCHAR(1000),
    host_reply    NVARCHAR(1000),          -- Feature 14: host can reply
    host_replied_at DATETIME,
    created_at    DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (listing_id)    REFERENCES listings(id)    ON DELETE CASCADE,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE NO ACTION,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE NO ACTION
);

-- ── Feature 13: Wishlist / Saved listings ─────────────────
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='wishlists' AND xtype='U')
CREATE TABLE wishlists (
    id         INT IDENTITY(1,1) PRIMARY KEY,
    user_id    INT NOT NULL,
    listing_id INT NOT NULL,
    created_at DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT uq_wishlist UNIQUE (user_id, listing_id)
);

-- ── Add status column to listings if missing ──────────────
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME='listings' AND COLUMN_NAME='status'
)
ALTER TABLE listings ADD status VARCHAR(20) DEFAULT 'active';

-- Backfill existing rows
UPDATE listings SET status = 'active' WHERE status IS NULL;

-- ── Ensure notifications table exists ─────────────────────
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='notifications' AND xtype='U')
CREATE TABLE notifications (
    id         INT IDENTITY(1,1) PRIMARY KEY,
    user_id    INT NOT NULL,
    title      NVARCHAR(200),
    message    NVARCHAR(500),
    is_read    TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
