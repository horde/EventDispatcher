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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
/**
 * The EventDispatcher class implements PSR-14 EventDispatcherInterface
 *
 * Not "final" on purpose. Formally extending is a viable and cheap strategy
 * for DI Autowiring, even though wrapping would be cleaner.
 * 
 * @author    Ralf Lang <lang@b1-systems.de>
 * @category  Horde
 * @copyright 2021 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD-3-Clause
 * @package   EventDispatcher
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var ListenerProviderInterface
     */
    private ListenerProviderInterface $listenerProvider;

    /**
     * @var \Horde_Log_Logger|LoggerInterface
     */
    private ?object $logger;

    /**
     * Constructor
     * 
     * Set up the dispatcher with a listener provider
     * Some implementations use an array of listener providers.
     * This is not necessary as ListenerProviders may be containers of other ListenerProviders
     *
     * @param ListenerProviderInterface $listenerProvider
     * @param \Horde_Log_Logger|LoggerInterface An optional logger TBD
     */
    public function __construct(ListenerProviderInterface $listenerProvider, object $logger = null)
    {
        $this->listenerProvider = $listenerProvider;
        $this->logger = $logger;
    }

    /**
     * Dispatch an event to all relevant listeners.
     * 
     * An event may be any object, no specific markers or base classes needed.
     * Events may form a hierarchy by common interfaces or inheritance.
     * 
     * @param object $event
     *   The object to process.
     *
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event): object
    {
        $listeners = $this->listenerProvider->getListenersForEvent($event);

        $isStoppable = $event instanceof StoppableEventInterface;
        foreach ($listeners as $listener) {
            if ($isStoppable && $event->isPropagationStopped()) {
                break;
            }
            // The listener may be any callable
            $listener($event);
        }
        return $event;
    }
}