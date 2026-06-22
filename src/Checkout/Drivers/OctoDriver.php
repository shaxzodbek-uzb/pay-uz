<?php

namespace Goodoneuz\PayUz\Checkout\Drivers;

use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;

/**
 * Octo (octo.uz) acquiring driver — hosted checkout + saved-card charge +
 * two-stage capture + refund + status + webhook, over Octo's REST API.
 *
 * Key facts this driver bakes in:
 *  - **Octo bills in decimal som, not tiyin.** The package is tiyin-internal, so
 *    THIS driver is the only place that converts (`tiyin/100` out, `som*100` in).
 *  - One host (`https://secure.octo.uz`); sandbox is the `test:true` body flag.
 *  - Credentials (`octo_shop_id` + `octo_secret`) travel in the JSON body.
 *  - HTTP is always 200; success/failure is the response `error` field (0 = ok).
 *
 * Identifier note (an Octo wart, documented per method): `capture()` and
 * `refund()` take the **octo_payment_UUID** (returned by createPayment), while
 * `status()` takes the **shop_transaction_id** (your order id) — Octo's
 * check_status is keyed on the latter.
 *
 * Config (`checkout.drivers.octo`): shop_id, secret, unique_key (webhook),
 * test, return_url, notify_url, language.
 */
class OctoDriver implements CheckoutDriver
{
    const BASE_URL = 'https://secure.octo.uz';

    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function createPayment(Payment $payment)
    {
        $canonical = array_merge($this->credentials(), [
            'shop_transaction_id' => $payment->orderId(),
            'auto_capture'        => $payment->isAutoCapture(),
            'test'                => (bool) $this->cfg('test', false),
            'init_time'           => date('Y-m-d H:i:s'),
            'total_sum'           => $this->toSom($payment->amount()),
            'currency'            => $payment->currency(),
            'description'         => (string) $payment->description(),
            'return_url'          => $payment->returnUrl() ?: $this->cfg('return_url'),
            'notify_url'          => $payment->notifyUrl() ?: $this->cfg('notify_url'),
            'language'            => $this->cfg('language', 'uz'),
        ]);

        // Pass-through extras (basket, user_data, payment_methods, ttl, tsp_id)
        // never override a canonical key.
        $body = array_merge($payment->extra(), $canonical);

        return $this->resultFrom($this->request('/prepare_payment', $body), PaymentResult::STATUS_CREATED);
    }

    public function chargeToken($token, Payment $payment)
    {
        // Two-step: mint a payment UUID, then charge the saved token against it.
        $uuid = $this->createPayment($payment)->paymentId();

        $body = array_merge($this->credentials(), [
            'method'     => isset($payment->extra()['method']) ? $payment->extra()['method'] : 'bank_card',
            'card_token' => (string) $token,
        ]);
        if (isset($payment->extra()['email'])) {
            $body['email'] = $payment->extra()['email'];
        }

        return $this->resultFrom($this->request('/pay/'.rawurlencode($uuid), $body), PaymentResult::STATUS_PENDING);
    }

    public function capture($paymentId, $amount = null)
    {
        if ($amount === null) {
            throw new CheckoutException('Octo requires an explicit capture amount (final_amount); pass the authorized amount.');
        }

        return $this->resultFrom($this->request('/set_accept', array_merge($this->credentials(), [
            'octo_payment_UUID' => (string) $paymentId,
            'accept_status'     => 'capture',
            'final_amount'      => $this->toSom($amount),
        ])), PaymentResult::STATUS_SUCCEEDED);
    }

    public function refund($paymentId, $amount = null)
    {
        if ($amount === null) {
            throw new CheckoutException('Octo requires an explicit refund amount; pass the amount to refund.');
        }

        $response = $this->request('/refund', array_merge($this->credentials(), [
            'octo_payment_UUID' => (string) $paymentId,
            'shop_refund_id'    => $this->refundId($paymentId),
            'amount'            => $this->toSom($amount),
        ]));

        // A successful /refund (error:0) means refunded, regardless of the status
        // string Octo echoes back.
        return new PaymentResult(PaymentResult::STATUS_REFUNDED, [
            'payment_id' => $this->pick($response, ['octo_payment_UUID']),
            'amount'     => (int) $amount,
            'raw'        => $response,
        ]);
    }

    public function status($reference)
    {
        // check_status is keyed on shop_transaction_id (the merchant order id),
        // i.e. PaymentResult::orderId() — NOT the octo_payment_UUID.
        return $this->resultFrom($this->request('/check_status', array_merge($this->credentials(), [
            'shop_transaction_id' => (string) $reference,
        ])), PaymentResult::STATUS_PENDING);
    }

    /**
     * {@inheritdoc}
     *
     * WARNING: Octo's signature recipe is not byte-precisely documented. This
     * computes sha1(unique_key . octo_payment_UUID . status) and compares with
     * hash_equals — confirm the exact concatenation against a live Octo callback
     * before trusting it, and (per Octo's own guidance) ALSO call status() to
     * re-fetch the authoritative state before mutating an order. Without a
     * configured unique_key the webhook is rejected.
     */
    public function verifyWebhook(array $payload, array $headers = [])
    {
        $uniqueKey = $this->cfg('unique_key');
        $signature = isset($payload['signature']) ? (string) $payload['signature'] : '';
        if (!$uniqueKey || $signature === '') {
            return false;
        }

        $uuid   = isset($payload['octo_payment_UUID']) ? (string) $payload['octo_payment_UUID'] : '';
        $status = isset($payload['status']) ? (string) $payload['status'] : '';

        return hash_equals(sha1($uniqueKey.$uuid.$status), $signature);
    }

