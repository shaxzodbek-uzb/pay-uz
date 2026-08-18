<?php

namespace Goodoneuz\PayUz\Checkout\Drivers;

use Goodoneuz\PayUz\Checkout\BindingSession;
use Goodoneuz\PayUz\Checkout\BoundCard;
use Goodoneuz\PayUz\Checkout\CardBinding;
use Goodoneuz\PayUz\Checkout\Contracts\CardBinder;
use Goodoneuz\PayUz\Checkout\Contracts\CheckoutDriver;
use Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException;
use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Checkout\PaymentResult;
use Goodoneuz\PayUz\Support\Http\HttpClient;

/**
 * Octo (octo.uz) acquiring — hosted checkout, saved-card charge, card binding,
 * two-stage capture, refund, status and webhooks over Octo's REST API.
 *
 * Written against the published contract at help.octo.uz. Four of its quirks are
 * baked in here so callers never have to think about them:
 *
 *  - **Octo bills in decimal som, not tiyin.** The package is tiyin-internal, so
 *    this driver is the only place that converts (`tiyin/100` out, `som*100` in).
 *  - **One host** (`https://secure.octo.uz`); the sandbox is the `test:true` body
 *    flag, not a different URL.
 *  - **Credentials travel in the JSON body** (`octo_shop_id` + `octo_secret`),
 *    not in headers.
 *  - **HTTP is always 200.** Success is the response `error` field being 0, with
 *    the human-readable reason in `errMessage`.
 *
 * Identifier note, an Octo wart: `capture()` and `refund()` take the
 * **octo_payment_UUID** (returned by createPayment), while `status()` takes the
 * **shop_transaction_id** (your order id) — Octo keys the status read on the latter.
 *
 * Config (`checkout.drivers.octo`): shop_id, secret, unique_key, test, return_url,
 * notify_url, bind_notify_url, language, receipt_email, bindable_methods.
 */
class OctoDriver implements CheckoutDriver, CardBinder
{
    const BASE_URL = 'https://secure.octo.uz';

    /**
     * Octo's five documented transaction states (help.octo.uz/statuses.html),
     * plus the handful of strings it returns from the refund and capture calls.
     *
     * Both spellings of "cancelled" are listed on purpose: the status page
     * documents `canceled`, while `set_accept` takes `cancel`. Recognising only
     * one leaves cancelled payments stuck looking pending.
     */
    protected static $statusMap = [
        'created'             => PaymentResult::STATUS_CREATED,
        'wait_user_action'    => PaymentResult::STATUS_PENDING,
        'waiting_for_capture' => PaymentResult::STATUS_HELD,
        'succeeded'           => PaymentResult::STATUS_SUCCEEDED,
        'capture'             => PaymentResult::STATUS_SUCCEEDED,
        'canceled'            => PaymentResult::STATUS_CANCELLED,
        'cancelled'           => PaymentResult::STATUS_CANCELLED,
        'cancel'              => PaymentResult::STATUS_CANCELLED,
        'failed'              => PaymentResult::STATUS_FAILED,
        'refunded'            => PaymentResult::STATUS_REFUNDED,
        'partially_refunded'  => PaymentResult::STATUS_REFUNDED,
    ];

    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function name()
    {
        return 'octo';
    }

    // --- hosted checkout ---

    public function createPayment(Payment $payment)
    {
        $canonical = array_merge($this->credentials(), [
            'shop_transaction_id' => $payment->orderId(),
            'auto_capture'        => $payment->isAutoCapture(),
            'test'                => $this->boolCfg('test'),
            'init_time'           => date('Y-m-d H:i:s'),
            'total_sum'           => $this->toSom($payment->amount()),
            'currency'            => $payment->currency(),
            'description'         => (string) $payment->description(),
            'return_url'          => $payment->returnUrl() ?: $this->cfg('return_url'),
            'notify_url'          => $this->requiredNotifyUrl($payment),
            'language'            => $this->cfg('language', 'uz'),
        ]);

        // Pass-through extras (basket, user_data, payment_methods, ttl, tsp_id)
        // never override a canonical key.
        $body = array_merge($payment->extra(), $canonical);

        return $this->resultFrom($this->request('/prepare_payment', $body), PaymentResult::STATUS_CREATED);
    }

    /**
     * Octo marks notify_url mandatory: without it a payment's outcome can never
     * reach us, and the order would hang whatever the customer did.
     *
     * @param Payment $payment
     * @return string
     *
     * @throws CheckoutException
     */
    protected function requiredNotifyUrl(Payment $payment)
    {
        $notifyUrl = $payment->notifyUrl() ?: $this->cfg('notify_url');

        if (!$notifyUrl) {
            throw new CheckoutException('Octo requires a notify_url: set it on the Payment or in the driver config.');
        }

        return $notifyUrl;
    }

