<?php
/**
 * Copyright 2021 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD-3-Clause
 * @package  EventDispatcher
 */
declare(strict_types=1);
namespace Horde\EventDispatcher;
use Fig\EventDispatcher\ParameterDeriverTrait;
use Psr\EventDispatcher\ListenerProviderInterface;
/**
 * Simple ListenerProvider
 *
 * A basic container of Listeners
 * All listeners are returned that match any of the event's interfaces
 *
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2021 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD-3-Clause
 * @package   EventDispatcher
 */
class SimpleListenerProvider implements ListenerProviderInterface
{
    use ParameterDeriverTrait;
    /**
     * @var callable[]
     */
    private array $listeners = [];

    /**
     * Add a listener to the stack of candidates
     *
     * @param callable $listener
     * @return void
     */
    public function addListener(callable $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * Filter the list of relevant listeners for this event
     *
     * @param object $event
     *   An event for which to return the relevant listeners.
     * @return iterable<callable>
     *   An iterable (array, iterator, or generator) of callables.  Each
     *   callable MUST be type-compatible with $event.
     */
    public function getListenersForEvent(object $event) : iterable
    {
        foreach ($this->listeners as $listener) {
            $type = $this->getParameterType($listener);
            if ($type == 'object' or $event instanceof $type) {
                yield $listener;
            }
        }
    }
}