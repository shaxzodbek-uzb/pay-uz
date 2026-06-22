<?php

namespace Goodoneuz\PayUz\Tests\Fiscalization;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Fiscalization\Receipt;
use Goodoneuz\PayUz\Fiscalization\ReceiptItem;
use Goodoneuz\PayUz\Support\Http\TransportException;
use Goodoneuz\PayUz\Fiscalization\Drivers\OfdDriver;
use Goodoneuz\PayUz\Fiscalization\Exceptions\FiscalizationException;

/**
 * OFD driver: maps a receipt to the soliq fiscal-receipt body, sends it with a
 * bearer token, and parses the fiscal sign / QR across the known response
 * envelopes — all against a fake transport, no network.
 */
class OfdDriverTest extends TestCase
{
    const MXIK = '00702001001000001';

    private function config()
    {
        return ['endpoint' => 'https://ofd.example/receipt', 'token' => 'secret-token', 'terminal_id' => 'UZ000000000042'];
    }

    private function saleReceipt()
    {
        return Receipt::sale('order-7', [
            new ReceiptItem('Pro plan', self::MXIK, 12000000, 2, 12, '1495762', 0),
        ])->payByCard();
    }

    /** @test */
    public function missing_credentials_throw()
    {
        $driver = new OfdDriver(['endpoint' => '', 'token' => ''], new FakeHttpClient());

        $this->expectException(FiscalizationException::class);
        $driver->fiscalize($this->saleReceipt());
    }

    /** @test */
    public function it_posts_a_soliq_shaped_receipt_with_bearer_auth()
    {
        $http = (new FakeHttpClient())->queue(['Code' => 0, 'data' => [
            'FiscalSign' => '123456789012',
            'QRCodeURL'  => 'https://ofd.soliq.uz/check?t=UZ&r=1&c=20260620120000&s=123456789012',
            'ReceiptId'  => 'r-9',
        ]]);

        $driver = new OfdDriver($this->config(), $http);
        $result = $driver->fiscalize($this->saleReceipt());

        // endpoint + auth
        $this->assertSame('https://ofd.example/receipt', $http->lastRequest['url']);
        $this->assertSame('Bearer secret-token', $http->lastRequest['headers']['Authorization']);

        // receipt-level mapping
        $payload = $http->lastRequest['payload'];
        $this->assertSame(0, $payload['IsRefund']);
        $this->assertSame('order-7', $payload['OrderId']);
        $this->assertSame('UZ000000000042', $payload['TerminalId']);
        $this->assertSame(0, $payload['ReceivedCash']);
        $this->assertSame(24000000, $payload['ReceivedCard']);

        // line mapping: GoodPrice = unit, Price = line total, Amount = qty × 1000
        $line = $payload['Items'][0];
        $this->assertSame('Pro plan', $line['Name']);
        $this->assertSame(self::MXIK, $line['SPIC']);
        $this->assertSame('1495762', $line['PackageCode']);
        $this->assertSame(12000000, $line['GoodPrice']);
        $this->assertSame(24000000, $line['Price']);
        $this->assertSame(2000, $line['Amount']);
        $this->assertSame(12, $line['VATPercent']);
        $this->assertSame(2571429, $line['VAT']); // 24_000_000 × 12/112 -> 2_571_429

        // parsed result (from the `data` envelope)
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('123456789012', $result->fiscalSign());
        $this->assertSame('r-9', $result->receiptId());
    }

    /** @test */
    public function it_builds_the_qr_url_from_parts_when_not_supplied()
    {
        // Older Multikassa-style flat `receipt_gnk_*` response without a QR URL.
        $http = (new FakeHttpClient())->queue([
            'receipt_gnk_fiscalsign'  => '999988887777',
            'receipt_gnk_terminalid'  => 'UZ000000000042',
            'receipt_gnk_receiptseq'  => 128,
            'receipt_gnk_datetime'    => '20260620120000',
        ]);

        $result = (new OfdDriver($this->config(), $http))->fiscalize($this->saleReceipt());

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('999988887777', $result->fiscalSign());
        $this->assertSame(
            'https://ofd.soliq.uz/check?t=UZ000000000042&r=128&c=20260620120000&s=999988887777',
            $result->qr()
        );
    }

