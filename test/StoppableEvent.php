<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Test event that implements StoppableEventInterface
 */
class StoppableEvent implements StoppableEventInterface
{
    public bool $propagationStopped = false;
    public array $listenersCalled = [];

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
