<?php

namespace Goodoneuz\PayUz\Subscribe\Drivers;

use Goodoneuz\PayUz\Subscribe\Card;
use Goodoneuz\PayUz\Subscribe\Charge;
use Goodoneuz\PayUz\Subscribe\VerifyCode;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Subscribe\Contracts\SubscribeDriver;
use Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException;
use Goodoneuz\PayUz\Subscribe\Exceptions\OperationException;

/**
 * ATMOS (atmos.uz) driver — modelled as a Subscribe (card-vault + OTP) gateway,
 * because ATMOS's verified shape is server-side card binding + create/pre-apply/
 * apply, NOT a hosted redirect. The lifecycle maps one-to-one onto SubscribeDriver:
 *
 *   createCard  -> POST /merchant/card/bind          (-> binding_id, OTP sent)
 *   verifyCard  -> POST /merchant/card/bind/confirm  (-> card_token)
 *   create/pay  -> POST /merchant/pay/create then /pay/pre-apply + /pay/apply
 *   cancel/info -> POST /merchant/pay/cancel | /merchant/pay/info
 *
 * Notable ATMOS specifics:
 *  - **Amounts are already tiyin** — unlike Octo, this driver does NOT convert.
 *  - Auth is OAuth2 client_credentials: POST /token (form-encoded, Basic
 *    consumer_key:consumer_secret) -> a bearer token, cached until ~60s before it
 *    expires. Every /merchant/* call sends the bearer + `store_id` in the body.
 *  - Success is `result.code == 'OK'`; anything else throws SubscribeException.
 *  - Card expiry is "YYmm" (Subscribe's "MMYY" is transposed here).
 *  - The two-handle bind quirk: createCard yields a `binding_id` (carried as the
 *    interim Card token); verifyCard swaps it for the real `card_token`.
 *  - Hold/capture and partial refund have NO verified endpoint — confirmHold
 *    throws; cancelReceipt is a whole-transaction reversal.
 *
 * NOTE: paths/fields are verified against community SDKs, not a reachable
 * docs.atmos.uz; confirm the webhook signature, the "YYmm" order and hold support
 * against the official docs before production.
 */
class AtmosDriver implements SubscribeDriver
{
    const BASE_PROD = 'https://partner.atmos.uz';
    const BASE_TEST = 'https://test-partner.atmos.uz';

    /** Fixed OTP ATMOS uses for saved-token charges (no real SMS). */
    const TOKEN_OTP = '111111';

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

    // --- card vault ---

    public function createCard($number, $expire, $save = true, array $account = [])
    {
        $phone = isset($account['phone']) ? $account['phone'] : null;
        if (!$phone) {
            throw new SubscribeException('ATMOS card bind requires a phone number in $account["phone"].');
        }

        $resp = $this->request('/merchant/card/bind', [
            'card_number' => (string) $number,
            'expiry'      => $this->atmosExpiry($expire),
            'phone'       => (string) $phone,
        ]);

        // The binding_id is the interim handle until the OTP is confirmed.
        return new Card([
            'token'     => (string) $this->pick($resp, ['binding_id']),
            'verify'    => false,
            'recurrent' => (bool) $save,
        ]);
    }

    public function sendVerifyCode($token)
    {
        // ATMOS sends the OTP during createCard (card/bind); there is no separate
        // resend endpoint, so this reports the already-sent code without a call.
        return new VerifyCode(['sent' => true, 'phone' => '', 'wait' => 0]);
    }

    public function verifyCard($token, $code)
    {
        $resp = $this->request('/merchant/card/bind/confirm', [
            'binding_id' => $this->numericId($token),
            'otp'        => (string) $code,
        ]);

        return new Card([
            'token'     => (string) $this->pick($resp, ['card_token', 'card_id']),
            'number'    => (string) $this->pick($resp, ['masked_pan', 'card_number']),
            'verify'    => true,
            'recurrent' => true,
        ]);
    }

    public function checkCard($token)
    {
        $resp  = $this->request('/merchant/card/list', []);
        $cards = $this->pick($resp, ['cards']);
        $cards = is_array($cards) ? $cards : [];

        foreach ($cards as $card) {
            $cardToken = isset($card['token']) ? $card['token'] : (isset($card['card_id']) ? $card['card_id'] : null);
            if ((string) $cardToken === (string) $token) {
                return new Card([
                    'token'     => (string) $cardToken,
                    'number'    => isset($card['masked_pan']) ? $card['masked_pan'] : (isset($card['card_number']) ? $card['card_number'] : ''),
                    'verify'    => !isset($card['status']) || !empty($card['status']),
                    'recurrent' => true,
                ]);
            }
        }

        throw new SubscribeException('ATMOS card not found for the given token.');
    }

