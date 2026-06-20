<?php

namespace Goodoneuz\PayUz\Tests;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Http\Classes\Uzum\Response;
use Goodoneuz\PayUz\Http\Classes\PaymentException;

/**
 * Wire-contract tests for the Uzum Bank Merchant API response envelope.
 *
 * These exercise the protocol surface (status literals, error-code table and the
 * serviceId/timestamp envelope) without needing a database or HTTP layer, so they
 * run in the package's lightweight test harness.
 */
class UzumTest extends TestCase
{
    /** @test */
    public function error_response_has_failed_status_and_error_code()
    {
        $response = new Response();
        $response->setServiceId(123);

        try {
            $response->error(Response::ERROR_INVALID_SERVICE_ID);
            $this->fail('error() must throw PaymentException to short-circuit.');
        } catch (PaymentException $e) {
            $payload = $e->response->response;

            $this->assertSame(123, $payload['serviceId']);
            $this->assertSame(Response::STATUS_FAILED, $payload['status']);
            $this->assertSame(10006, $payload['errorCode']);
            $this->assertArrayHasKey('timestamp', $payload);
        }
    }

    /** @test */
    public function error_can_merge_extra_fields()
    {
        $response = new Response();
        $response->setServiceId(7);

        try {
            $response->error(Response::ERROR_PAYMENT_CANCELLED, 400, ['transId' => 'abc-1']);
            $this->fail('error() must throw.');
        } catch (PaymentException $e) {
            $payload = $e->response->response;
            $this->assertSame('abc-1', $payload['transId']);
            $this->assertSame(10009, $payload['errorCode']);
        }
    }

    /** @test */
    public function success_response_echoes_service_id_and_omits_error_code()
    {
        $response = new Response();
        $response->setServiceId(99);

        try {
            $response->success([
                'transId' => 'tx-1',
                'status'  => Response::STATUS_CONFIRMED,
                'amount'  => 100000,
            ]);
            $this->fail('success() must throw.');
        } catch (PaymentException $e) {
            $payload = $e->response->response;

            $this->assertSame(99, $payload['serviceId']);
            $this->assertSame('tx-1', $payload['transId']);
            $this->assertSame(Response::STATUS_CONFIRMED, $payload['status']);
            $this->assertSame(100000, $payload['amount']);
            $this->assertArrayNotHasKey('errorCode', $payload);
            $this->assertArrayHasKey('timestamp', $payload);
        }
    }

    /** @test */
    public function error_code_table_matches_uzum_specification()
    {
        $this->assertSame(10001, Response::ERROR_AUTH);
        $this->assertSame(10002, Response::ERROR_PARSE_JSON);
        $this->assertSame(10003, Response::ERROR_UNKNOWN_OPERATION);
        $this->assertSame(10005, Response::ERROR_NOT_ENOUGH_PARAMS);
        $this->assertSame(10006, Response::ERROR_INVALID_SERVICE_ID);
        $this->assertSame(10007, Response::ERROR_ALREADY_PROCESSED);
        $this->assertSame(10008, Response::ERROR_TRANSACTION_NOT_FOUND);
        $this->assertSame(10009, Response::ERROR_PAYMENT_CANCELLED);
        $this->assertSame(99999, Response::ERROR_CHECK_PAYMENT_DATA);
    }

    /** @test */
    public function status_literals_match_uzum_specification()
    {
        $this->assertSame('OK', Response::STATUS_OK);
        $this->assertSame('FAILED', Response::STATUS_FAILED);
        $this->assertSame('CREATED', Response::STATUS_CREATED);
        $this->assertSame('CONFIRMED', Response::STATUS_CONFIRMED);
        $this->assertSame('REVERSED', Response::STATUS_REVERSED);
    }
}
