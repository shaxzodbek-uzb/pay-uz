<?php

namespace Goodoneuz\PayUz\Fiscalization\Exceptions;

/**
 * Base exception for the fiscalization layer (transport faults, misconfiguration
 * and unknown drivers). Business rejections by the OFD are reported as an
 * unsuccessful {@see \Goodoneuz\PayUz\Fiscalization\FiscalResult}, not as throws.
 */
class FiscalizationException extends \RuntimeException
{
}
