<?php

namespace Goodoneuz\PayUz\Tests\Support;

/**
 * Minimal event dispatcher double — records dispatched events so a manager's
 * event emission can be asserted without booting Laravel's event system. Not a
 * *Test.php file.
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
     * @return object[]
     */
    public function ofType($class)
    {
        return array_values(array_filter($this->events, function ($event) use ($class) {
            return $event instanceof $class;
        }));
    }
}
