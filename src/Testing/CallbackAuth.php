<?php

namespace Goodoneuz\PayUz\Testing;

/**
 * Test-only switch for the Payme and Uzum merchant callbacks.
 *
 * While skipped:
 *   - Payme and Uzum do not check the HTTP Basic credentials (Uzum still checks
 *     the serviceId);
 *   - the JSON body is read from a `request` form field when one is posted, so a
 *     feature test can send it with `$this->post($uri, ['request' => $json])`.
 *
 * It is off by default and only code can turn it on — it is never read from env
 * or config. It replaces the old `app()->runningUnitTests()` seam: in Laravel that
 * call is just `APP_ENV === 'testing'`, so a deployed app with APP_ENV=testing
 * accepted unauthenticated callbacks.
 *
 * The flag is static, so it outlives the test that set it. Call enforce() in
 * tearDown():
 *
 *   protected function setUp(): void    { parent::setUp(); CallbackAuth::skipForTests(); }
 *   protected function tearDown(): void { CallbackAuth::enforce(); parent::tearDown(); }
 */
final class CallbackAuth
{
    /** @var bool */
    private static $skipped = false;

    public static function skipForTests()
    {
        self::$skipped = true;
    }

    public static function enforce()
    {
        self::$skipped = false;
    }

    /**
     * @return bool
     */
    public static function isSkipped()
    {
        return self::$skipped;
    }
}
