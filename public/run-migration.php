<?php
/**
 * Migration Runner
 * Run once: http://localhost/online/public/run-migration.php
 */
require_once '../config/config.php';

global $conn;

echo '<html><head><style>
body{font-family:monospace;background:#0f0e1a;color:#f1f5f9;padding:30px;line-height:1.8;}
.ok{color:#4ade80;} .err{color:#f87171;} .skip{color:#94a3b8;}
h2{color:#818cf8;} hr{border-color:rgba(255,255,255,0.1);}
a{color:#818cf8;}
</style></head><body>';
echo '<h2>🔧 Hostel System — Migration Runner</h2><hr>';

/**
 * Run a query and print result
 */
function runQuery(string $label, string $sql): void {
    global $conn;
    echo "<p><strong>→ {$label}</strong><br>";
    if ($conn->query($sql)) {
        echo "<span class='ok'>✅ Done.</span></p>";
    } else {
        echo "<span class='err'>❌ " . htmlspecialchars($conn->error) . "</span></p>";
    }
}

/**
 * Check if a column already exists in a table
 */
function columnExists(string $table, string $column): bool {
    global $conn;
    $db  = DB_NAME;
    $res = $conn->query(
        "SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = '{$db}'
           AND TABLE_NAME   = '{$table}'
           AND COLUMN_NAME  = '{$column}'
         LIMIT 1"
    );
    return $res && $res->num_rows > 0;
}

/**
 * Check if an index already exists on a table
 */
function indexExists(string $table, string $index): bool {
    global $conn;
    $res = $conn->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$index}'");
    return $res && $res->num_rows > 0;
}

// ── 1. Add 'card' to payment_method ENUM ──────────────────────────────────
runQuery(
    "Add 'card' to payment_method ENUM",
    "ALTER TABLE payments
     MODIFY COLUMN payment_method
     ENUM('cash','mtn_momo','airtel_money','pesapal','card') DEFAULT 'cash'"
);

// ── 2. Add receipt_token column (MySQL-safe check) ─────────────────────────
if (columnExists('payments', 'receipt_token')) {
    echo "<p><strong>→ Add receipt_token column to payments</strong><br><span class='skip'>⏭ Already exists, skipped.</span></p>";
} else {
    runQuery(
        "Add receipt_token column to payments",
        "ALTER TABLE payments ADD COLUMN receipt_token VARCHAR(64) NULL AFTER notes"
    );
}

// ── 3. Index on receipt_token ──────────────────────────────────────────────
if (indexExists('payments', 'idx_receipt_token')) {
    echo "<p><strong>→ Index on receipt_token</strong><br><span class='skip'>⏭ Already exists, skipped.</span></p>";
} else {
    runQuery(
        "Index on receipt_token",
        "ALTER TABLE payments ADD INDEX idx_receipt_token (receipt_token)"
    );
}

// ── 4. Index on transaction_reference ─────────────────────────────────────
if (indexExists('payments', 'idx_txn_ref')) {
    echo "<p><strong>→ Index on transaction_reference</strong><br><span class='skip'>⏭ Already exists, skipped.</span></p>";
} else {
    runQuery(
        "Index on transaction_reference",
        "ALTER TABLE payments ADD INDEX idx_txn_ref (transaction_reference)"
    );
}

// ── 5. Create app_options table ────────────────────────────────────────────
runQuery(
    "Create app_options table",
    "CREATE TABLE IF NOT EXISTS app_options (
        option_key   VARCHAR(100) PRIMARY KEY,
        option_value TEXT,
        updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

// ── 6. Backfill receipt_token for existing completed payments ──────────────
runQuery(
    "Backfill receipt_token for existing completed payments",
    "UPDATE payments
     SET receipt_token = SHA2(CONCAT(payment_id, booking_id, amount), 256)
     WHERE status = 'completed' AND (receipt_token IS NULL OR receipt_token = '')"
);

echo '<hr><p class="ok">✅ All migrations complete. Safe to run again — existing columns/indexes are skipped.</p>';
echo '<p><a href="dashboard.php">← Back to Dashboard</a></p>';
echo '</body></html>';