    /** @test */
    public function it_sends_gross_price_with_a_separate_discount_keeping_the_line_consistent()
    {
        // unit 10_000 × qty 3 = 30_000 gross; 5_000 discount => 25_000 net.
        $http = (new FakeHttpClient())->queue(['Code' => 0, 'FiscalSign' => '123456789012']);
        $driver = new OfdDriver($this->config(), $http);

        $driver->fiscalize(Receipt::sale('order-d', [
            new ReceiptItem('Discounted', self::MXIK, 10000, 3, 12, null, 5000),
        ])->payByCard());

        $payload = $http->lastRequest['payload'];
        $line    = $payload['Items'][0];

        // The soliq invariant Price == GoodPrice × Amount / 1000 must hold, with
        // the discount carried separately (not already subtracted from Price).
        $this->assertSame(30000, $line['Price']);
        $this->assertSame(5000, $line['Discount']);
        $this->assertSame((int) ($line['GoodPrice'] * $line['Amount'] / 1000), $line['Price']);
        // VAT is extracted from the net (post-discount) amount: 25_000 × 12/112 -> 2_679.
        $this->assertSame(2679, $line['VAT']);
        // Receipt payment is the net total.
        $this->assertSame(25000, $payload['ReceivedCard']);
        $this->assertSame(0, $payload['ReceivedCash']);
    }

    /** @test */
    public function a_zero_vat_line_emits_zero_vat()
    {
        $http = (new FakeHttpClient())->queue(['Code' => 0, 'FiscalSign' => '123456789012']);
        $driver = new OfdDriver($this->config(), $http);

        $driver->fiscalize(Receipt::sale('order-z', [
            new ReceiptItem('Exempt', self::MXIK, 5000000, 1, 0),
        ])->payByCard());

        $line = $http->lastRequest['payload']['Items'][0];
        $this->assertSame(0, $line['VATPercent']);
        $this->assertSame(0, $line['VAT']);
    }

    /** @test */
    public function extras_cannot_override_computed_canonical_keys()
    {
        $http = (new FakeHttpClient())->queue(['Code' => 0, 'FiscalSign' => '123456789012']);
        $driver = new OfdDriver($this->config(), $http);

        $receipt = Receipt::sale('order-x', [
            // a hostile/buggy item extra trying to rewrite the VAT
            new ReceiptItem('Pro plan', self::MXIK, 12000000, 1, 12, null, 0, ['VAT' => 999]),
        ])->payByCard()->with(['IsRefund' => 1, 'ReceivedCard' => 0]); // try to flip to refund / zero the split

        $driver->fiscalize($receipt);
        $payload = $http->lastRequest['payload'];

        $this->assertSame(0, $payload['IsRefund']);            // canonical sale flag wins
        $this->assertSame(12000000, $payload['ReceivedCard']); // computed split wins
        $this->assertSame(1285714, $payload['Items'][0]['VAT']); // computed VAT wins over the extra
    }

    /** @test */
    public function a_refund_is_marked_and_succeeds_with_the_right_split()
    {
        $http = (new FakeHttpClient())->queue(['Code' => 0, 'FiscalSign' => '111122223333']);
        $driver = new OfdDriver($this->config(), $http);

        $result = $driver->fiscalize(Receipt::refund('order-7', [
            new ReceiptItem('Pro plan', self::MXIK, 12000000, 1),
        ])->payByCard());

        $payload = $http->lastRequest['payload'];
        $this->assertSame(1, $payload['IsRefund']);
        $this->assertSame(0, $payload['ReceivedCash']);
        $this->assertSame(12000000, $payload['ReceivedCard']);
        $this->assertTrue($result->isSuccessful());
        $this->assertSame('111122223333', $result->fiscalSign());
    }

    /** @test */
    public function a_textual_failure_status_is_not_treated_as_success()
    {
        // (int) "FAILED" === 0 must NOT slip through as success.
        $http = (new FakeHttpClient())->queue(['Code' => 'FAILED', 'Message' => 'rejected'], 200);
        $result = (new OfdDriver($this->config(), $http))->fiscalize($this->saleReceipt());

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('rejected', $result->errorMessage());
    }

    /** @test */
    public function a_textual_ok_status_is_treated_as_success()
    {
        $http = (new FakeHttpClient())->queue(['Code' => 'OK', 'FiscalSign' => '123456789012']);
        $result = (new OfdDriver($this->config(), $http))->fiscalize($this->saleReceipt());

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('123456789012', $result->fiscalSign());
    }

    /** @test */
    public function a_transport_fault_propagates_from_the_driver()
    {
        $driver = new OfdDriver($this->config(), new ThrowingHttpClient());

        $this->expectException(TransportException::class);
        $driver->fiscalize($this->saleReceipt());
    }

    /** @test */
    public function a_nonzero_status_code_becomes_an_unsuccessful_result()
    {
        $http = (new FakeHttpClient())->queue(['Code' => 21, 'Message' => 'invalid SPIC'], 200);
        $result = (new OfdDriver($this->config(), $http))->fiscalize($this->saleReceipt());

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(21, $result->errorCode());
        $this->assertSame('invalid SPIC', $result->errorMessage());
    }

    /** @test */
    public function a_non_2xx_status_becomes_an_unsuccessful_result()
    {
        $http = (new FakeHttpClient())->queue(['message' => 'unauthorized'], 401);
        $result = (new OfdDriver($this->config(), $http))->fiscalize($this->saleReceipt());

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('unauthorized', $result->errorMessage());
    }
}
