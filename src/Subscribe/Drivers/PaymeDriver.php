<?php

namespace Goodoneuz\PayUz\Subscribe\Drivers;

use Goodoneuz\PayUz\Subscribe\Card;
use Goodoneuz\PayUz\Subscribe\Charge;
use Goodoneuz\PayUz\Subscribe\VerifyCode;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Support\Http\JsonRpcClient;
use Goodoneuz\PayUz\Support\Http\JsonRpcException;
use Goodoneuz\PayUz\Subscribe\Contracts\SubscribeDriver;
use Goodoneuz\PayUz\Subscribe\Exceptions\SubscribeException;

/**
 * Payme Subscribe API driver (JSON-RPC 2.0, distinct from the Merchant API).
 *
 * The load-bearing rule, enforced here: the `X-Auth` header is the cashbox id
 * ALONE for the browser-safe token-minting methods (`cards.create`,
 * `cards.get_verify_code`, `cards.verify`), and `id:key` (with the secret key)
 * for every server-side method — `cards.check`, `cards.remove` and all
 * `receipts.*`. Sending the id-only header to a server-side method yields
 * gateway error -32504 (mapped to AuthorizationException).
 *
 * Config (under `subscribe.drivers.payme`):
 *   merchant_id — cashbox id (required)
 *   key         — secret X-Auth payment key from the cabinet (required for the
 *                 server-side methods; receipts cannot be charged without it)
 *   test        — bool, use the sandbox endpoint
 *
 * Amounts are tiyin. Only the returned token is persistable — never the PAN.
 */
class PaymeDriver implements SubscribeDriver
{
    const ENDPOINT_PROD = 'https://checkout.paycom.uz/api';
    const ENDPOINT_TEST = 'https://checkout.test.paycom.uz/api';

    /** Methods that authenticate with the cashbox id only (browser-safe). */
    const CLIENT_METHODS = ['cards.create', 'cards.get_verify_code', 'cards.verify'];

    /** @var array */
    protected $config;

    /** @var JsonRpcClient */
    protected $rpc;

    /**
     * @param array      $config
     * @param HttpClient $http
     */
    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->rpc    = new JsonRpcClient($http);
    }

    // --- card tokenization ---

    public function createCard($number, $expire, $save = true, array $account = [])
    {
        $params = [
            'card' => ['number' => (string) $number, 'expire' => (string) $expire],
            'save' => (bool) $save,
        ];
        if ($account) {
            $params['account'] = $account;
        }

        return Card::fromResult($this->call('cards.create', $params));
    }

    public function sendVerifyCode($token)
    {
        return VerifyCode::fromResult($this->call('cards.get_verify_code', ['token' => (string) $token]));
    }

    public function verifyCard($token, $code)
    {
        return Card::fromResult($this->call('cards.verify', [
            'token' => (string) $token,
            'code'  => (string) $code,
        ]));
    }

    public function checkCard($token)
    {
        return Card::fromResult($this->call('cards.check', ['token' => (string) $token]));
    }

    public function removeCard($token)
    {
        $result = $this->call('cards.remove', ['token' => (string) $token]);

        return !empty($result['success']);
    }

    // --- receipts / charges ---

    public function createReceipt($amount, array $account, array $options = [])
    {
        $params = ['amount' => (int) $amount, 'account' => $account];

        if (isset($options['description'])) {
            $params['description'] = $options['description'];
        }
        if (isset($options['detail'])) {
            $params['detail'] = $options['detail'];
        }
        if (!empty($options['hold'])) {
            $params['hold'] = true;
        }

        return Charge::fromResult($this->call('receipts.create', $params));
    }

    public function payReceipt($receiptId, $token, array $options = [])
    {
        $params = ['id' => (string) $receiptId, 'token' => (string) $token];

        if (isset($options['payer'])) {
            $params['payer'] = $options['payer'];
        }
        if (!empty($options['hold'])) {
            $params['hold'] = true;
        }

        return Charge::fromResult($this->call('receipts.pay', $params));
    }

    public function cancelReceipt($receiptId)
    {
        return Charge::fromResult($this->call('receipts.cancel', ['id' => (string) $receiptId]));
    }

    public function checkReceipt($receiptId)
    {
        $result = $this->call('receipts.check', ['id' => (string) $receiptId]);

        return isset($result['state']) ? (int) $result['state'] : Charge::STATE_CREATED;
    }

    public function getReceipt($receiptId)
    {
        return Charge::fromResult($this->call('receipts.get', ['id' => (string) $receiptId]));
    }

    // --- holds ---

    public function confirmHold($receiptId)
    {
        return Charge::fromResult($this->call('receipts.confirm_hold', ['id' => (string) $receiptId]));
    }

    public function name()
    {
        return 'payme';
    }

    // --- internals ---

    /**
     * @return string
     */
    protected function endpoint()
    {
        return !empty($this->config['test']) ? self::ENDPOINT_TEST : self::ENDPOINT_PROD;
    }

    /**
     * The X-Auth value for a method: id-only for client methods, id:key otherwise.
     *
     * @param string $method
     * @return string
     */
    protected function xAuth($method)
    {
        $id = isset($this->config['merchant_id']) ? $this->config['merchant_id'] : '';

        if ($this->isClientMethod($method)) {
            return (string) $id;
        }

        return $id.':'.(isset($this->config['key']) ? $this->config['key'] : '');
    }

    /**
     * @param string $method
     * @return bool
     */
    protected function isClientMethod($method)
    {
        return in_array($method, self::CLIENT_METHODS, true);
    }

    /**
     * Make the call, enforcing configuration and translating JSON-RPC errors.
     *
     * @param string $method
     * @param array  $params
     * @return array
     * @throws SubscribeException
     */
    protected function call($method, array $params)
    {
        if (empty($this->config['merchant_id'])) {
            throw new SubscribeException('Payme Subscribe driver is not configured: missing "merchant_id".');
        }
        if (!$this->isClientMethod($method) && empty($this->config['key'])) {
            throw new SubscribeException(sprintf(
                'Payme Subscribe driver requires a "key" for the server-side method %s.',
                $method
            ));
        }

        try {
            return $this->rpc->call($this->endpoint(), $method, $params, ['X-Auth' => $this->xAuth($method)]);
        } catch (JsonRpcException $e) {
            throw SubscribeException::fromJsonRpc($e);
        }
    }
}
