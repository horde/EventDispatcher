<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

/**
 * SomethingHappened Event
 *
 * An event without any state or details
 */
class SomethingHappened
{
    public bool $handled = false;
}