    public function chargeToken($token, Payment $payment)
    {
        // Two steps: mint a payment UUID, then charge the saved token against it.
        $uuid = $this->createPayment($payment)->paymentId();

        $extra = $payment->extra();

        $body = array_merge($this->credentials(), [
            'method'     => isset($extra['method']) ? $extra['method'] : 'bank_card',
            'card_token' => (string) $token,
            // Octo documents `email` as required on /pay. Falling back to the shop's
            // own address keeps the call valid for customers who have none (Telegram
            // sign-ups, phone-only accounts) instead of failing on a missing field.
            'email'      => isset($extra['email']) && $extra['email'] !== ''
                ? $extra['email']
                : (string) $this->cfg('receipt_email', ''),
        ]);

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

    /**
     * Release a held (auto_capture:false) payment. Octo allows cancellation only
     * for holds; a settled payment has to go through {@see refund()} instead.
     *
     * @param string   $paymentId octo_payment_UUID
     * @param int|null $amount    tiyin, for a partial release
     * @return PaymentResult
     */
    public function cancel($paymentId, $amount = null)
    {
        $body = array_merge($this->credentials(), [
            'octo_payment_UUID' => (string) $paymentId,
            'accept_status'     => 'cancel',
        ]);

        if ($amount !== null) {
            $body['final_amount'] = $this->toSom($amount);
        }

        return $this->resultFrom($this->request('/set_accept', $body), PaymentResult::STATUS_CANCELLED);
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

        // `error: 0` only means Octo accepted the request. The refund itself reports
        // succeeded / pending / failed, and calling a pending refund "refunded" would
        // close an order against money that has not moved yet.
        //
        // Note this is NOT mapStatus(): on a refund, "succeeded" means the refund
        // succeeded — the payment is refunded, not paid.
        $status = strtolower(trim((string) $this->pick($response, ['status'])));

        if ($status === 'succeeded') {
            $refundStatus = PaymentResult::STATUS_REFUNDED;
        } elseif ($status === 'failed') {
            $refundStatus = PaymentResult::STATUS_FAILED;
        } else {
            $refundStatus = PaymentResult::STATUS_PENDING;
        }

        return new PaymentResult($refundStatus, [
            'payment_id' => $this->pick($response, ['octo_payment_UUID']),
            'refund_id'  => $this->pick($response, ['refund_id', 'shop_refund_id']),
            'amount'     => (int) $amount,
            'raw'        => $response,
        ]);
    }

    public function status($reference)
    {
        // Octo has no dedicated status endpoint (help.octo.uz/check-status.html):
        // the read goes through /prepare_payment with credentials and the merchant
        // order id, and nothing else. The omission is what makes it a read — without
        // `total_sum` Octo cannot open a new transaction, so it reports the existing
        // one instead.
        $body = array_merge($this->credentials(), ['shop_transaction_id' => (string) $reference]);

        try {
            $response = $this->request('/prepare_payment', $body);
        } catch (CheckoutException $e) {
            // Octo allows roughly one status read per second per transaction and
            // answers "Requests too often" beyond that. A webhook, a return page and
            // a reconciliation sweep can easily collide on the same order, and the
            // caller would read a rate-limit complaint as "state unknown" — so the
            // one retry that resolves it happens here.
            if (!$this->isRateLimited($e)) {
                throw $e;
            }

            usleep(1100000);

            $response = $this->request('/prepare_payment', $body);
        }

        return $this->resultFrom($response, PaymentResult::STATUS_PENDING);
    }

    /**
     * @param CheckoutException $e
     * @return bool
     */
    protected function isRateLimited(CheckoutException $e)
    {
        return $e->getCode() === 11
            || stripos($e->getMessage(), 'too often') !== false;
    }

    /**
     * Confirm the SMS code for a payment (as opposed to a binding).
     *
     * Octo asks for one whenever a card transaction comes back `wait_user_action`,
     * including charges against a saved token. Note this endpoint is keyed on the
     * NUMERIC payment id from the /pay response (`data.id`), not the payment UUID,
     * and takes no shop credentials.
     *
     * @param int    $paymentId
     * @param int    $verifyId
     * @param string $smsKey
     * @return PaymentResult
     *
     * @throws CheckoutException on a rejected code
     */
    public function confirmPayment($paymentId, $verifyId, $smsKey)
    {
        return $this->resultFrom($this->request('/check_sms_key', [
            'smsKey'    => (string) $smsKey,
            'paymentId' => (int) $paymentId,
            'verifyId'  => (int) $verifyId,
        ]), PaymentResult::STATUS_PENDING);
    }

    // --- webhooks ---

    /**
     * {@inheritdoc}
     *
     * Octo signs its notifications as `sha1(unique_key + octo_payment_UUID + status)`
     * (help.octo.uz/notifications.html). The docs do not state the hex case and
     * merchants have been issued both, so the comparison is case-insensitive and
     * covers the `hash_key` field as well as `signature`. Without a configured
     * `unique_key` the webhook is rejected.
     *
     * The signature covers only the payment id and status — never the amount. Use
     * {@see \Goodoneuz\PayUz\Checkout\CheckoutManager::status()} to reconcile the
     * amount before granting value.
     */
    public function verifyWebhook(array $payload, array $headers = [])
    {
        $uniqueKey = $this->cfg('unique_key');

        if (!$uniqueKey) {
            return false;
        }

        $uuid   = isset($payload['octo_payment_UUID']) ? (string) $payload['octo_payment_UUID'] : '';
        $status = isset($payload['status']) ? (string) $payload['status'] : '';
        $expected = sha1($uniqueKey.$uuid.$status);

        foreach (['signature', 'hash_key'] as $field) {
            $given = isset($payload[$field]) ? strtolower(trim((string) $payload[$field])) : '';

            if ($given !== '' && hash_equals($expected, $given)) {
                return true;
            }
        }

        return false;
    }

    public function parseWebhook(array $payload)
    {
        $status = isset($payload['status']) ? (string) $payload['status'] : '';

        // NOTE: only octo_payment_UUID + status are covered by the signature.
        // `amount` and `masked_card` here are NOT authenticated — reconcile via
        // status() before granting value.
        return new PaymentResult($status !== '' ? $this->mapStatus($status) : PaymentResult::STATUS_PENDING, [
            'payment_id'  => isset($payload['octo_payment_UUID']) ? (string) $payload['octo_payment_UUID'] : '',
            'order_id'    => isset($payload['shop_transaction_id']) ? (string) $payload['shop_transaction_id'] : '',
            'amount'      => isset($payload['total_sum']) ? $this->toTiyin($payload['total_sum']) : 0,
            'masked_card' => isset($payload['maskedPan']) ? (string) $payload['maskedPan'] : null,
            'raw'         => $payload,
        ]);
    }

    // --- card binding (help.octo.uz/tokenization.html) ---

    public function bindableMethods()
    {
        $methods = $this->cfg('bindable_methods');

        // A control-panel field can only hold a string, so a comma-separated list is
        // accepted alongside the config file's array.
        if (is_string($methods)) {
            $methods = array_values(array_filter(array_map('trim', explode(',', $methods))));
        }

        // Octo documents binding for Humo and Uzcard. Overridable, because whether
        // international cards can be bound is a per-contract matter.
        return is_array($methods) && $methods ? $methods : ['uzcard', 'humo'];
    }

    public function startBinding(CardBinding $binding)
    {
        $method = $binding->method();

        if (!in_array($method, $this->bindableMethods(), true)) {
            throw new CheckoutException(sprintf('Octo cannot bind "%s" cards on this contract.', $method));
        }

        if (!$binding->phone()) {
            throw new CheckoutException('Octo needs a phone number to send the card binding code to.');
        }

        // Both callback URLs are mandatory on /bind_card. The published field table
        // marks them optional, but the live API rejects the call outright with
        // "Wrong params: [bindNotifyUrl, notifyUrl] must not be null or blank" —
        // and does so before it even looks at the shop, so there is no way around it.
        $bindNotifyUrl = $binding->notifyUrl() ?: $this->cfg('bind_notify_url');
        $notifyUrl     = $binding->paymentNotifyUrl() ?: $this->cfg('notify_url');

        foreach (['bind_notify_url' => $bindNotifyUrl, 'notify_url' => $notifyUrl] as $key => $value) {
            if (!$value) {
                throw new CheckoutException(sprintf('Octo requires a %s for card binding.', $key));
            }
        }

        $body = array_merge($this->credentials(), [
            'shop_transaction_id' => $binding->reference(),
            'pan'                 => $binding->pan(),
            'exp'                 => $binding->expireYYMM(),
            'phone'               => $binding->phone(),
            'method'              => $method,
            // Octo notifies the binding's own micro-transaction here; the token
            // itself arrives on bind_notify_url.
            'notify_url'          => $notifyUrl,
            'bind_notify_url'     => $bindNotifyUrl,
        ]);

        // Documented as required, but the live API binds without it, so an absent
        // one is omitted rather than sent as null.
        $returnUrl = $binding->returnUrl() ?: $this->cfg('return_url');

        if ($returnUrl) {
            $body['return_url'] = $returnUrl;
        }

        // Both optional on the live API; sent only when the caller supplied them.
        if ($binding->holderName()) {
            $body['cardHolderName'] = $binding->holderName();
        }

        if ($binding->cvc()) {
            $body['cvc2'] = $binding->cvc();
        }

        $response = $this->request('/bind_card', $body);

        $bindingId = (string) $this->pick($response, ['octo_payment_UUID']);

        if ($bindingId === '') {
            throw new CheckoutException('Octo did not return a binding identifier.');
        }

        return $this->verificationInfo($bindingId, $binding);
    }

    public function confirmBinding($bindingId, $verifyId, $code)
    {
        // Octo names the identifier `paymentUUID` on this endpoint alone — every
        // other call spells it `octo_payment_UUID`.
        $response = $this->request('/bind_card/check_sms_key', array_merge($this->credentials(), [
            'smsKey'      => (string) $code,
            'paymentUUID' => (string) $bindingId,
            'verifyId'    => (int) $verifyId,
        ]));

        return new BindingSession([
            'binding_id' => $bindingId,
            'verify_id'  => $verifyId,
            'status'     => $this->pick($response, ['status']),
            'first_six'  => $this->pick($response, ['first6']),
            'last_four'  => $this->pick($response, ['last4']),
            // The code was accepted, so there is nothing left to wait for; the token
            // now comes on the binding callback.
            'seconds_left' => 0,
            'raw'          => $response,
        ]);
    }

    public function parseBindCallback(array $payload)
    {
        // The callback carries a full PAN. Only the BIN and the last four digits are
        // kept, and the raw copy has the number removed, so nothing downstream can
        // persist or log a card number by accident.
        $pan = isset($payload['pan']) ? preg_replace('/\D/', '', (string) $payload['pan']) : '';

        $raw = $payload;
        unset($raw['pan']);

        return new BoundCard([
            'token'       => isset($payload['card_token']) ? $payload['card_token'] : null,
            'reference'   => isset($payload['shop_transaction_id']) ? $payload['shop_transaction_id'] : '',
            'status'      => isset($payload['status']) ? $payload['status'] : '',
            'first_six'   => strlen($pan) >= 6 ? substr($pan, 0, 6) : null,
            'last_four'   => strlen($pan) >= 4 ? substr($pan, -4) : null,
            'expire'      => isset($payload['exp']) ? $payload['exp'] : null,
            'holder_name' => isset($payload['cardHolderName']) ? $payload['cardHolderName'] : null,
            'raw'         => $raw,
        ]);
    }

    public function revokeToken($token)
    {
        $this->request('/block_card_token', array_merge($this->credentials(), [
            'card_token' => (string) $token,
        ]));

        return true;
    }

    /**
     * Where the confirmation code went and how long it is valid. Also used to
     * resume a binding whose code screen was reloaded.
     *
     * @param string           $bindingId
     * @param CardBinding|null $binding
     * @return BindingSession
     */
    public function verificationInfo($bindingId, ?CardBinding $binding = null)
    {
        $response = $this->get('/verificationInfo/'.rawurlencode($bindingId));

        return new BindingSession([
            'binding_id'   => $bindingId,
            'verify_id'    => $this->pick($response, ['verifyId']),
            'phone'        => $this->pick($response, ['phone']),
            'seconds_left' => $this->pick($response, ['secondsLeft']),
            'first_six'    => $binding === null ? null : $binding->firstSix(),
            'last_four'    => $binding === null ? null : $binding->lastFour(),
            'raw'          => $response,
        ]);
    }

    // --- internals ---

    /**
     * @param string $path
     * @param array  $body
     * @return array decoded response
     *
     * @throws CheckoutException on transport/HTTP fault or a non-zero `error`
     */
    protected function request($path, array $body)
    {
        $this->assertConfigured();

        return $this->handle($this->http->post(self::BASE_URL.$path, $body));
    }

    /**
     * @param string $path
     * @return array
     *
     * @throws CheckoutException
     */
    protected function get($path)
    {
        $this->assertConfigured();

        return $this->handle($this->http->request('GET', self::BASE_URL.$path, null));
    }

    /**
     * @param array $response transport envelope
     * @return array
     *
     * @throws CheckoutException
     */
    protected function handle(array $response)
    {
        $status = isset($response['status']) ? (int) $response['status'] : 0;
        $resp   = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

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
     * @param string $octo
     * @return string
     */
    protected function mapStatus($octo)
    {
        $key = strtolower(trim((string) $octo));

        // An unrecognised state is "in progress", never a terminal outcome — a
        // status string Octo adds later must not cancel or settle an order.
        return isset(self::$statusMap[$key]) ? self::$statusMap[$key] : PaymentResult::STATUS_PENDING;
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

    /**
     * A flag that may have come from a control-panel text field.
     *
     * `(bool) "false"` is true in PHP, which would silently put a live shop into
     * test mode (or worse, the reverse), so string forms are parsed properly.
     *
     * @param string $key
     * @param bool   $default
     * @return bool
     */
    protected function boolCfg($key, $default = false)
    {
        $value = $this->cfg($key, $default);

        return is_bool($value) ? $value : (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
