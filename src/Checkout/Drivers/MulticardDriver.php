<?php

namespace Goodoneuz\PayUz\Checkout\Drivers;

use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;

/**
 * Multicard (multicard.uz) acquiring driver — hosted checkout + saved-card charge
 * + hold-capture + full/partial refund + webhook, over Multicard's REST API.
 *
 * Key facts:
 *  - **Amounts are already tiyin** end-to-end — this driver does NO conversion
 *    (unlike Octo).
 *  - Auth is a cached bearer token: POST /auth {application_id, secret} -> {token,
 *    expiry}. Every protected call sends both `Authorization: Bearer` and
 *    `X-Access-Token` (the docs disagree on which; sending both is safe), and a
 *    401 drops the cached token and retries once.
 *  - The envelope is `{success: bool, data|error}`: success===true -> data;
 *    otherwise a CheckoutException carrying error.details.
 *  - Methods use real HTTP verbs: invoice/charge are POST, capture is PUT, refund
 *    is DELETE, status is GET.
 *  - The host is the prod/sandbox switch (`base_url`), there is no `test` flag.
 *
 * Identifier note: capture()/refund()/status() take the payment **uuid**
 * ({@see PaymentResult::paymentId()}). createPayment returns a checkout_url to
 * redirect to. `ofd` line items, `ttl`, `sms`, `device_details`, `split` are
 * passed through from {@see Payment::with()}.
 *
 * UNCERTAIN (confirm against Multicard before production): the status GET path,
 * which of the two webhook signature schemes your store uses (`callback_scheme`),
 * and the auth header name.
 */
class MulticardDriver implements CheckoutDriver
{
    const BASE_URL = 'https://mesh.multicard.uz';

    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /** @var string|null cached bearer token */
    protected $token;

    /** @var int unix time the cached token must be refreshed at */
    protected $tokenExpiresAt = 0;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function createPayment(Payment $payment)
    {
        $canonical = [
            'store_id'     => (int) $this->cfg('store_id'),
            'amount'       => $payment->amount(), // tiyin pass-through
            'invoice_id'   => $payment->orderId(),
            'callback_url' => $payment->notifyUrl() ?: $this->cfg('callback_url'),
            'lang'         => $this->cfg('language', 'uz'),
        ];
        if ($payment->returnUrl()) {
            $canonical['return_url'] = $payment->returnUrl();
        }

        // Extras (ofd, ttl, sms, …) pass through but never override a canonical key.
        $body = array_merge($payment->extra(), $canonical);

        return $this->resultFrom($this->api('POST', '/payment/invoice', $body), PaymentResult::STATUS_CREATED);
    }

    public function chargeToken($token, Payment $payment)
    {
        $canonical = [
            'card'         => ['token' => (string) $token],
            'amount'       => $payment->amount(),
            'store_id'     => (int) $this->cfg('store_id'),
            'invoice_id'   => $payment->orderId(),
            'callback_url' => $payment->notifyUrl() ?: $this->cfg('callback_url'),
        ];
        $body = array_merge($payment->extra(), $canonical);

        return $this->resultFrom($this->api('POST', '/payment', $body), PaymentResult::STATUS_PENDING);
    }

    public function capture($paymentId, $amount = null)
    {
        if ($amount === null) {
            throw new CheckoutException('Multicard capture requires an explicit amount and applies only to a held (pre-authorized) payment.');
        }

        $resp = $this->api('PUT', '/payment/hold/'.rawurlencode($paymentId).'/charge', ['amount' => (int) $amount]);

        return $this->resultFrom($resp, PaymentResult::STATUS_SUCCEEDED);
    }

