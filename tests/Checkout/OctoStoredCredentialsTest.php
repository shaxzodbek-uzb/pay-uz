<?php

namespace Goodoneuz\PayUz\Tests\Checkout;

use Goodoneuz\PayUz\Checkout\CheckoutManager;
use Goodoneuz\PayUz\Checkout\Drivers\OctoDriver;
use Goodoneuz\PayUz\Checkout\Payment;
use Goodoneuz\PayUz\Tests\Support\FakeHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * A CheckoutManager with the control-panel lookup stubbed, so the merge rules can
 * be asserted without a database.
 */
class StubbedParamsManager extends CheckoutManager
{
    /** @var array */
    public $stored = [];

    protected function storedParams($name)
    {
        // Mirrors the real method's contract: blank values never reach the driver.
        return array_filter($this->stored, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}

/**
 * Octo credentials come from `payment_system_params` — the same control-panel table
 * Payme, Click and Uzum read from — with the config file as a fallback.
 */
class OctoStoredCredentialsTest extends TestCase
{
    private function manager(FakeHttpClient $http, array $config, array $stored)
    {
        // notify_url is mandatory on Octo's side, so every driver config carries one.
        $config = array_merge(['notify_url' => 'https://app/webhook'], $config);

        $manager = new StubbedParamsManager(['default' => 'octo', 'drivers' => ['octo' => $config]], $http);
        $manager->stored = $stored;

        return $manager;
    }

    /** @test */
    public function control_panel_credentials_win_over_the_config_file()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1']]);

        $manager = $this->manager(
            $http,
            ['shop_id' => 1, 'secret' => 'from-config', 'language' => 'ru'],
            ['shop_id' => '42528', 'secret' => 'from-panel'],
        );

        $manager->pay(Payment::make(100000, 'order-1'));

        $body = $http->lastRequest['payload'];

        $this->assertSame(42528, $body['octo_shop_id']);
        $this->assertSame('from-panel', $body['octo_secret']);
        // Untouched by the panel, so the config file still supplies it.
        $this->assertSame('ru', $body['language']);
    }

    /** @test */
    public function a_blank_panel_field_does_not_erase_a_configured_value()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1']]);

        $manager = $this->manager(
            $http,
            ['shop_id' => 42528, 'secret' => 'from-config'],
            ['shop_id' => '', 'secret' => null],
        );

        $manager->pay(Payment::make(100000, 'order-1'));

        $this->assertSame(42528, $http->lastRequest['payload']['octo_shop_id']);
        $this->assertSame('from-config', $http->lastRequest['payload']['octo_secret']);
    }

    /**
     * A panel field is text, and `(bool) "false"` is true in PHP — the exact way a
     * live shop ends up silently in test mode.
     *
     * @test
     * @dataProvider flagValues
     */
    public function the_test_flag_survives_being_stored_as_text($stored, $expected)
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1']]);

        $manager = $this->manager($http, ['shop_id' => 1, 'secret' => 's'], ['test' => $stored]);
        $manager->pay(Payment::make(100000, 'order-1'));

        $this->assertSame($expected, $http->lastRequest['payload']['test']);
    }

    public function flagValues()
    {
        return [
            'string one'   => ['1', true],
            'string zero'  => ['0', false],
            'string true'  => ['true', true],
            'string false' => ['false', false],
            'real boolean' => [true, true],
        ];
    }

    /** @test */
    public function bindable_schemes_can_be_stored_as_a_comma_separated_field()
    {
        $manager = $this->manager(new FakeHttpClient(), ['shop_id' => 1, 'secret' => 's'], [
            'bindable_methods' => 'uzcard, humo , bank_card',
        ]);

        $driver = $manager->driver('octo');

        $this->assertInstanceOf(OctoDriver::class, $driver);
        $this->assertSame(['uzcard', 'humo', 'bank_card'], $driver->bindableMethods());
    }

    /** @test */
    public function without_stored_params_the_driver_still_runs_on_the_config_file()
    {
        $http = (new FakeHttpClient())->queue(['error' => 0, 'data' => ['octo_payment_UUID' => 'U1']]);

        $manager = $this->manager($http, ['shop_id' => 7, 'secret' => 'only-config'], []);
        $manager->pay(Payment::make(100000, 'order-1'));

        $this->assertSame(7, $http->lastRequest['payload']['octo_shop_id']);
    }
}
