<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 * @license    http://www.horde.org/licenses/bsd BSD-3-Clause
 * @category   Horde
 * @package    EventDispatcher
 * @subpackage UnitTests
 */
#[CoversClass(EventDispatcher::class)]
#[CoversClass(SimpleListenerProvider::class)]
class BasicDispatchTest extends TestCase
{
    public function testDispatcherReturnsEvents(): void
    {
        $matchingEvent = new SomethingHappened();
        $nonmatchingEvent = new SomethingElseHappened();
        $listenerProvider = new SimpleListenerProvider();
        $listenerProvider->addListener(new SomethingHappenedListener());
        $dispatcher = new EventDispatcher($listenerProvider);
        $res = $dispatcher->dispatch($matchingEvent);
        $this->assertInstanceOf(SomethingHappened::class, $res);
        $this->assertTrue($res->handled);
        $res = $dispatcher->dispatch($nonmatchingEvent);
        $this->assertFalse($res->handled);
        $this->assertInstanceOf(SomethingElseHappened::class, $res);
    }

    public function testMatchAnything(): void
    {
        $matchingEvent = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();
        $listenerProvider->addListener(new MatchAnythingListener());
        $dispatcher = new EventDispatcher($listenerProvider);
        $res = $dispatcher->dispatch($matchingEvent);
        $this->assertInstanceOf(SomethingHappened::class, $res);
        $this->assertTrue($res->handled);
    }
}