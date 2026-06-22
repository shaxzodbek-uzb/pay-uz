<?php

namespace Goodoneuz\PayUz\Subscribe\Events;

use Goodoneuz\PayUz\Subscribe\Card;

/**
 * Fired after a card token is OTP-verified (and becomes chargeable). Listen to
 * persist the token against your customer. Plain public properties so the event
 * works outside a full framework boot.
 */
class CardVerified
{
    /** @var Card */
    public $card;

    /** @var string driver name */
    public $driver;

    public function __construct(Card $card, $driver)
    {
        $this->card   = $card;
        $this->driver = (string) $driver;
    }
}
