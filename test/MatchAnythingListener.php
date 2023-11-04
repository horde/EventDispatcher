<?php
declare(strict_types=1);
namespace Horde\EventDispatcher\Test;

class MatchAnythingListener
{
    public function __invoke(object $event)
    {
        $event->handled = true;
        return $event;
    }
}