    public function removeCard($token)
    {
        $this->request('/merchant/card/unbind', ['card_token' => (string) $token]);

        return true; // request() throws unless result.code == 'OK'
    }

    // --- receipts / charges ---

    public function createReceipt($amount, array $account, array $options = [])
    {
        $body = [
            'amount'  => (int) $amount, // already tiyin — no conversion
            'account' => $this->accountString($account),
            'lang'    => $this->cfg('lang', 'uz'),
        ];
        if ($this->cfg('terminal_id')) {
            $body['terminal_id'] = $this->cfg('terminal_id');
        }
        // Contract option is 'description' (a free string); map it to ATMOS's
        // `details` field. 'details' is still accepted as an alias.
        $details = isset($options['description']) ? $options['description']
            : (isset($options['details']) ? $options['details'] : null);
        if ($details !== null) {
            $body['details'] = $details;
        }

        $resp = $this->request('/merchant/pay/create', $body);

        return $this->chargeFrom($resp, $this->pick($resp, ['transaction_id']));
    }

    public function payReceipt($receiptId, $token, array $options = [])
    {
        if (!empty($options['hold'])) {
            throw new OperationException('ATMOS two-stage hold is not supported by this driver.');
        }

        $transId = $this->numericId($receiptId);

        // Bind the saved token to the transaction, then confirm. A saved token
        // charges with the fixed OTP (no fresh customer SMS).
        $this->request('/merchant/pay/pre-apply', ['transaction_id' => $transId, 'card_token' => (string) $token]);

        $otp  = isset($options['otp']) ? (string) $options['otp'] : self::TOKEN_OTP;
        $resp = $this->request('/merchant/pay/apply', ['transaction_id' => $transId, 'otp' => $otp]);

        return $this->chargeFrom($resp, $receiptId);
    }

    public function cancelReceipt($receiptId)
    {
        $this->request('/merchant/pay/cancel', ['transaction_id' => $this->numericId($receiptId)]);

        return new Charge(['_id' => (string) $receiptId, 'state' => Charge::STATE_CANCELLED]);
    }

    public function checkReceipt($receiptId)
    {
        return $this->getReceipt($receiptId)->state();
    }

    public function getReceipt($receiptId)
    {
        $resp = $this->request('/merchant/pay/info', ['transaction_id' => $this->numericId($receiptId)]);

        return $this->chargeFrom($resp, $receiptId);
    }

    public function confirmHold($receiptId)
    {
        throw new OperationException('ATMOS hold/capture is not available in this driver (no verified endpoint).');
    }

    public function name()
    {
        return 'atmos';
    }

    // --- ATMOS-specific webhook helpers (the Subscribe contract has no webhook
    //     methods; a controller calls these for ATMOS's merchant-cabinet callback) ---

    /**
     * Verify an ATMOS callback signature.
     *
     * WARNING: the recipe md5(store_id . transaction_id . invoice . amount .
     * api_key) is taken from community SDKs and is NOT confirmed against the
     * official docs — verify against a live callback. The signature does not cover
     * the card and the amount is a tiyin string: reconcile via {@see getReceipt()}
     * before granting value.
     *
     * @param array $payload
     * @return bool
     */
    public function verifyCallback(array $payload)
    {
        $apiKey = $this->cfg('api_key');
        $sign   = isset($payload['sign']) ? (string) $payload['sign'] : '';
        if (!$apiKey || $sign === '') {
            return false;
        }

        $expected = md5(
            $this->str($payload, 'store_id')
            .$this->str($payload, 'transaction_id')
            .$this->str($payload, 'invoice')
            .$this->str($payload, 'amount')
            .$apiKey
        );

        return hash_equals($expected, $sign);
    }

    /**
     * Normalize an ATMOS callback into a Charge.
     *
     * The callback only carries {transaction_id, amount} (no status), and its
     * signature is uncertain, so this deliberately returns an UNCONFIRMED charge
     * (state created) — never treat the callback as proof of payment. Call
     * {@see verifyCallback()} first, then {@see getReceipt()} to obtain the
     * authoritative paid/cancelled state before granting value.
     *
     * @param array $payload
     * @return Charge
     */
    public function parseCallback(array $payload)
    {
        return new Charge([
            '_id'    => $this->str($payload, 'transaction_id'),
            'state'  => Charge::STATE_CREATED, // a nudge, not a confirmation
            'amount' => isset($payload['amount']) ? (int) $payload['amount'] : 0,
        ]);
    }

    // --- internals ---

