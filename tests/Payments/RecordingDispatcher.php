<?php

namespace Goodoneuz\PayUz\Tests\Payments;

/**
 * Minimal event dispatcher double — records dispatched events so PaymentService's
 * event emission can be asserted without booting Laravel's event system. Only the
 * dispatch() method PaymentService calls is implemented.
 *
 * Not a *Test.php file, so PHPUnit does not collect it as a test case.
 */
class RecordingDispatcher
{
    /** @var object[] */
    public $events = [];

    public function dispatch($event)
    {
        $this->events[] = $event;
    }

    /**
     * @param string $class
     * @return object[] dispatched events that are instances of $class
     */
    public function ofType($class)
    {
        return array_values(array_filter($this->events, function ($event) use ($class) {
            return $event instanceof $class;
        }));
    }
}
