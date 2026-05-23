<?php
/**
 * Payment Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class Payment extends Model {
    public function __construct() {
        parent::__construct('payments', 'payment_id');
    }

    /**
     * Create a new payment record
     */
    public function createPayment($data) {
        $data['status'] = 'pending';
        $data['payment_date'] = date('Y-m-d H:i:s');
        return $this->insert($data);
    }

    /**
     * Get payment by transaction reference
     */
    public function getByReference($reference) {
        $sql = "SELECT * FROM payments WHERE transaction_reference = ?";
        return $this->queryOne($sql, [$reference]);
    }

    /**
     * Update payment status — auto-generates receipt_token on completion
     */
    public function updateStatus($payment_id, $status, $reference = null) {
        if ($status === 'completed') {
            // Fetch amount + booking_id for token generation
            $rec = $this->queryOne("SELECT booking_id, amount FROM {$this->table} WHERE payment_id = ?", [$payment_id]);
            $token = $rec ? hash('sha256', $payment_id . $rec['booking_id'] . $rec['amount']) : null;
            $sql  = "UPDATE {$this->table} SET status = ?, transaction_reference = ?, receipt_token = ? WHERE payment_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param('sssi', $status, $reference, $token, $payment_id);
            return $stmt->execute();
        }
        $sql  = "UPDATE {$this->table} SET status = ?, transaction_reference = ? WHERE payment_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ssi', $status, $reference, $payment_id);
        return $stmt->execute();
    }
}
