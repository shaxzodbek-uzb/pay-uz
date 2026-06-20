<?php

namespace Goodoneuz\PayUz\Tests;

use PHPUnit\Framework\TestCase;
use Goodoneuz\PayUz\Http\Classes\DataFormat;

/**
 * Regression tests for DataFormat time conversions.
 *
 * `timestamp2datetime()` is called with a numeric Unix timestamp (e.g. Uzcard's
 * create_time) and with a date-time string (Click sign_time, Paynet
 * transactionTime); both must work. Previously a numeric input was passed through
 * strtotime() and collapsed to 1970-01-01.
 */
class DataFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    /** @test */
    public function timestamp2datetime_handles_numeric_unix_seconds()
    {
        $this->assertSame('2024-06-20 18:53:20', DataFormat::timestamp2datetime(1718909600));
    }

    /** @test */
    public function timestamp2datetime_handles_numeric_milliseconds()
    {
        // 13-digit ms is normalised to seconds first, then formatted.
        $this->assertSame('2024-06-20 18:53:20', DataFormat::timestamp2datetime(1718909600000));
    }

    /** @test */
    public function timestamp2datetime_still_handles_datetime_strings()
    {
        // Click sends sign_time as a 'Y-m-d H:i:s' string — must round-trip unchanged.
        $this->assertSame('2019-02-15 14:30:00', DataFormat::timestamp2datetime('2019-02-15 14:30:00'));
    }

    /** @test */
    public function timestamp2datetime_no_longer_returns_epoch_for_numeric_input()
    {
        $this->assertStringStartsNotWith('1970-01-01', DataFormat::timestamp2datetime(1718909600));
    }

    /** @test */
    public function datetime2timestamp_parses_strings_and_passes_through_numbers()
    {
        $this->assertSame(1550241000, DataFormat::datetime2timestamp('2019-02-15 14:30:00'));
        $this->assertSame(1718909600, DataFormat::datetime2timestamp(1718909600));
        // Falsy input is returned unchanged.
        $this->assertSame(0, DataFormat::datetime2timestamp(0));
    }
}
