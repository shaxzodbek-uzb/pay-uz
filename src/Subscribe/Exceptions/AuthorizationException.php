<?php

namespace Goodoneuz\PayUz\Subscribe\Exceptions;

/**
 * Wrong or insufficient X-Auth (gateway code -32504) — e.g. the cards-only {id} header used on a server-side receipts.* / cards.check / cards.remove call.
 */
class AuthorizationException extends SubscribeException
{
}
