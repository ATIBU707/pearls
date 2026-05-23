-- =====================================================
-- Migration: PesaPal v3 Integration Fixes
-- Run this via phpMyAdmin or any MySQL client
-- =====================================================

-- 1. Add 'card' to payment_method enum
ALTER TABLE payments
    MODIFY COLUMN payment_method
        ENUM('cash','mtn_momo','airtel_money','pesapal','card')
        DEFAULT 'cash';

-- 2. Add index on transaction_reference for faster receipt token lookups
ALTER TABLE payments
    ADD INDEX IF NOT EXISTS idx_txn_ref (transaction_reference);

-- 3. Add receipt_token column to payments for O(1) QR verification
ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS receipt_token VARCHAR(64) NULL AFTER notes,
    ADD INDEX IF NOT EXISTS idx_receipt_token (receipt_token);

-- 4. Create app_options table (for PesaPal IPN ID caching, etc.)
CREATE TABLE IF NOT EXISTS app_options (
    option_key   VARCHAR(100) PRIMARY KEY,
    option_value TEXT,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Backfill receipt_token for any existing completed payments
UPDATE payments
SET receipt_token = SHA2(CONCAT(payment_id, booking_id, amount), 256)
WHERE status = 'completed' AND receipt_token IS NULL;
