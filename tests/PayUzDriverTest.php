<?php

namespace Goodoneuz\PayUz\Tests;

use Goodoneuz\PayUz\Http\Classes\Click\Click;
use Goodoneuz\PayUz\Http\Classes\Payme\Payme;
use Goodoneuz\PayUz\Models\PaymentSystem;
use Goodoneuz\PayUz\PayUz;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Driver selection.
 *
 * PaymentSystem declares thirteen constants; only five have a gateway behind
 * them. The rest are transaction labels, and asking for one used to leave the
 * driver null and fail later with "Driver not selected" — a message that is
 * wrong twice over, since the caller *did* select one and it names neither the
 * request nor the alternatives.
 */
class PayUzDriverTest extends TestCase
{
    public function test_every_supported_driver_maps_to_a_loadable_class(): void
    {
        // Constructing a gateway reaches the database (PaymentSystemService), so
        // this asserts the mapping rather than instantiating — the mapping is the
        // part that can silently rot.
        foreach (PayUz::supportedDrivers() as $system => $class) {
            $this->assertTrue(class_exists($class), "{$system} maps to a missing class [{$class}]");
        }
    }

    public function test_payme_and_click_resolve_to_their_gateways(): void
    {
        $drivers = PayUz::supportedDrivers();

        $this->assertSame(Payme::class, $drivers[PaymentSystem::PAYME]);
        $this->assertSame(Click::class, $drivers[PaymentSystem::CLICK]);
    }

    /**
     * These constants exist on PaymentSystem and are meaningful as transaction
     * labels, but there is no gateway for them. The failure must say so.
     *
     * @dataProvider systemsWithoutAGateway
     */
    public function test_rejects_a_payment_system_that_has_no_gateway(string $system): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($system);

        (new PayUz)->driver($system);
    }

    public static function systemsWithoutAGateway(): array
    {
        return [
            'oson' => [PaymentSystem::OSON],
            'uzcard' => [PaymentSystem::UZCARD],
            'upay' => [PaymentSystem::UPAY],
            'mbank' => [PaymentSystem::MBANK],
            'visa' => [PaymentSystem::VISA],
            'cash' => [PaymentSystem::CASH],
            'terminal' => [PaymentSystem::TERMINAL],
        ];
    }

    public function test_the_error_lists_what_is_supported(): void
    {
        try {
            (new PayUz)->driver(PaymentSystem::OSON);
            $this->fail('expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            foreach (array_keys(PayUz::supportedDrivers()) as $supported) {
                $this->assertStringContainsString(
                    $supported,
                    $e->getMessage(),
                    "the error should name {$supported} as an alternative"
                );
            }
        }
    }

    public function test_rejects_null_and_nonsense_rather_than_deferring(): void
    {
        foreach ([null, '', 'not-a-system'] as $bad) {
            try {
                (new PayUz)->driver($bad);
                $this->fail('expected an InvalidArgumentException for '.var_export($bad, true));
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Supported:', $e->getMessage());
            }
        }
    }

    /**
     * Guards the gap this test file documents: if a gateway is added for one of
     * the label-only systems, supportedDrivers() must be updated with it, and
     * this list is where that shows up.
     */
    public function test_the_supported_set_is_exactly_the_five_wired_gateways(): void
    {
        $this->assertSame(
            [
                PaymentSystem::PAYME,
                PaymentSystem::CLICK,
                PaymentSystem::PAYNET,
                PaymentSystem::STRIPE,
                PaymentSystem::UZUM,
            ],
            array_keys(PayUz::supportedDrivers())
        );
    }
}
