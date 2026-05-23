<?php
/**
 * Initiate Payment Page — PesaPal v3 Mobile Money
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/models/Booking.php';
require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/services/PesapalService.php';

requireLogin();

$booking_id = (int)($_GET['booking_id'] ?? 0);
if (!$booking_id) { header('Location: ../payments.php'); exit; }

$bookingModel = new Booking();
$paymentModel = new Payment();
$booking      = $bookingModel->getDetails($booking_id);

if (!$booking || $booking['user_id'] != getCurrentUserId()) {
    redirectWithMessage('../payments.php', 'Booking not found or access denied.', 'error');
}

// Already paid?
$existing = getRow(
    "SELECT * FROM payments WHERE booking_id = ? AND status = 'completed'",
    [$booking_id]
);
if ($existing) {
    redirectWithMessage('../payments.php', 'This booking is already paid.', 'info');
}

$error = '';

// Calculate already paid amount for this booking
$already_paid = (float)(getValue(
    "SELECT COALESCE(SUM(amount),0) FROM payments WHERE booking_id = ? AND status = 'completed'",
    [$booking_id]
) ?? 0);
$total_due      = (float)$booking['price_per_semester'];
$balance_due    = max(0, $total_due - $already_paid);
$min_deposit    = max(1000, round($balance_due * 0.1)); // minimum 10% or UGX 1,000

// ── Handle form submission ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $method        = $_POST['method'] ?? '';
    $phone         = trim($_POST['phone'] ?? '');
    $deposit_input = trim($_POST['deposit_amount'] ?? '');
    $deposit_amount = (float)str_replace(',', '', $deposit_input);

    $allowed_methods = ['mtn_momo', 'airtel_money', 'card'];
    if (!in_array($method, $allowed_methods)) {
        $error = 'Please select a valid payment method.';
    } elseif (in_array($method, ['mtn_momo', 'airtel_money']) && empty($phone)) {
        $error = 'Please enter your mobile money phone number.';
    } elseif (in_array($method, ['mtn_momo', 'airtel_money']) && !preg_match('/^(\+?256|0)?[0-9]{9}$/', $phone)) {
        $error = 'Enter a valid Ugandan phone number (e.g. 0771234567 or +256771234567).';
    } elseif ($deposit_amount <= 0) {
        $error = 'Please enter the amount you want to deposit.';
    } elseif ($deposit_amount < $min_deposit) {
        $error = 'Minimum deposit is ' . formatCurrency($min_deposit) . '.';
    } elseif ($deposit_amount > $balance_due) {
        $error = 'Amount cannot exceed the balance due of ' . formatCurrency($balance_due) . '.';
    }

    if (!$error) {
        // Create pending payment record
        $payment_id = $paymentModel->createPayment([
            'booking_id'     => $booking_id,
            'amount'         => $deposit_amount,
            'payment_method' => $method,
            'status'         => 'pending',
            'notes'          => $phone ? 'Phone: ' . $phone : '',
        ]);

        if (!$payment_id) {
            $error = 'Could not create payment record. Please try again.';
        } else {

            // ── Demo mode: skip real API when credentials are not configured ──
            $isDemo = (PESAPAL_CONSUMER_KEY === 'your_consumer_key_here'
                    || PESAPAL_CONSUMER_SECRET === 'your_consumer_secret_here');

            if ($isDemo) {
                // Generate a fake tracking ID and go straight to status page
                $fakeTracking = 'DEMO-' . strtoupper(bin2hex(random_bytes(6)));
                executeQuery(
                    "UPDATE payments SET transaction_reference = ?, notes = ? WHERE payment_id = ?",
                    [
                        $fakeTracking,
                        json_encode(['demo' => true, 'phone' => $phone]),
                        $payment_id,
                    ]
                );
                header("Location: status.php?payment_id={$payment_id}&tracking_id=" . urlencode($fakeTracking) . "&demo=1");
                exit;
            }

            // ── Live mode: submit to PesaPal ──────────────────────────────
            $pesapal = new PesapalService();
            $result  = $pesapal->submitOrder([
                'booking_id'     => $booking_id,
                'amount'         => $deposit_amount,
                'description'    => 'Room ' . $booking['room_number'] . ' — ' . $booking['semester'],
                'first_name'     => $booking['first_name'],
                'last_name'      => $booking['last_name'],
                'email'          => $booking['email'],
                'phone'          => $phone,
                'payment_method' => $method,
            ]);

            if ($result['success']) {
                executeQuery(
                    "UPDATE payments SET transaction_reference = ?, notes = ? WHERE payment_id = ?",
                    [
                        $result['order_tracking_id'],
                        json_encode([
                            'phone'              => $phone,
                            'merchant_reference' => $result['merchant_reference'],
                            'order_tracking_id'  => $result['order_tracking_id'],
                        ]),
                        $payment_id,
                    ]
                );

                if (!empty($result['redirect_url'])) {
                    header('Location: ' . $result['redirect_url']);
                } else {
                    header("Location: status.php?payment_id={$payment_id}&tracking_id=" . urlencode($result['order_tracking_id']));
                }
                exit;

            } else {
                executeQuery("DELETE FROM payments WHERE payment_id = ?", [$payment_id]);
                $error = 'Payment gateway error: ' . htmlspecialchars($result['message']);
            }
        }
    }
}


$page_title = 'Secure Checkout';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container py-4">
<div class="row justify-content-center">
<div class="col-lg-6 col-md-8">

    <a href="../payments.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-4">
        <i class="fas fa-arrow-left me-1"></i>Back to Payments
    </a>

    <?php if ($error): ?>
    <div class="alert border-0 rounded-3 mb-4" style="background:rgba(239,68,68,0.12);border-left:4px solid #f87171 !important;color:#fca5a5;">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
    </div>
    <?php endif; ?>

    <!-- Amount summary card -->
    <div class="card border-0 rounded-4 mb-4 shadow-sm" style="background:linear-gradient(135deg,rgba(79,70,229,0.2),rgba(124,58,237,0.15));border:1px solid rgba(79,70,229,0.3)!important;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:4px;">Booking Code</div>
                    <code style="color:#818cf8;font-weight:700;font-size:0.95rem;"><?php echo htmlspecialchars($booking['booking_code']); ?></code>
                    <div style="font-size:0.82rem;color:#94a3b8;margin-top:4px;">Room <?php echo htmlspecialchars($booking['room_number']); ?> &bull; <?php echo htmlspecialchars($booking['semester']); ?></div>
                </div>
                <div class="text-end">
                    <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:2px;">Total Fee</div>
                    <div style="font-size:1rem;font-weight:700;color:#94a3b8;"><?php echo formatCurrency($total_due); ?></div>
                    <div style="font-size:0.72rem;color:#94a3b8;margin-top:4px;">Balance Due</div>
                    <div style="font-size:1.6rem;font-weight:900;color:#fbbf24;"><?php echo formatCurrency($balance_due); ?></div>
                </div>
            </div>
            <?php if ($already_paid > 0): ?>
            <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:8px;padding:8px 14px;font-size:0.8rem;color:#4ade80;">
                <i class="fas fa-check-circle me-1"></i>
                You have already paid <?php echo formatCurrency($already_paid); ?> towards this booking.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Deposit amount input -->
    <div class="card border-0 rounded-4 mb-4 shadow-sm" style="background:#1e1b4b;border:1px solid rgba(255,255,255,0.08)!important;">
        <div class="card-body p-4">
            <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:12px;">
                <i class="fas fa-coins me-1" style="color:#fbbf24;"></i> How much would you like to deposit?
            </div>
            <div style="position:relative;">
                <div style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.8rem;font-weight:700;">UGX</div>
                <input type="number" id="depositDisplay" name="deposit_amount"
                       min="<?php echo $min_deposit; ?>"
                       max="<?php echo $balance_due; ?>"
                       step="1000"
                       value="<?php echo htmlspecialchars($_POST['deposit_amount'] ?? $balance_due); ?>"
                       required
                       style="width:100%;padding:14px 14px 14px 58px;border-radius:10px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.15);color:white;font-size:1.2rem;font-weight:800;outline:none;font-family:inherit;box-sizing:border-box;"
                       onfocus="this.style.borderColor='#818cf8';this.style.background='rgba(255,255,255,0.12)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.15)';this.style.background='rgba(255,255,255,0.07)'"
                       oninput="updateDepositHint(this.value)">
            </div>
            <div id="depositHint" style="font-size:0.78rem;margin-top:8px;"></div>

            <!-- Quick amount buttons -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                <button type="button" onclick="setDeposit(<?php echo $balance_due; ?>)"
                        style="padding:6px 14px;border-radius:999px;background:rgba(79,70,229,0.2);border:1px solid rgba(79,70,229,0.4);color:#818cf8;font-size:0.78rem;font-weight:700;cursor:pointer;">
                    Full Amount
                </button>
                <button type="button" onclick="setDeposit(<?php echo round($balance_due * 0.75); ?>)"
                        style="padding:6px 14px;border-radius:999px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;font-size:0.78rem;font-weight:600;cursor:pointer;">
                    75%
                </button>
                <button type="button" onclick="setDeposit(<?php echo round($balance_due * 0.5); ?>)"
                        style="padding:6px 14px;border-radius:999px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;font-size:0.78rem;font-weight:600;cursor:pointer;">
                    50%
                </button>
                <button type="button" onclick="setDeposit(<?php echo round($balance_due * 0.25); ?>)"
                        style="padding:6px 14px;border-radius:999px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#94a3b8;font-size:0.78rem;font-weight:600;cursor:pointer;">
                    25%
                </button>
            </div>
        </div>
    </div>

    <!-- Checkout form -->
    <div class="card border-0 rounded-4 shadow-sm" style="background:#1e1b4b;border:1px solid rgba(255,255,255,0.08)!important;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-4">
                <div style="width:36px;height:36px;background:rgba(79,70,229,0.25);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-shield-alt" style="color:#818cf8;"></i>
                </div>
                <div>
                    <div style="font-size:0.95rem;font-weight:700;color:#f1f5f9;">Secure Checkout</div>
                    <div style="font-size:0.72rem;color:#64748b;">Powered by PesaPal</div>
                </div>
            </div>

            <form method="POST" id="checkoutForm">
                <?php echo getCSRFTokenInput(); ?>
                <!-- Hidden field to carry deposit amount into POST -->
                <input type="hidden" name="deposit_amount" id="depositAmountHidden" value="<?php echo htmlspecialchars($_POST['deposit_amount'] ?? $balance_due); ?>">

                <!-- ── Step 1: Payment Method ── -->
                <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:12px;">
                    Step 1 — Choose Payment Method
                </div>

                <!-- MTN Mobile Money -->
                <label class="method-card" id="card-mtn" onclick="selectMethod('mtn_momo')">
                    <input type="radio" name="method" value="mtn_momo" id="m_mtn" style="display:none">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:48px;height:48px;background:#ffd500;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-weight:900;color:#1a1a1a;font-size:0.7rem;letter-spacing:-.02em;">MTN</span>
                        </div>
                        <div>
                            <div style="font-weight:700;color:#f1f5f9;font-size:0.9rem;">MTN Mobile Money</div>
                            <div style="font-size:0.75rem;color:#94a3b8;">STK push sent to your phone</div>
                        </div>
                    </div>
                    <i class="fas fa-check-circle method-check" style="color:#4ade80;display:none;font-size:1.2rem;"></i>
                </label>

                <!-- Airtel Money -->
                <label class="method-card" id="card-airtel" onclick="selectMethod('airtel_money')">
                    <input type="radio" name="method" value="airtel_money" id="m_airtel" style="display:none">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:48px;height:48px;background:#e8001c;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span style="font-weight:900;color:white;font-size:0.65rem;letter-spacing:-.02em;">AIRTEL</span>
                        </div>
                        <div>
                            <div style="font-weight:700;color:#f1f5f9;font-size:0.9rem;">Airtel Money</div>
                            <div style="font-size:0.75rem;color:#94a3b8;">STK push sent to your phone</div>
                        </div>
                    </div>
                    <i class="fas fa-check-circle method-check" style="color:#4ade80;display:none;font-size:1.2rem;"></i>
                </label>

                <!-- Card (PesaPal hosted) -->
                <label class="method-card" id="card-card" onclick="selectMethod('card')">
                    <input type="radio" name="method" value="card" id="m_card" style="display:none">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:48px;height:48px;background:rgba(79,70,229,0.3);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-credit-card" style="color:#818cf8;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;color:#f1f5f9;font-size:0.9rem;">Visa / MasterCard</div>
                            <div style="font-size:0.75rem;color:#94a3b8;">Redirected to secure PesaPal page</div>
                        </div>
                    </div>
                    <i class="fas fa-check-circle method-check" style="color:#4ade80;display:none;font-size:1.2rem;"></i>
                </label>

                <!-- ── Step 2: Phone Number (mobile money only) ── -->
                <div id="phoneSection" style="display:none;margin-top:20px;">
                    <div style="height:1px;background:rgba(255,255,255,0.08);margin-bottom:20px;"></div>
                    <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:12px;">
                        Step 2 — Enter Mobile Money Number
                    </div>
                    <div style="position:relative;">
                        <div style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#818cf8;font-size:0.85rem;font-weight:600;">+256</div>
                        <input type="tel" name="phone" id="phoneInput"
                               placeholder="7XX XXX XXX"
                               maxlength="12"
                               style="width:100%;padding:12px 14px 12px 54px;border-radius:10px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.15);color:white;font-size:0.95rem;font-weight:600;outline:none;font-family:inherit;letter-spacing:.03em;box-sizing:border-box;"
                               onfocus="this.style.borderColor='#818cf8';this.style.background='rgba(255,255,255,0.12)'"
                               onblur="this.style.borderColor='rgba(255,255,255,0.15)';this.style.background='rgba(255,255,255,0.07)'"
                               oninput="formatPhone(this)">
                    </div>
                    <div id="phoneHint" style="font-size:0.75rem;color:#94a3b8;margin-top:6px;">
                        <i class="fas fa-info-circle me-1" style="color:#818cf8;"></i>
                        A payment request will be sent to this number. Answer and enter your PIN to complete.
                    </div>
                </div>

                <!-- Submit -->
                <div style="margin-top:24px;">
                    <button type="submit" id="submitBtn" disabled
                            style="width:100%;padding:14px;border-radius:999px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;color:white;font-size:1rem;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(79,70,229,0.4);transition:opacity 0.2s;opacity:0.45;"
                            onmouseover="if(!this.disabled)this.style.opacity='0.9'"
                            onmouseout="if(!this.disabled)this.style.opacity='1'">
                        <i class="fas fa-lock me-2"></i><span id="submitLabel">Select a payment method</span>
                    </button>
                </div>

                <div style="text-align:center;margin-top:16px;">
                    <i class="fas fa-shield-alt" style="color:#4ade80;font-size:0.8rem;"></i>
                    <span style="font-size:0.72rem;color:#64748b;margin-left:4px;">256-bit encrypted &bull; Secured by PesaPal</span>
                </div>
            </form>
        </div>
    </div>

</div>
</div>
</div>

<style>
.method-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-radius: 12px;
    border: 1.5px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    cursor: pointer;
    margin-bottom: 10px;
    transition: border-color 0.2s, background 0.2s;
}
.method-card:hover { border-color: rgba(129,140,248,0.5); background: rgba(79,70,229,0.08); }
.method-card.selected { border-color: #818cf8; background: rgba(79,70,229,0.14); }
</style>

<script>
var selectedMethod = '';

function selectMethod(method) {
    selectedMethod = method;
    document.getElementById('m_' + (method === 'mtn_momo' ? 'mtn' : method === 'airtel_money' ? 'airtel' : 'card')).checked = true;

    // Style cards
    ['mtn','airtel','card'].forEach(function(k) {
        var c = document.getElementById('card-' + k);
        c.classList.remove('selected');
        c.querySelector('.method-check').style.display = 'none';
    });
    var key = method === 'mtn_momo' ? 'mtn' : method === 'airtel_money' ? 'airtel' : 'card';
    var sel = document.getElementById('card-' + key);
    sel.classList.add('selected');
    sel.querySelector('.method-check').style.display = 'inline';

    // Phone section
    var ps  = document.getElementById('phoneSection');
    var pin = document.getElementById('phoneInput');
    if (method === 'mtn_momo' || method === 'airtel_money') {
        ps.style.display = 'block';
        pin.required = true;
        updateSubmitBtn();
    } else {
        ps.style.display = 'none';
        pin.required = false;
        enableSubmit('Pay with Card →');
    }
}

function updateSubmitBtn() {
    var phone = document.getElementById('phoneInput').value.replace(/\D/g,'');
    var ready = phone.length >= 9;
    var labels = {
        'mtn_momo':     '📲 Send MTN MoMo Request',
        'airtel_money': '📲 Send Airtel Money Request',
        'card':         'Pay with Card →'
    };
    if (ready) {
        enableSubmit(labels[selectedMethod] || 'Confirm & Pay');
    } else {
        disableSubmit('Enter phone number to continue');
    }
}

function enableSubmit(label) {
    var btn = document.getElementById('submitBtn');
    btn.disabled = false;
    btn.style.opacity = '1';
    btn.style.cursor  = 'pointer';
    document.getElementById('submitLabel').textContent = label;
}
function disableSubmit(label) {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.style.opacity = '0.45';
    document.getElementById('submitLabel').textContent = label;
}

function formatPhone(input) {
    // Strip non-digits, remove leading 0 or 256
    var v = input.value.replace(/\D/g,'');
    if (v.startsWith('256')) v = v.slice(3);
    if (v.startsWith('0'))   v = v.slice(1);
    input.value = v.slice(0, 9);
    updateSubmitBtn();
}

var balanceDue  = <?php echo $balance_due; ?>;
var minDeposit  = <?php echo $min_deposit; ?>;

function setDeposit(amount) {
    document.getElementById('depositDisplay').value = amount;
    document.getElementById('depositAmountHidden').value = amount;
    updateDepositHint(amount);
    if (selectedMethod) updateSubmitBtn();
}

function updateDepositHint(val) {
    document.getElementById('depositAmountHidden').value = val; // keep in sync
    var hint = document.getElementById('depositHint');
    var amount = parseFloat(val) || 0;
    if (amount <= 0) {
        hint.style.color = '#94a3b8';
        hint.innerHTML = '<i class="fas fa-info-circle me-1" style="color:#818cf8;"></i>Enter the amount you wish to pay now.';
    } else if (amount < minDeposit) {
        hint.style.color = '#f87171';
        hint.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Minimum deposit is UGX ' + minDeposit.toLocaleString() + '.';
    } else if (amount > balanceDue) {
        hint.style.color = '#f87171';
        hint.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Cannot exceed balance due of UGX ' + balanceDue.toLocaleString() + '.';
    } else if (amount === balanceDue) {
        hint.style.color = '#4ade80';
        hint.innerHTML = '<i class="fas fa-check-circle me-1"></i>Full balance — booking will be fully settled.';
    } else {
        var remaining = balanceDue - amount;
        hint.style.color = '#fbbf24';
        hint.innerHTML = '<i class="fas fa-info-circle me-1"></i>Partial payment. Remaining balance after this: UGX ' + remaining.toLocaleString() + '.';
    }
    if (selectedMethod) updateSubmitBtn();
}

// Override updateSubmitBtn to include amount in label
var _origUpdateSubmitBtn = updateSubmitBtn;
updateSubmitBtn = function() {
    var phone  = document.getElementById('phoneInput').value.replace(/\D/g,'');
    var amount = parseFloat(document.getElementById('depositDisplay').value) || 0;
    var ready  = (selectedMethod === 'card' || phone.length >= 9) && amount >= minDeposit && amount <= balanceDue;
    var labels = {
        'mtn_momo':     '📲 Pay UGX ' + amount.toLocaleString() + ' via MTN MoMo',
        'airtel_money': '📲 Pay UGX ' + amount.toLocaleString() + ' via Airtel Money',
        'card':         'Pay UGX ' + amount.toLocaleString() + ' with Card →'
    };
    if (ready) {
        enableSubmit(labels[selectedMethod] || 'Confirm & Pay');
    } else if (amount < minDeposit && amount > 0) {
        disableSubmit('Amount too low — minimum UGX ' + minDeposit.toLocaleString());
    } else {
        disableSubmit(selectedMethod ? 'Enter a valid amount' : 'Select a payment method');
    }
};

// Prevent submit if no method selected
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    if (!selectedMethod) { e.preventDefault(); alert('Please select a payment method.'); return; }
    var amount = parseFloat(document.getElementById('depositDisplay').value) || 0;
    if (amount < minDeposit || amount > balanceDue) { e.preventDefault(); alert('Please enter a valid deposit amount.'); return; }
    var btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing…';
    btn.disabled = true;
});

// Init hint on page load
window.addEventListener('DOMContentLoaded', function() {
    var val = document.getElementById('depositDisplay').value;
    document.getElementById('depositAmountHidden').value = val;
    updateDepositHint(val);
});

<?php if ($_POST): // Pre-select on error ?>
<?php $old = htmlspecialchars($_POST['method'] ?? ''); ?>
<?php if ($old): ?>
window.addEventListener('DOMContentLoaded', function() { selectMethod('<?php echo $old; ?>'); });
<?php endif; ?>
<?php endif; ?>
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
