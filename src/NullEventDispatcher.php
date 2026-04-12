<?php

declare(strict_types=1);

/**
 * Copyright 2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD-3-Clause
 * @package  EventDispatcher
 */

namespace Horde\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Null event dispatcher that silently accepts and returns events unchanged.
 *
 * Useful as a default when no event handling is needed. Analogous to
 * NullLogger (PSR-3) and Horde\Cache\NullStorage.
 *
 * @author    Ralf Lang <ralf.lang@ralf-lang.de>
 * @category  Horde
 * @copyright 2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD-3-Clause
 * @package   EventDispatcher
 */
final class NullEventDispatcher implements EventDispatcherInterface
{
    /**
     * Accept an event and return it unchanged.
     *
     * No listeners are called.
     *
     * @param object $event The event to dispatch.
     * @return object The unchanged event.
     */
    public function dispatch(object $event): object
    {
        return $event;
    }
}
