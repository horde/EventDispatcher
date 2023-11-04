<?php
declare(strict_types=1);
namespace Horde\EventDispatcher\Test;

class SomethingHappenedListener
{
    public function __invoke(SomethingHappened $event)
    {
        $event->handled = true;
        return $event;
    }
}