-- ============================================================
-- StayHub — Payments & Invoices Migration (SQL Server)
-- Run this once in SQL Server Management Studio
-- ============================================================
USE stayhub;

-- ── payments table ─────────────────────────────────────────
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='payments' AND xtype='U')
CREATE TABLE payments (
    id               INT IDENTITY(1,1) PRIMARY KEY,
    reservation_id   INT NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    payment_method   NVARCHAR(50) NOT NULL DEFAULT 'Card',
    payment_status   NVARCHAR(50) NOT NULL DEFAULT 'completed',
    created_at       DATETIME DEFAULT GETDATE(),
    CONSTRAINT fk_pay_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

-- ── invoices table ─────────────────────────────────────────
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='invoices' AND xtype='U')
CREATE TABLE invoices (
    id              INT IDENTITY(1,1) PRIMARY KEY,
    payment_id      INT NOT NULL,
    invoice_number  NVARCHAR(100) NOT NULL,
    tax_amount      DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    issued_at       DATETIME DEFAULT GETDATE(),
    CONSTRAINT uq_invoice_number UNIQUE (invoice_number),
    CONSTRAINT fk_inv_payment
        FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

-- ── Index for fast payment lookups ─────────────────────────
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name='ix_pay_reservation' AND object_id = OBJECT_ID('payments'))
CREATE INDEX ix_pay_reservation ON payments (reservation_id);

PRINT 'payments and invoices tables ready.';
