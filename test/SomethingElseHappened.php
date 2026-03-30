<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

/**
 * SomethingElseHappened Event
 *
 * An event completely unrelated to SomethingHappened
 */
class SomethingElseHappened
{
    public bool $handled = false;
}