    public function parseWebhook(array $payload)
    {
        $status = isset($payload['status']) ? (string) $payload['status'] : '';

        // NOTE: only octo_payment_UUID + status are covered by the webhook
        // signature. `amount` and `masked_card` here are NOT authenticated — see
        // the parseWebhook docblock; reconcile the amount via status() before
        // granting value.
        return new PaymentResult($status !== '' ? $this->mapStatus($status) : PaymentResult::STATUS_PENDING, [
            'payment_id'  => isset($payload['octo_payment_UUID']) ? (string) $payload['octo_payment_UUID'] : '',
            'order_id'    => isset($payload['shop_transaction_id']) ? (string) $payload['shop_transaction_id'] : '',
            'amount'      => isset($payload['total_sum']) ? $this->toTiyin($payload['total_sum']) : 0,
            'masked_card' => isset($payload['maskedPan']) ? (string) $payload['maskedPan'] : null,
            'raw'         => $payload,
        ]);
    }

    public function name()
    {
        return 'octo';
    }

    // --- internals ---

    /**
     * @param string $path
     * @param array  $body
     * @return array decoded response
     * @throws CheckoutException on transport/HTTP fault or a non-zero `error`.
     */
    protected function request($path, array $body)
    {
        $this->assertConfigured();

        $response = $this->http->post(self::BASE_URL.$path, $body);
        $status   = isset($response['status']) ? (int) $response['status'] : 0;
        $resp     = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        if ($status < 200 || $status >= 300) {
            throw new CheckoutException(sprintf('Octo HTTP %d.', $status), $status, $resp);
        }

        $error = isset($resp['error']) ? (int) $resp['error'] : 0;
        if ($error !== 0) {
            $message = isset($resp['errMessage']) ? $resp['errMessage']
                : (isset($resp['errorMessage']) ? $resp['errorMessage'] : 'Octo request failed.');
            throw new CheckoutException((string) $message, $error, $resp);
        }

        return $resp;
    }

    /**
     * @param array  $response
     * @param string $defaultStatus
     * @return PaymentResult
     */
    protected function resultFrom(array $response, $defaultStatus)
    {
        $rawStatus = $this->pick($response, ['status']);
        $totalSum  = $this->pick($response, ['total_sum', 'totalSum']);

        return new PaymentResult($rawStatus ? $this->mapStatus($rawStatus) : $defaultStatus, [
            'payment_id'  => $this->pick($response, ['octo_payment_UUID', 'uuid']),
            'order_id'    => $this->pick($response, ['shop_transaction_id']),
            'pay_url'     => $this->pick($response, ['octo_pay_url', 'redirectUrl']),
            'amount'      => $totalSum !== null ? $this->toTiyin($totalSum) : 0,
            'masked_card' => $this->pick($response, ['maskedPan']),
            'raw'         => $response,
        ]);
    }

    /**
     * Map an Octo status string to a normalized PaymentResult status.
     *
     * @param string $octo
     * @return string
     */
    protected function mapStatus($octo)
    {
        switch (strtolower((string) $octo)) {
            case 'created':
                return PaymentResult::STATUS_CREATED;
            case 'waiting_for_capture':
                return PaymentResult::STATUS_HELD;
            case 'capture':
            case 'succeeded':
                return PaymentResult::STATUS_SUCCEEDED;
            case 'failed':
                return PaymentResult::STATUS_FAILED;
            case 'cancelled':
            case 'cancel':
                return PaymentResult::STATUS_CANCELLED;
            case 'refunded':
            case 'partially_refunded':
                return PaymentResult::STATUS_REFUNDED;
            default:
                // wait_user_action and any unknown string
                return PaymentResult::STATUS_PENDING;
        }
    }

    /**
     * First present value among $keys, checked inside `data{}` first then at the
     * top level (Octo echoes fields in both places).
     *
     * @param array $response
     * @param array $keys
     * @return mixed|null
     */
    protected function pick(array $response, array $keys)
    {
        $haystacks = [];
        if (isset($response['data']) && is_array($response['data'])) {
            $haystacks[] = $response['data'];
        }
        $haystacks[] = $response;

        foreach ($haystacks as $haystack) {
            foreach ($keys as $key) {
                if (isset($haystack[$key]) && $haystack[$key] !== '') {
                    return $haystack[$key];
                }
            }
        }

        return null;
    }

    /**
     * @return array
     */
    protected function credentials()
    {
        return [
            'octo_shop_id' => (int) $this->cfg('shop_id'),
            'octo_secret'  => (string) $this->cfg('secret'),
        ];
    }

    /**
     * @throws CheckoutException
     */
    protected function assertConfigured()
    {
        if (!$this->cfg('shop_id') || !$this->cfg('secret')) {
            throw new CheckoutException('Octo driver is not configured: missing "shop_id" or "secret".');
        }
    }

    /**
     * tiyin -> som (decimal, 2 places).
     *
     * @param int $tiyin
     * @return float
     */
    protected function toSom($tiyin)
    {
        return round(((int) $tiyin) / 100, 2);
    }

    /**
     * som -> tiyin.
     *
     * @param mixed $som
     * @return int
     */
    protected function toTiyin($som)
    {
        return (int) round(((float) $som) * 100);
    }

    /**
     * @param string $paymentId
     * @return string unique merchant refund id
     */
    protected function refundId($paymentId)
    {
        return $paymentId.'-r-'.uniqid();
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
