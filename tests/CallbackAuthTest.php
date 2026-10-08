<?php

namespace Goodoneuz\PayUz\Tests;

use Goodoneuz\PayUz\Http\Classes\PaymentException;
use Goodoneuz\PayUz\Http\Classes\Payme\Merchant as PaymeMerchant;
use Goodoneuz\PayUz\Http\Classes\Payme\Request as PaymeRequest;
use Goodoneuz\PayUz\Http\Classes\Payme\Response as PaymeResponse;
use Goodoneuz\PayUz\Http\Classes\Uzum\Merchant as UzumMerchant;
use Goodoneuz\PayUz\Http\Classes\Uzum\Request as UzumRequest;
use Goodoneuz\PayUz\Http\Classes\Uzum\Response as UzumResponse;
use Goodoneuz\PayUz\Testing\CallbackAuth;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Basic auth on the Payme and Uzum merchant callbacks.
 *
 * Until 4.1.1 both drivers skipped the check — and read the body from a `request`
 * form field — whenever app()->runningUnitTests() was true. In Laravel that call
 * is `$app['env'] === 'testing'`, so a deployed app with APP_ENV=testing accepted
 * unauthenticated callbacks: anyone could post `request={"method":
 * "PerformTransaction",...}` and mark an order paid. The seam is now an explicit
 * switch that only code can turn on.
 */
class CallbackAuthTest extends TestCase
{
    private const PAYME = ['login' => 'Paycom', 'password' => 'payme-secret'];
    private const UZUM = ['login' => 'uzum', 'password' => 'uzum-secret', 'service_id' => '501'];

    /** @var array */
    private $server;

    /** @var array */
    private $env;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = $_SERVER;
        $this->env = [$_ENV['APP_ENV'] ?? null, getenv('APP_ENV')];

