<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for listener execution order and callable types
 *
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 * @license    http://www.horde.org/licenses/bsd BSD-3-Clause
 * @category   Horde
 * @package    EventDispatcher
 * @subpackage UnitTests
 */
#[CoversClass(EventDispatcher::class)]
#[CoversClass(SimpleListenerProvider::class)]
class ListenerOrderTest extends TestCase
{
    public function testListenersExecuteInRegistrationOrder(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();
        $executionOrder = [];

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$executionOrder): void {
            $executionOrder[] = 'first';
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$executionOrder): void {
            $executionOrder[] = 'second';
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$executionOrder): void {
            $executionOrder[] = 'third';
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$executionOrder): void {
            $executionOrder[] = 'fourth';
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$executionOrder): void {
            $executionOrder[] = 'fifth';
        });

        $dispatcher = new EventDispatcher($listenerProvider);
        $dispatcher->dispatch($event);

        $this->assertSame(['first', 'second', 'third', 'fourth', 'fifth'], $executionOrder);
    }

    public function testCallableClosureListener(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();
        $called = false;

        $listener = function (SomethingHappened $e) use (&$called): void {
            $called = true;
            $e->handled = true;
        };

        $listenerProvider->addListener($listener);
        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($called);
        $this->assertTrue($result->handled);
    }

    public function testCallableInvokableObjectListener(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $listenerProvider->addListener(new SomethingHappenedListener());
        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($result->handled);
    }

    public function testCallableArrayCallableListener(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $listenerObject = new class {
            public function handle(SomethingHappened $event): void
            {
                $event->handled = true;
            }
        };

        $listenerProvider->addListener([$listenerObject, 'handle']);
        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($result->handled);
    }

    public function testCallableStaticMethodListener(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $listenerProvider->addListener([StaticListenerClass::class, 'handle']);
        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($result->handled);
    }

    public function testCallableStringCallableListener(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $listenerProvider->addListener(StaticListenerClass::class . '::handleStatic');
        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($result->handled);
    }

    public function testMixedCallableTypes(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();
        $executionOrder = [];

        // Closure
        $listenerProvider->addListener(function (SomethingHappened $e) use (&$executionOrder): void {
            $executionOrder[] = 'closure';
        });

        // Invokable object
        $listenerProvider->addListener(new class {
            public function __invoke(SomethingHappened $event): void
            {
                // Access outer scope via reference won't work, so we set event property
                $event->handled = true;
            }
        });

        // Array callable
        $listenerProvider->addListener([StaticListenerClass::class, 'recordCall']);

        // Static string callable
        $listenerProvider->addListener(StaticListenerClass::class . '::recordCall');

        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertCount(1, $executionOrder);
        $this->assertTrue($result->handled);
        $this->assertSame(2, StaticListenerClass::$callCount);
    }
}

class StaticListenerClass
{
    public static int $callCount = 0;

    public static function handle(SomethingHappened $event): void
    {
        $event->handled = true;
    }

    public static function handleStatic(SomethingHappened $event): void
    {
        $event->handled = true;
    }

    public static function recordCall(SomethingHappened $event): void
    {
        self::$callCount++;
    }
}
