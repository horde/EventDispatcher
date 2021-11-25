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

use Horde\Log\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
     * The logger instance. We prefer the Null logger over null.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ListenerProviderInterface
     */
    private ListenerProviderInterface $listenerProvider;

    /**
     * Constructor
     * 
     * Set up the dispatcher with a listener provider
     * Some implementations use an array of listener providers.
     * This is not necessary as ListenerProviders may be containers of other ListenerProviders
     *
     * @param ListenerProviderInterface $listenerProvider
     * @param LoggerInterface $logger An optional PSR-3 logger
     */
    public function __construct(ListenerProviderInterface $listenerProvider, LoggerInterface $logger = null)
    {
        $this->listenerProvider = $listenerProvider;
        // Prevent having to use if checks
        $this->logger = $logger ?? new NullLogger;
    }

    /**
     * Dispatch an event to all relevant listeners.
     *
     * An event may be any object, no specific markers or base classes needed.
     * Events may form a hierarchy by common interfaces or inheritance.
     *
     * @param object|StoppableEventInterface $event
     *   The object to process.
     *
     * @return object
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(object $event): object
    {
        $log = 'Event {eventType} dispatched to {listenerId}:{listenerType}';
        // When moving to PHP 8, we can use ::class on variables
        $eventType = get_class($event);
        $listeners = $this->listenerProvider->getListenersForEvent($event);

        $isStoppable = $event instanceof StoppableEventInterface;
        foreach ($listeners as $id => $listener) {
            // PHPStan does not get that check
            // @phpstan-ignore-next-line
            if ($isStoppable && $event->isPropagationStopped()) {
                break;
            }
            // The listener may be any callable
            if (is_object($listener)) {
                $listenerType = get_class($listener);
            } else {
                $listenerType = gettype($listener);
            }

            $this->logger->debug(
                $log,
                [
                    'library' => self::class,
                    'eventType' => $eventType,
                    'listenerId' => (string) $id,
                    'listenerType' => $listenerType
                ]                
            );
            $listener($event);
        }
        return $event;
    }
}