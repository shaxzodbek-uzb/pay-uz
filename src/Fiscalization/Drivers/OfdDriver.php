<?php

namespace Goodoneuz\PayUz\Fiscalization\Drivers;

use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Goodoneuz\PayUz\Fiscalization\FiscalResult;
use Goodoneuz\PayUz\Support\Http\HttpClient;
use Goodoneuz\PayUz\Fiscalization\Contracts\FiscalDriver;
use Goodoneuz\PayUz\Fiscalization\Exceptions\FiscalizationException;

/**
 * Generic OFD / virtual-kassa HTTP driver using the standard soliq fiscal-receipt
 * shape — the PascalCase format the Tax Committee's Fiscal Data Operator and the
 * commercial virtual-kassa HTTP gateways consume:
 *
 *   line:    Name, SPIC (=MXIK), PackageCode, GoodPrice (unit), Price (line),
 *            Amount (quantity × 1000), VAT, VATPercent, Discount
 *   receipt: IsRefund (0|1), Items, ReceivedCash, ReceivedCard, Time
 *   result:  FiscalSign, QRCodeURL, TerminalId, ReceiptSeq, DateTime
 *
 * Point it at your provider's register-receipt endpoint with a bearer token:
 *
 *   'ofd' => [
 *       'endpoint'    => 'https://<your-ofd-gateway>/receipt',
 *       'token'       => '<api token>',
 *       'terminal_id' => '<optional terminal id>',
 *   ]
 *
 * Notes:
 *  - Amounts are in tiyin; quantity is scaled ×1000 (soliq's 3-decimal convention).
 *  - The response parser is deliberately tolerant: it reads PascalCase, snake_case
 *    and the `receipt_gnk_*` variants, at the top level or inside a `data`/`result`
 *    envelope, and keeps the full raw response on the FiscalResult.
 *  - Direct submission to ofd.uz additionally requires PKCS#7 signing of the body
 *    with the taxpayer certificate; that is out of scope here — use a gateway that
 *    signs on its side, or register a signing driver via Fiscalizer::extend().
 *  - Multikassa (multibank) is a *local* cashier agent (http://localhost:8080),
 *    not a server-side API, so it is intentionally not shipped as a driver.
 */
class OfdDriver implements FiscalDriver
{
    /** @var array */
    protected $config;

    /** @var HttpClient */
    protected $http;

