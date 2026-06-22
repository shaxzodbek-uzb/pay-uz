<?php

namespace Goodoneuz\PayUz\Bnpl\Drivers;

use Goodoneuz\PayUz\Bnpl\ValueObjects\Contract;
use Goodoneuz\PayUz\Bnpl\ValueObjects\Eligibility;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Bnpl\Contracts\BnplDriver;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractResult;
use Goodoneuz\PayUz\Bnpl\ValueObjects\ContractStatus;
use Goodoneuz\PayUz\Bnpl\ValueObjects\InstallmentPlan;
use Goodoneuz\PayUz\Bnpl\Exceptions\BnplException;

/**
 * Uzum Nasiya (BNPL / installments) driver, coded against Uzum's official
 * "Nasiya Partner API" OpenAPI spec.
 *
 * Flow: checkEligibility -> calculate -> createContract -> (buyer signs in the
 * webview) -> confirm -> status. Cancel is full-only.
 *
 * Wire facts:
 *  - Base URL https://merchants-api.uzumnasiya.uz; all ops are POST/JSON.
 *  - Auth is a per-partner Bearer JWT (issued at onboarding; no self-serve key,
 *    no published sandbox).
 *  - INFERRED: amounts on the wire are decimal SOM (the spec types them as
 *    `number` and never says "tiyin"), so this driver converts tiyin->som out and
 *    som->tiyin in. Confirm against a live payload during onboarding.
 *  - The two ids: confirm()/status() use `contract_id`; cancel() uses the `order`
 *    id (carried as {@see Contract::orderId()}), which Nasiya also names contract_id.
 *  - UNCERTAIN: whether activation is partner-driven OTP (/v3) or WebView-only.
 *    The /v3 OTP pair is exposed as driver-specific methods gated on `otp_mode`.
 */
class UzumNasiyaDriver implements BnplDriver
{
    const BASE_URL = 'https://merchants-api.uzumnasiya.uz';

    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function checkEligibility($phone)
    {
        $response = $this->call('/api/v1/buyers/check-status', ['phone' => (int) $this->digits($phone)]);
        $this->assertOk($response);

        return new Eligibility($this->data($response));
    }

    public function calculate($buyerId, array $items)
    {
        $response = $this->call('/api/v1/orders/calculate', [
            'user_id'  => (int) $buyerId,
            'products' => array_map(array($this, 'toWireProduct'), $items),
        ]);
        $this->assertOk($response);

        $plans = [];
        foreach ($this->data($response) as $tariff) {
            $plans[] = $this->planFromTariff($tariff);
        }

        return $plans;
    }

    public function createContract($buyerId, $period, array $items, $extOrderId = null, $returnUrl = null)
    {
        $body = [
            'user_id'  => (int) $buyerId,
            'period'   => (string) $period,
            'products' => array_map(array($this, 'toWireProduct'), $items),
        ];
        if ($returnUrl) {
            $body['callback'] = $returnUrl;
        }
        if ($extOrderId) {
            $body['ext_order_id'] = $extOrderId;
        }

        $response = $this->call('/api/v1/orders', $body);
        $this->assertOk($response);

        return $this->contractFrom($this->data($response));
    }

    public function confirm($contractId)
    {
        $response = $this->call('/api/v1/contracts/confirm', ['contract_id' => (int) $contractId]);
        $this->assertAuthorized($response);

        return ContractResult::fromResponse($response['body'], $response['status']);
    }

    public function cancel($orderId)
    {
        // Nasiya's cancel is keyed on the `order` id, sent in the contract_id field.
        $response = $this->call('/api/v1/contracts/cancel', ['contract_id' => (int) $orderId]);
        $this->assertAuthorized($response);

        return ContractResult::fromResponse($response['body'], $response['status']);
    }

    public function status($contractId)
    {
        $response = $this->call('/api/v1/contracts/check-status', ['contract_id' => (int) $contractId]);
        $this->assertOk($response);

        return new ContractStatus($this->data($response));
    }

    public function name()
    {
        return 'uzum_nasiya';
    }

    // --- driver-specific OTP (UNCERTAIN role; used only when otp_mode='sms') ---

    /**
     * @param string $phone
     * @param int    $contractId
     * @return array
     */
    public function sendSmsCode($phone, $contractId)
    {
        $this->assertOtpEnabled();
        $response = $this->call('/v3/buyers/send-code-sms', [
            'phone'       => (int) $this->digits($phone),
            'contract_id' => (int) $contractId,
        ]);
        $this->assertOk($response);

        return $this->data($response);
    }

    /**
     * @param string $phone
     * @param int    $contractId
     * @param string $code
     * @return ContractResult
     */
    public function verifySmsCode($phone, $contractId, $code)
    {
        $this->assertOtpEnabled();
        $response = $this->call('/v3/buyers/check-code-sms', [
            'phone'       => (int) $this->digits($phone),
            'contract_id' => (int) $contractId,
            'code'        => (string) $code,
        ]);
        $this->assertAuthorized($response);

        return ContractResult::fromResponse($response['body'], $response['status']);
    }

    // --- internals ---

