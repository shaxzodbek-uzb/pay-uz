<?php

namespace Goodoneuz\PayUz\Checkout\Contracts;

use Goodoneuz\PayUz\Checkout\BindingSession;
use Goodoneuz\PayUz\Checkout\BoundCard;
use Goodoneuz\PayUz\Checkout\CardBinding;

/**
 * Saving a card with an acquirer so it can be charged later without the customer
 * re-entering it — the missing half of
 * {@see CheckoutDriver::chargeToken()}, which spends a token but cannot mint one.
 *
 * An optional capability: not every checkout gateway binds cards, so callers
 * check for it rather than assume it.
 *
 *   $driver = Checkout::driver('octo');
 *   if ($driver instanceof CardBinder) { … }
 *
 * The lifecycle is deliberately three steps rather than two, because that is what
 * the acquirers actually do:
 *
 *   1. {@see startBinding()} sends the card and triggers an SMS code.
 *   2. {@see confirmBinding()} submits the code.
 *   3. the gateway calls back with the token, which {@see parseBindCallback()}
 *      reads.
 *
 * Step 3 is separate on purpose. Octo, for instance, verifies the card with a
 * small debit it then reverses, and only afterwards delivers the token on its own
 * request to your `bind_notify_url` — so a card is confirmed before it is
 * chargeable, and the token never appears in the response to step 2.
 *
 * SECURITY: the PAN passes through {@see CardBinding} and must never be
 * persisted. The only storable identifier is {@see BoundCard::token()}.
 */
interface CardBinder
{
    /**
     * Register a card and trigger the confirmation code.
     *
     * @param CardBinding $binding
     * @return BindingSession where the code went and how long it is valid
     *
     * @throws \Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException on a decline
     *                                                               or transport fault
     */
    public function startBinding(CardBinding $binding);

    /**
     * Submit the confirmation code.
     *
     * The returned session reports the gateway's verdict; the token itself arrives
     * later via {@see parseBindCallback()}.
     *
     * @param string $bindingId gateway binding id, i.e. {@see BindingSession::bindingId()}
     * @param int    $verifyId  i.e. {@see BindingSession::verifyId()}
     * @param string $code      the code the customer received
     * @return BindingSession
     *
     * @throws \Goodoneuz\PayUz\Checkout\Exceptions\CheckoutException on a wrong code
     */
    public function confirmBinding($bindingId, $verifyId, $code);

    /**
     * Read the gateway's asynchronous binding callback.
     *
     * Answer that request with HTTP 200 and {@see BoundCard::acknowledgement()} —
     * Octo retries three times and then cancels the token if it never sees one, so
     * an unrecognised callback still has to be acknowledged.
     *
     * @param array $payload raw callback body
     * @return BoundCard
     */
    public function parseBindCallback(array $payload);

    /**
     * Revoke a stored token at the gateway, so deleting a card locally cannot
     * leave a chargeable token behind.
     *
     * @param string $token
     * @return bool
     */
    public function revokeToken($token);

    /**
     * The `method` values this gateway is able to bind (e.g. ['uzcard', 'humo']).
     * A card outside this list should be refused before any money moves.
     *
     * @return array
     */
    public function bindableMethods();
}
