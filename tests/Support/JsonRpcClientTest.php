<?php

namespace Goodoneuz\PayUz\Tests\Support;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Support\Http\JsonRpcClient;
use Goodoneuz\PayUz\Support\Http\JsonRpcException;
use Goodoneuz\PayUz\Support\Http\TransportException;

/**
 * JSON-RPC client: request envelope, result extraction, and error → exception
 * (including Payme's localised error messages).
 */
class JsonRpcClientTest extends TestCase
{
    /** @test */
    public function it_builds_the_request_envelope_and_returns_the_result()
    {
        $http = (new FakeHttpClient())->queue(['jsonrpc' => '2.0', 'id' => 7, 'result' => ['card' => ['token' => 'tok_1']]]);
        $client = new JsonRpcClient($http);

        $result = $client->call('https://api', 'cards.create', ['save' => true], ['X-Auth' => 'merchant'], 7);

        $this->assertSame('https://api', $http->lastRequest['url']);
        $this->assertSame(7, $http->lastRequest['payload']['id']);
        $this->assertSame('cards.create', $http->lastRequest['payload']['method']);
        $this->assertSame(['save' => true], $http->lastRequest['payload']['params']);
        $this->assertSame('merchant', $http->lastRequest['headers']['X-Auth']);
        $this->assertSame(['card' => ['token' => 'tok_1']], $result);
    }

    /** @test */
    public function empty_params_serialise_as_a_json_object()
    {
        $http = (new FakeHttpClient())->queue(['result' => []]);
        $client = new JsonRpcClient($http);

        $client->call('https://api', 'ping', []);

        // stdClass so json_encode emits {} rather than []
        $this->assertInstanceOf(\stdClass::class, $http->lastRequest['payload']['params']);
    }

    /** @test */
    public function an_error_response_throws_a_json_rpc_exception_with_code_and_data()
    {
        $http = (new FakeHttpClient())->queue([
            'error' => ['code' => -32504, 'message' => 'Insufficient privilege', 'data' => 'X-Auth'],
        ]);
        $client = new JsonRpcClient($http);

        try {
            $client->call('https://api', 'receipts.pay', ['id' => 'r1']);
            $this->fail('Expected JsonRpcException.');
        } catch (JsonRpcException $e) {
            $this->assertSame(-32504, $e->getCode());
            $this->assertSame('Insufficient privilege', $e->getMessage());
            $this->assertSame('X-Auth', $e->data());
        }
    }

    /** @test */
    public function it_collapses_a_localised_error_message_to_a_string()
    {
        $http = (new FakeHttpClient())->queue([
            'error' => ['code' => -31001, 'message' => ['ru' => 'Неверная сумма', 'uz' => 'Notog\'ri', 'en' => 'Invalid amount']],
        ]);
        $client = new JsonRpcClient($http);

        try {
            $client->call('https://api', 'receipts.create', ['amount' => -1]);
            $this->fail('Expected JsonRpcException.');
        } catch (JsonRpcException $e) {
            $this->assertSame('Invalid amount', $e->getMessage()); // prefers English
        }
    }

    /** @test */
    public function a_transport_failure_propagates_as_a_transport_exception()
    {
        $client = new JsonRpcClient(new ThrowingHttpClient());

        $this->expectException(TransportException::class);
        $client->call('https://api', 'cards.create', ['save' => true]);
    }

    /** @test */
    public function a_non_2xx_response_with_no_result_or_error_is_a_transport_failure()
    {
        // e.g. a 502 HTML/empty body — must not be read as an empty success.
        $http = (new FakeHttpClient())->queue([], 502);
        $client = new JsonRpcClient($http);

        $this->expectException(TransportException::class);
        $client->call('https://api', 'receipts.pay', ['id' => 'r1']);
    }
}