    /**
     * Obtain (and cache) the OAuth2 bearer token.
     *
     * @return string
     * @throws SubscribeException
     */
    protected function token()
    {
        if ($this->token && $this->now() < $this->tokenExpiresAt) {
            return $this->token;
        }

        if (!$this->cfg('consumer_key') || !$this->cfg('consumer_secret')) {
            throw new SubscribeException('ATMOS driver is not configured: missing "consumer_key"/"consumer_secret".');
        }

        $basic    = base64_encode($this->cfg('consumer_key').':'.$this->cfg('consumer_secret'));
        $response = $this->http->postForm($this->baseUrl().'/token', ['grant_type' => 'client_credentials'], [
            'Authorization' => 'Basic '.$basic,
        ]);
        $body = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        if (empty($body['access_token'])) {
            throw new SubscribeException('ATMOS authentication failed: no access_token in the token response.');
        }

        $this->token          = (string) $body['access_token'];
        $expiresIn            = isset($body['expires_in']) ? (int) $body['expires_in'] : 3600;
        $this->tokenExpiresAt = $this->now() + max(60, $expiresIn - 60);

        return $this->token;
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

    /**
     * POST an authenticated /merchant call and assert ATMOS's OK envelope.
     *
     * @param string $path
     * @param array  $body
     * @return array
     * @throws SubscribeException
     */
    protected function request($path, array $body)
    {
        if (!$this->cfg('store_id')) {
            throw new SubscribeException('ATMOS driver is not configured: missing "store_id".');
        }

        $body['store_id'] = (string) $this->cfg('store_id');

        $response = $this->http->post($this->baseUrl().$path, $body, [
            'Authorization' => 'Bearer '.$this->token(),
        ]);
        $resp = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        $code = isset($resp['result']['code']) ? $resp['result']['code'] : null;
        if ($code !== 'OK') {
            $message = isset($resp['result']['description']) ? $resp['result']['description'] : 'ATMOS request failed.';
            throw new SubscribeException((string) $message, 0, $resp, $resp);
        }

        return $resp;
    }

    /**
     * Build a Charge from a create/apply/info response (confirmed -> paid).
     *
     * @param array       $resp
     * @param mixed       $receiptId
     * @return Charge
     */
    protected function chargeFrom(array $resp, $receiptId)
    {
        $tx        = isset($resp['store_transaction']) && is_array($resp['store_transaction']) ? $resp['store_transaction'] : [];
        $confirmed = !empty($tx['confirmed']);
        $id        = $receiptId !== null && $receiptId !== '' ? $receiptId : $this->pick($resp, ['transaction_id']);

        return new Charge([
            '_id'    => (string) $id,
            'state'  => $confirmed ? Charge::STATE_PAID : Charge::STATE_CREATED,
            'amount' => isset($tx['amount']) ? (int) $tx['amount'] : 0,
        ]);
    }

    /**
     * @return string
     */
    protected function baseUrl()
    {
        return $this->cfg('test', false) ? self::BASE_TEST : self::BASE_PROD;
    }

    /**
     * Transpose Subscribe's "MMYY" expiry to ATMOS's "YYmm".
     *
     * @param string $mmyy
     * @return string
     */
    protected function atmosExpiry($mmyy)
    {
        $digits = preg_replace('/\D+/', '', (string) $mmyy);

        return strlen($digits) === 4 ? substr($digits, 2, 2).substr($digits, 0, 2) : $digits;
    }

    /**
     * ATMOS ids (binding_id, transaction_id) are integers — cast numerics to int.
     *
     * @param mixed $value
     * @return int|string
     */
    protected function numericId($value)
    {
        return is_numeric($value) ? (int) $value : (string) $value;
    }

    /**
     * @param array $account
     * @return string
     * @throws SubscribeException
     */
    protected function accountString(array $account)
    {
        if (isset($account['account'])) {
            return (string) $account['account'];
        }
        if (!empty($account)) {
            return (string) reset($account);
        }

        throw new SubscribeException('ATMOS createReceipt requires an account identifier in $account.');
    }

    /**
     * First present value among $keys, at the top level or inside a `data` wrapper.
     *
     * @param array $resp
     * @param array $keys
     * @return mixed|null
     */
    protected function pick(array $resp, array $keys)
    {
        $haystacks = [$resp];
        if (isset($resp['data']) && is_array($resp['data'])) {
            $haystacks[] = $resp['data'];
        }

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
     * @param array  $payload
     * @param string $key
     * @return string
     */
    protected function str(array $payload, $key)
    {
        return isset($payload[$key]) ? (string) $payload[$key] : '';
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