        // Merchant::Authorize() takes credentials from any $_SERVER key that
        // contains AUTHORIZATION; start every test with none.
        foreach (array_keys($_SERVER) as $key) {
            if (strpos($key, 'AUTHORIZATION') !== false) {
                unset($_SERVER[$key]);
            }
        }
    }

    protected function tearDown(): void
    {
        CallbackAuth::enforce();
        Container::setInstance(null);

        $_SERVER = $this->server;
        [$env, $putenv] = $this->env;
        if ($env === null) {
            unset($_ENV['APP_ENV']);
        } else {
            $_ENV['APP_ENV'] = $env;
        }
        putenv($putenv === false ? 'APP_ENV' : 'APP_ENV=' . $putenv);

        parent::tearDown();
    }

    public function test_switch_is_off_by_default(): void
    {
        $this->assertFalse(CallbackAuth::isSkipped());
    }

    public function test_payme_rejects_a_callback_without_credentials(): void
    {
        $this->assertPaymeRejects();
    }

    public function test_payme_rejects_wrong_credentials(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode('Paycom:wrong');

        $this->assertPaymeRejects();
    }

    public function test_payme_accepts_the_configured_credentials(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode('Paycom:payme-secret');

        $this->assertTrue((new PaymeMerchant(self::PAYME, new PaymeResponse()))->Authorize());
    }

    public function test_app_env_testing_alone_does_not_skip_payme_auth(): void
    {
        $this->deployWithAppEnvTesting();

        $this->assertPaymeRejects();
    }

    public function test_app_env_testing_alone_does_not_skip_uzum_auth(): void
    {
        $this->deployWithAppEnvTesting();

        $this->assertUzumRejectsAuth();
    }

    public function test_app_env_testing_alone_does_not_read_the_body_from_a_request_field(): void
    {
        $this->deployWithAppEnvTesting($this->callbackWithBodyAndField());

        $this->assertSame('CheckTransaction', (new PaymeRequest(new PaymeResponse()))->method);
        $this->assertSame('raw-body', (new UzumRequest(new UzumResponse()))->transId);
    }

    public function test_skip_for_tests_skips_payme_auth_until_enforced(): void
    {
        CallbackAuth::skipForTests();
        $this->assertTrue((new PaymeMerchant(self::PAYME, new PaymeResponse()))->Authorize());

        CallbackAuth::enforce();
        $this->assertPaymeRejects();
    }

    public function test_skip_for_tests_skips_uzum_basic_auth_but_not_the_service_id(): void
    {
        CallbackAuth::skipForTests();

        $merchant = new UzumMerchant(self::UZUM, new UzumResponse());
        $this->assertTrue($merchant->Authorize((object) ['serviceId' => 501]));

        try {
            $merchant->Authorize((object) ['serviceId' => 999]);
            $this->fail('A wrong serviceId must be rejected even with auth skipped.');
        } catch (PaymentException $e) {
            $this->assertSame(UzumResponse::ERROR_INVALID_SERVICE_ID, $e->response->response['errorCode']);
        }
    }

    public function test_skip_for_tests_reads_the_body_from_the_request_field(): void
    {
        $this->deployWithAppEnvTesting($this->callbackWithBodyAndField());
        CallbackAuth::skipForTests();

        $this->assertSame('PerformTransaction', (new PaymeRequest(new PaymeResponse()))->method);
        $this->assertSame('form-field', (new UzumRequest(new UzumResponse()))->transId);
    }

    /**
     * send() used to skip header() under runningUnitTests() because PHPUnit has
     * already printed by then and header() would warn. It now asks headers_sent().
     */
    public function test_send_echoes_json_without_warning_once_output_has_started(): void
    {
        foreach ([new PaymeResponse(), (new UzumResponse())->setServiceId(501)] as $response) {
            ob_start();
            try {
                $response->send();
            } finally {
                $output = ob_get_clean();
            }

            $this->assertIsArray(json_decode($output, true));
        }
    }

    /**
     * What a deployed app with APP_ENV=testing looks like: the env var is set and
     * the Laravel application reports runningUnitTests() — the condition the old
     * seam was keyed on.
     */
    private function deployWithAppEnvTesting(?Request $request = null): void
    {
        if (! class_exists(\Illuminate\Foundation\Application::class)) {
            $this->markTestSkipped('Needs illuminate/foundation (laravel/framework) to build an application.');
        }

        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');

        $app = new \Illuminate\Foundation\Application(sys_get_temp_dir());
        $app['env'] = 'testing';
        $app->instance('request', $request ?: Request::create('/handle', 'POST'));

        $this->assertTrue(app()->runningUnitTests(), 'Precondition: the old seam would have been active.');
    }

    /**
     * A callback whose raw body and `request` form field disagree, so the test can
     * tell which one the driver read.
     */
    private function callbackWithBodyAndField(): Request
    {
        $raw = json_encode(['id' => 1, 'method' => 'CheckTransaction', 'params' => [], 'transId' => 'raw-body']);
        $field = json_encode(['id' => 2, 'method' => 'PerformTransaction', 'params' => [], 'transId' => 'form-field']);

        return Request::create('/handle', 'POST', ['request' => $field], [], [], [], $raw);
    }

    private function assertPaymeRejects(): void
    {
        try {
            (new PaymeMerchant(self::PAYME, new PaymeResponse()))->Authorize();
            $this->fail('Payme callback without valid Basic credentials was authorized.');
        } catch (PaymentException $e) {
            $this->assertSame(PaymeResponse::ERROR_INSUFFICIENT_PRIVILEGE, $e->response->response['error']['code']);
        }
    }

    private function assertUzumRejectsAuth(): void
    {
        try {
            (new UzumMerchant(self::UZUM, new UzumResponse()))->Authorize((object) ['serviceId' => 501]);
            $this->fail('Uzum callback without valid Basic credentials was authorized.');
        } catch (PaymentException $e) {
            $this->assertSame(UzumResponse::ERROR_AUTH, $e->response->response['errorCode']);
        }
    }
}