    /**
     * @param array      $config
     * @param HttpClient $http
     */
    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    /**
     * {@inheritdoc}
     */
    public function fiscalize(Receipt $receipt)
    {
        $receipt->assertValid();

        return $this->parse($this->http->post(
            $this->endpoint(),
            $this->buildPayload($receipt),
            $this->headers()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'ofd';
    }

    /**
     * @return string
     * @throws FiscalizationException when not configured.
     */
    protected function endpoint()
    {
        $endpoint = isset($this->config['endpoint']) ? $this->config['endpoint'] : null;
        if (!$endpoint && isset($this->config['base_url'])) {
            $endpoint = $this->config['base_url'];
        }

        if (empty($endpoint)) {
            throw new FiscalizationException('OFD driver is not configured: missing "endpoint".');
        }
        if (empty($this->config['token'])) {
            throw new FiscalizationException('OFD driver is not configured: missing "token".');
        }

        return $endpoint;
    }

    /**
     * @return array
     */
    protected function headers()
    {
        return ['Authorization' => 'Bearer '.$this->config['token']];
    }

    /**
     * @param Receipt $receipt
     * @return array
     */
    protected function buildPayload(Receipt $receipt)
    {
        list($cash, $card) = $receipt->payment();

        $payload = [
            'IsRefund'     => $receipt->isRefund() ? 1 : 0,
            'OrderId'      => $receipt->orderId(),
            'Items'        => $this->buildItems($receipt),
            'ReceivedCash' => $cash,
            'ReceivedCard' => $card,
            'Time'         => $this->time($receipt),
        ];

        if (!empty($this->config['terminal_id'])) {
            $payload['TerminalId'] = $this->config['terminal_id'];
        }

        // Receipt-level extras (Location, ReceiptSeq, RefundInfo for refunds, …)
        // are additive: they pass through but never override a computed canonical
        // key (IsRefund, Items, ReceivedCash/Card, …), so the value-object
        // guarantees cannot be defeated from the extras bag.
        return array_merge($receipt->extra(), $payload);
    }

    /**
     * @param Receipt $receipt
     * @return array
     */
    protected function buildItems(Receipt $receipt)
    {
        $items = [];
        foreach ($receipt->items() as $item) {
            /** @var ReceiptItem $item */
            $line = [
                'Name'        => $item->title(),
                'SPIC'        => $item->mxik(),
                'PackageCode' => $item->packageCode(),
                'GoodPrice'   => $item->price(),                     // unit price, tiyin
                'Price'       => $item->subtotal(),                  // line GROSS = GoodPrice × Amount/1000
                'Amount'      => (int) round($item->count() * 1000), // quantity × 1000
                'VAT'         => $item->vatAmount(),                 // VAT of the net (post-discount) total
                'VATPercent'  => $item->vatPercent(),
                'Discount'    => $item->discount(),                  // tiyin; module subtracts from Price
            ];

            // Per-item extras (CommissionInfo, Other, OwnerType, Barcode, …) pass
            // through, but never override a computed canonical key.
            $items[] = array_merge($item->extra(), $line);
        }

        return $items;
    }

    /**
     * Receipt timestamp in the body, soliq's 'Y-m-d H:i:s' format.
     *
     * @param Receipt $receipt
     * @return string
     */
    protected function time(Receipt $receipt)
    {
        $ms      = $receipt->time();
        $seconds = $ms ? (int) floor($ms / 1000) : time();

        return date('Y-m-d H:i:s', $seconds);
    }

    /**
     * Parse the response into a FiscalResult. A non-2xx status, an explicit
     * success=false flag, or a non-zero status code are treated as a business
     * failure (unsuccessful result), not a throw.
     *
     * @param array $response ['status','body','raw']
     * @return FiscalResult
     */
    protected function parse(array $response)
    {
        $status = isset($response['status']) ? (int) $response['status'] : 0;
        $body   = isset($response['body']) && is_array($response['body']) ? $response['body'] : [];

        $statusCode   = $this->pick($body, ['Code', 'code', 'status_code']);
        $errorCode    = $this->pick($body, ['error_code', 'errorCode']);
        $errorMessage = $this->pick($body, ['Message', 'message', 'error_message', 'error']);

        $explicitFail = (array_key_exists('success', $body) && $body['success'] === false)
            || (array_key_exists('is_success', $body) && $body['is_success'] === false);

        // A status code of numeric 0 means success; any other numeric code fails.
        // A non-numeric status string (e.g. "FAILED") must NOT cast to 0 and slip
        // through as success — only an explicit OK/SUCCESS literal is a pass.
        $codeFail = false;
        if ($statusCode !== null) {
            $codeFail = is_numeric($statusCode)
                ? (int) $statusCode !== 0
                : !in_array(strtoupper((string) $statusCode), ['OK', 'SUCCESS'], true);
        }

        if ($status < 200 || $status >= 300 || $explicitFail || $codeFail || $errorCode) {
            return FiscalResult::failure(
                $errorMessage ? (string) $errorMessage : 'OFD rejected the receipt.',
                $errorCode !== null ? $errorCode : $statusCode,
                $body
            );
        }

        $fiscalSign = $this->pick($body, ['FiscalSign', 'fiscal_sign', 'receipt_gnk_fiscalsign']);
        $terminalId = $this->pick($body, ['TerminalId', 'TerminalID', 'terminal_id', 'receipt_gnk_terminalid']);
        $receiptSeq = $this->pick($body, ['ReceiptSeq', 'receipt_seq', 'receipt_gnk_receiptseq', 'receipt_count']);
        $datetime   = $this->pick($body, ['DateTime', 'datetime', 'receipt_gnk_datetime']);
        $qr         = $this->pick($body, ['QRCodeURL', 'qrCodeURL', 'qr_url', 'qr', 'receipt_gnk_qrcodeurl']);

        if (!$qr && $fiscalSign && $terminalId && $receiptSeq && $datetime) {
            // Build the soliq verification URL from its parts when not supplied.
            $qr = sprintf(
                'https://ofd.soliq.uz/check?t=%s&r=%s&c=%s&s=%s',
                $terminalId,
                $receiptSeq,
                $datetime,
                $fiscalSign
            );
        }

        return FiscalResult::success([
            'receipt_id'  => $this->pick($body, ['ReceiptId', 'receipt_id', 'id']),
            'fiscal_sign' => $fiscalSign,
            'qr'          => $qr,
            'receipt_url' => $this->pick($body, ['receipt_url', 'receiptUrl', 'url']) ?: $qr,
            'terminal_id' => $terminalId,
            'raw'         => $body,
        ]);
    }

    /**
     * First present, non-empty value among $keys, searched at the top level and
     * inside a nested data/result envelope.
     *
     * @param array $body
     * @param array $keys
     * @return mixed|null
     */
    protected function pick(array $body, array $keys)
    {
        $haystacks = [$body];
        foreach (['data', 'result', 'Data', 'Result'] as $envelope) {
            if (isset($body[$envelope]) && is_array($body[$envelope])) {
                $haystacks[] = $body[$envelope];
            }
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
}