    public function refund($paymentId, $amount = null)
    {
        if ($amount === null) {
            $resp = $this->api('DELETE', '/payment/'.rawurlencode($paymentId));
        } else {
            $resp = $this->api('DELETE', '/payment/'.rawurlencode($paymentId).'/partial', ['refund_amount' => (int) $amount]);
        }

        $data = $this->data($resp);

        // A successful refund call means refunded, regardless of the echoed status.
        return new PaymentResult(PaymentResult::STATUS_REFUNDED, [
            'payment_id' => $this->pick($data, ['uuid']),
            'amount'     => $amount !== null ? (int) $amount : (int) ($this->pick($data, ['total_amount', 'payment_amount']) ?: 0),
            'raw'        => $resp,
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * For Multicard, pass the payment **uuid** ({@see PaymentResult::paymentId()}).
     * NOTE: the get-payment path is not byte-confirmed in the public docs — verify
     * it (or reconcile via the webhook) before relying on it in production.
     */
    public function status($reference)
    {
        return $this->resultFrom($this->api('GET', '/payment/'.rawurlencode($reference)), PaymentResult::STATUS_PENDING);
    }

    /**
     * {@inheritdoc}
     *
     * Multicard has TWO signature schemes; `callback_scheme` selects it:
     *   'webhooks' (default): sha1(uuid . invoice_id . amount . secret)
     *   'success'           : md5(store_id . invoice_id . amount . secret)
     * `amount` is the integer (tiyin) form. Confirm which scheme your store uses.
     * Without a configured `secret`, or a missing `sign`, the webhook is rejected.
     */
    public function verifyWebhook(array $payload, array $headers = [])
    {
        $secret = $this->cfg('secret');
        $sign   = isset($payload['sign']) ? (string) $payload['sign'] : '';
        if (!$secret || $sign === '') {
            return false;
        }

        $amount = (string) (int) (isset($payload['amount']) ? $payload['amount'] : 0);

        if ($this->cfg('callback_scheme', 'webhooks') === 'success') {
            $expected = md5($this->str($payload, 'store_id').$this->str($payload, 'invoice_id').$amount.$secret);
        } else {
            $expected = sha1($this->str($payload, 'uuid').$this->str($payload, 'invoice_id').$amount.$secret);
        }

        return hash_equals(strtolower($expected), strtolower($sign));
    }

    public function parseWebhook(array $payload)
    {
        $status = isset($payload['status']) ? (string) $payload['status'] : '';

        // SECURITY: the webhook amount/card are not fully covered by the signature;
        // reconcile via status() before granting value.
        return new PaymentResult($status !== '' ? $this->mapStatus($status) : PaymentResult::STATUS_PENDING, [
            'payment_id'  => $this->str($payload, 'uuid'),
            'order_id'    => $this->str($payload, 'invoice_id'),
            'amount'      => isset($payload['amount']) ? (int) $payload['amount'] : 0,
            'card_token'  => isset($payload['card_token']) ? (string) $payload['card_token'] : null,
            'masked_card' => isset($payload['card_pan']) ? (string) $payload['card_pan'] : null,
            'raw'         => $payload,
        ]);
    }

    public function name()
    {
        return 'multicard';
    }

    // --- internals ---

    /**
     * Call an authenticated endpoint, refreshing the token + retrying once on 401,
     * then asserting the {success:bool} envelope.
     *
     * @param string     $method
     * @param string     $path
     * @param array|null $payload
     * @return array the success `data`-bearing body
     * @throws CheckoutException
     */
    protected function api($method, $path, $payload = null)
    {
        $this->assertConfigured();

        $url      = $this->baseUrl().$path;
        $response = $this->http->request($method, $url, $payload, $this->authHeaders());

        if ((isset($response['status']) ? (int) $response['status'] : 0) === 401) {
            $this->token = null; // force a fresh token, then retry once
            $response = $this->http->request($method, $url, $payload, $this->authHeaders());
        }

        return $this->parseEnvelope($response);
    }

    /**
     * @param array $response
     * @return array
     * @throws CheckoutException
     */
    protected function parseEnvelope(array $response)
    {
        $status = isset($response['status']) ? (int) $response['status'] : 0;
        $body   = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        if (!isset($body['success']) || $body['success'] !== true) {
            $error   = isset($body['error']) && is_array($body['error']) ? $body['error'] : [];
            $message = isset($error['details']) ? $error['details']
                : (isset($error['code']) ? $error['code'] : 'Multicard HTTP '.$status.'.');

            throw new CheckoutException((string) $message, 0, $body);
        }

        return $body;
    }

    /**
     * @param array  $resp
     * @param string $defaultStatus
     * @return PaymentResult
     */
    protected function resultFrom(array $resp, $defaultStatus)
    {
        $data      = $this->data($resp);
        $rawStatus = $this->pick($data, ['status']);
        $amount    = $this->pick($data, ['amount', 'payment_amount']);

        return new PaymentResult($rawStatus ? $this->mapStatus($rawStatus) : $defaultStatus, [
            'payment_id'  => $this->pick($data, ['uuid']),
            'order_id'    => $this->pick($data, ['invoice_id', 'store_invoice_id']),
            'pay_url'     => $this->pick($data, ['checkout_url']),
            'amount'      => $amount !== null ? (int) $amount : 0, // tiyin, no conversion
            'card_token'  => $this->pick($data, ['card_token']),
            'masked_card' => $this->pick($data, ['card_pan']),
            'raw'         => $resp,
        ]);
    }

    /**
     * @param string $multicard
     * @return string
     */
    protected function mapStatus($multicard)
    {
        switch (strtolower((string) $multicard)) {
            case 'draft':
                return PaymentResult::STATUS_CREATED;
            case 'hold':
                return PaymentResult::STATUS_HELD;
            case 'success':
                return PaymentResult::STATUS_SUCCEEDED;
            case 'error':
                return PaymentResult::STATUS_FAILED;
            case 'revert':
                return PaymentResult::STATUS_REFUNDED;
            default:
                // progress, billing and any unknown string
                return PaymentResult::STATUS_PENDING;
        }
    }

    // --- auth ---

    /**
     * @return array
     */
    protected function authHeaders()
    {
        $token = $this->token();

        return ['Authorization' => 'Bearer '.$token, 'X-Access-Token' => $token];
    }

    /**
     * @return string
     * @throws CheckoutException
     */
    protected function token()
    {
        if ($this->token && $this->now() < $this->tokenExpiresAt) {
            return $this->token;
        }

        $response = $this->http->request('POST', $this->baseUrl().'/auth', [
            'application_id' => $this->cfg('application_id'),
            'secret'         => $this->cfg('secret'),
        ]);
        $body = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        if (empty($body['token'])) {
            throw new CheckoutException('Multicard authentication failed: no token in the /auth response.');
        }

        $this->token          = (string) $body['token'];
        $this->tokenExpiresAt = $this->parseExpiry(isset($body['expiry']) ? $body['expiry'] : null);

        return $this->token;
    }

    /**
     * Parse Multicard's GMT+5 expiry datetime into a refresh deadline (−60s). A
     * 401 retry is the backstop if this is off.
     *
     * @param string|null $expiry
     * @return int
     */
    protected function parseExpiry($expiry)
    {
        if ($expiry) {
            $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $expiry, new \DateTimeZone('+05:00'));
            if ($dt instanceof \DateTime) {
                return $dt->getTimestamp() - 60;
            }
        }

        return $this->now() + 3000;
    }

    /**
     * Current unix time — a seam so tests can simulate token expiry.
     *
     * @return int
     */
    protected function now()
    {
        return time();
    }

    // --- helpers ---

    /**
     * @throws CheckoutException
     */
    protected function assertConfigured()
    {
        foreach (['application_id', 'secret', 'store_id'] as $key) {
            if (!$this->cfg($key)) {
                throw new CheckoutException(sprintf('Multicard driver is not configured: missing "%s".', $key));
            }
        }
    }

    /**
     * @param array $resp
     * @return array
     */
    protected function data(array $resp)
    {
        return isset($resp['data']) && is_array($resp['data']) ? $resp['data'] : $resp;
    }

    /**
     * @param array $arr
     * @param array $keys
     * @return mixed|null
     */
    protected function pick(array $arr, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($arr[$key]) && $arr[$key] !== '') {
                return $arr[$key];
            }
        }

        return null;
    }

    /**
     * @param array  $payload
     * @param string $key
     * @return string
     */
    protected function str(array $payload, $key)
    {
        return isset($payload[$key]) ? (string) $payload[$key] : '';
    }

    /**
     * @return string
     */
    protected function baseUrl()
    {
        return rtrim($this->cfg('base_url', self::BASE_URL), '/');
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function cfg($key, $default = null)
    {
        return isset($this->config[$key]) && $this->config[$key] !== '' ? $this->config[$key] : $default;
    }
}
