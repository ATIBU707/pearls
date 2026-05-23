<?php
/**
 * PesapalService — PesaPal v3 API Integration
 * Online Hostel Management System
 *
 * Handles:
 *  - OAuth token acquisition
 *  - IPN registration
 *  - Order submission (STK push for mobile money)
 *  - Transaction status checking
 */

class PesapalService
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl        = PESAPAL_BASE_URL;
        $this->consumerKey    = PESAPAL_CONSUMER_KEY;
        $this->consumerSecret = PESAPAL_CONSUMER_SECRET;
    }

    // ── 1. Authentication ────────────────────────────────────────────────

    /**
     * Request OAuth token from PesaPal.
     * Tokens are valid for 5 minutes — do not cache across requests.
     */
    public function getToken(): string|false
    {
        if ($this->token) return $this->token;

        $response = $this->post('/api/Auth/RequestToken', [
            'consumer_key'    => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ], false);

        if (isset($response['token'])) {
            $this->token = $response['token'];
            return $this->token;
        }

        logMessage('PesaPal token error: ' . json_encode($response), 'error');
        return false;
    }

    // ── 2. IPN Registration ───────────────────────────────────────────────

    /**
     * Register (or re-register) the IPN URL.
     * Returns the ipn_id needed for order submission.
     */
    public function registerIPN(): string|false
    {
        $response = $this->post('/api/URLSetup/RegisterIPN', [
            'url'                   => PESAPAL_IPN_URL,
            'ipn_notification_type' => 'GET',
        ], true, 60); // 60s — PesaPal validates the URL which takes time

        if (!empty($response['ipn_id'])) {
            return $response['ipn_id'];
        }

        logMessage('PesaPal IPN registration error: ' . json_encode($response), 'error');
        return false;
    }

    /**
     * Get existing registered IPN URLs and return first valid ipn_id.
     */
    public function getIPNId(): string|false
    {
        // Try to get cached IPN id from DB or just re-register each time (cheap)
        $cached = getValue("SELECT option_value FROM app_options WHERE option_key = 'pesapal_ipn_id'");
        if ($cached) return $cached;

        $id = $this->registerIPN();
        if ($id) {
            // Cache it — create app_options table if needed
            executeQuery(
                "INSERT INTO app_options (option_key, option_value) VALUES ('pesapal_ipn_id', ?)
                 ON DUPLICATE KEY UPDATE option_value = ?",
                [$id, $id]
            );
            return $id;
        }
        return false;
    }

    // ── 3. Submit Order (STK Push) ────────────────────────────────────────

    /**
     * Submit a payment order to PesaPal.
     *
     * @param array $params {
     *   booking_id, amount, currency, description,
     *   first_name, last_name, email, phone,
     *   payment_method: 'mtn_momo'|'airtel_money'
     * }
     * @return array ['success'=>bool, 'order_tracking_id'=>string, 'redirect_url'=>string, 'message'=>string]
     */
    public function submitOrder(array $params): array
    {
        $ipn_id = $this->registerIPN(); // always register fresh for dev; cache in prod
        if (!$ipn_id) {
            return ['success' => false, 'message' => 'Failed to register IPN.'];
        }

        // Map our method names to PesaPal billing types
        $billingTypeMap = [
            'mtn_momo'     => 'MOBILE_MONEY',
            'airtel_money' => 'MOBILE_MONEY',
        ];

        // Live Uganda network codes for PesaPal v3
        $networkCodeMap = [
            'mtn_momo'     => 'UG_MTN',    // MTN Uganda (live)
            'airtel_money' => 'UG_AIRTEL', // Airtel Uganda (live)
        ];

        $method = $params['payment_method'] ?? 'mtn_momo';

        $payload = [
            'id'                        => 'BOOKING-' . $params['booking_id'] . '-' . time(),
            'currency'                  => 'UGX',
            'amount'                    => (float)$params['amount'],
            'description'               => $params['description'] ?? 'Hostel Room Booking Payment',
            'callback_url'              => PESAPAL_REDIRECT_URL . '?booking_id=' . $params['booking_id'],
            'notification_id'           => $ipn_id,
            'billing_address'           => [
                'email_address' => $params['email']      ?? '',
                'phone_number'  => $this->normalizePhone($params['phone'] ?? ''),
                'first_name'    => $params['first_name'] ?? '',
                'last_name'     => $params['last_name']  ?? '',
            ],
        ];

        $response = $this->post('/api/Transactions/SubmitOrderRequest', $payload);

        if (!empty($response['order_tracking_id'])) {
            return [
                'success'           => true,
                'order_tracking_id' => $response['order_tracking_id'],
                'redirect_url'      => $response['redirect_url'] ?? '',
                'merchant_reference'=> $payload['id'],
            ];
        }

        $msg = $response['error']['message'] ?? $response['message'] ?? 'Order submission failed.';
        logMessage('PesaPal submit error: ' . json_encode($response), 'error');
        return ['success' => false, 'message' => $msg];
    }

    // ── 4. Transaction Status ─────────────────────────────────────────────

    /**
     * Check the status of a transaction by order_tracking_id.
     * Returns 'COMPLETED', 'PENDING', 'FAILED', 'INVALID' etc.
     */
    public function getTransactionStatus(string $orderTrackingId): array
    {
        $response = $this->get('/api/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $orderTrackingId,
        ]);

        return [
            'status'            => $response['payment_status_description'] ?? 'Unknown',
            'payment_method'    => $response['payment_method']             ?? '',
            'amount'            => $response['amount']                      ?? 0,
            'currency'          => $response['currency']                    ?? 'UGX',
            'confirmation_code' => $response['confirmation_code']           ?? '',
            'message'           => $response['message']                     ?? '',
            'raw'               => $response,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Normalize Ugandan phone to international format (256XXXXXXXXX)
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '256' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+')) {
            $phone = ltrim($phone, '+');
        } elseif (!str_starts_with($phone, '256')) {
            $phone = '256' . $phone;
        }
        return $phone;
    }

    /**
     * HTTP POST to PesaPal API
     */
    private function post(string $endpoint, array $data, bool $auth = true, int $timeout = 30): array
    {
        $url     = $this->baseUrl . $endpoint;
        $headers = ['Content-Type: application/json', 'Accept: application/json'];

        if ($auth) {
            $token = $this->getToken();
            if (!$token) return ['error' => ['message' => 'Auth failed']];
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $caBundle = 'C:\\wamp64\\bin\\php\\php8.3.14\\cacert.pem';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO         => $caBundle,
        ]);

        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            logMessage("PesaPal cURL error {$errno} on POST {$endpoint}", 'error');
            return ['error' => ['message' => 'Network error']];
        }

        return json_decode($result, true) ?? [];
    }

    /**
     * HTTP GET to PesaPal API
     */
    private function get(string $endpoint, array $params = []): array
    {
        $token = $this->getToken();
        if (!$token) return [];

        $url = $this->baseUrl . $endpoint;
        if ($params) $url .= '?' . http_build_query($params);

        $caBundle = 'C:\\wamp64\\bin\\php\\php8.3.14\\cacert.pem';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO         => $caBundle,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true) ?? [];
    }
}
?>