    /**
     * @param string $path
     * @param array  $payload
     * @return array ['status' => int, 'body' => array]
     */
    protected function call($path, array $payload)
    {
        $this->assertConfigured();

        $response = $this->http->post(self::baseUrl($this->config).$path, $payload, [
            'Authorization' => 'Bearer '.$this->cfg('token'),
        ]);

        return [
            'status' => isset($response['status']) ? (int) $response['status'] : 0,
            'body'   => isset($response['body']) && is_array($response['body']) ? $response['body'] : [],
        ];
    }

    /**
     * Throw on any non-2xx (auth/validation/transport) for the value-returning ops.
     *
     * @param array $response
     * @throws BnplException
     */
    protected function assertOk(array $response)
    {
        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new BnplException($this->errorMessage($response['body']), $response['status'], $response['body']);
        }
    }

    /**
     * Throw only on auth failure (401/403); business 4xx for confirm/cancel are
     * carried in the ContractResult response_code, not thrown.
     *
     * @param array $response
     * @throws BnplException
     */
    protected function assertAuthorized(array $response)
    {
        if ($response['status'] === 401 || $response['status'] === 403) {
            throw new BnplException($this->errorMessage($response['body']), $response['status'], $response['body']);
        }
    }

    /**
     * @param array $body
     * @return string
     */
    protected function errorMessage(array $body)
    {
        if (isset($body['message']) && $body['message'] !== '') {
            return (string) $body['message'];
        }
        if (isset($body['error'])) {
            $error = $body['error'];
            if (is_array($error)) {
                $first = reset($error);

                return (string) (is_array($first) ? json_encode($first) : $first);
            }

            return (string) $error;
        }

        return 'Uzum Nasiya request failed.';
    }

    /**
     * @param array $response
     * @return array
     */
    protected function data(array $response)
    {
        return isset($response['body']['data']) && is_array($response['body']['data']) ? $response['body']['data'] : [];
    }

    /**
     * Convert an item's tiyin price to som and forward the rest verbatim.
     *
     * @param array $item
     * @return array
     */
    protected function toWireProduct(array $item)
    {
        if (isset($item['price'])) {
            $item['price'] = $this->toSom($item['price']);
        }

        return $item;
    }

    /**
     * @param array $tariff
     * @return InstallmentPlan
     */
    protected function planFromTariff(array $tariff)
    {
        return new InstallmentPlan([
            'tariff_id'          => isset($tariff['tariff']) ? $tariff['tariff'] : '',
            'period_months'      => isset($tariff['period_months']) ? $tariff['period_months'] : 0,
            'total'              => $this->toTiyin(isset($tariff['total']) ? $tariff['total'] : 0),
            'origin'             => $this->toTiyin(isset($tariff['origin']) ? $tariff['origin'] : 0),
            'monthly'            => $this->toTiyin(isset($tariff['month']) ? $tariff['month'] : 0),
            'deposit'            => $this->toTiyin(isset($tariff['deposit']) ? $tariff['deposit'] : 0),
            'first_payment_date' => isset($tariff['first_payment_date']) ? $tariff['first_payment_date'] : null,
            'is_available'       => !empty($tariff['is_available']),
            'is_mini_loan'       => !empty($tariff['is_mini_loan']),
            'raw'                => $tariff,
        ]);
    }

    /**
     * @param array $data the `data` of an `orders` response
     * @return Contract
     */
    protected function contractFrom(array $data)
    {
        $client = isset($data['paymart_client']) && is_array($data['paymart_client']) ? $data['paymart_client'] : [];

        return new Contract([
            'contract_id'  => isset($client['contract_id']) ? $client['contract_id'] : 0,
            'order_id'     => isset($client['order']) ? $client['order'] : 0,
            'total'        => $this->toTiyin(isset($client['total']) ? $client['total'] : 0),
            'monthly'      => $this->toTiyin(isset($client['price_month']) ? $client['price_month'] : 0),
            'webview_path' => isset($data['webview_path']) ? $data['webview_path'] : null,
            'act_pdf_url'  => isset($data['client_act_pdf']) ? $data['client_act_pdf'] : null,
            'raw'          => $data,
        ]);
    }

    /**
     * @throws BnplException
     */
    protected function assertConfigured()
    {
        if (!$this->cfg('token')) {
            throw new BnplException('Uzum Nasiya driver is not configured: missing "token" (partner Bearer JWT).');
        }
    }

    /**
     * The /v3 OTP pair is only meaningful when the partner drives activation by
     * SMS; the default flow signs in the WebView. Guard so the methods cannot be
     * called in WebView mode by mistake.
     *
     * @throws BnplException
     */
    protected function assertOtpEnabled()
    {
        if ($this->cfg('otp_mode', 'webview') !== 'sms') {
            throw new BnplException('Uzum Nasiya OTP methods require otp_mode="sms"; the default flow activates in the WebView.');
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
     * @param string|int $phone
     * @return string digits only
     */
    protected function digits($phone)
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    /**
     * @param array $config
     * @return string
     */
    protected static function baseUrl(array $config)
    {
        $base = isset($config['base_url']) && $config['base_url'] !== '' ? $config['base_url'] : self::BASE_URL;

        return rtrim($base, '/');
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
