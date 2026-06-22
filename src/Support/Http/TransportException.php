<?php

namespace Goodoneuz\PayUz\Support\Http;

/**
 * Thrown by an {@see HttpClient} when a request fails at the transport level —
 * no response was received (DNS failure, connection refused, timeout, missing
 * cURL extension). HTTP error statuses (4xx/5xx) are NOT transport failures: the
 * client returns them and each integration decides how to interpret them.
 */
class TransportException extends \RuntimeException
{
}
