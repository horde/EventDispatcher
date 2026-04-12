<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Tests for StoppableEventInterface support (PSR-14 requirement)
 *
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 * @license    http://www.horde.org/licenses/bsd BSD-3-Clause
 * @category   Horde
 * @package    EventDispatcher
 * @subpackage UnitTests
 */
#[CoversClass(EventDispatcher::class)]
#[CoversClass(SimpleListenerProvider::class)]
class StoppableEventTest extends TestCase
{
    public function testStoppableEventStopsPropagation(): void
    {
        $event = new StoppableEvent();
        $listenerProvider = new SimpleListenerProvider();

        // Add three listeners
        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'first';
        });

        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'second';
            $e->stopPropagation(); // Stop here
        });

        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'third'; // Should NOT be called
        });

        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        // Only first two listeners should have been called
        $this->assertCount(2, $result->listenersCalled);
        $this->assertSame(['first', 'second'], $result->listenersCalled);
        $this->assertTrue($result->isPropagationStopped());
    }

    public function testStoppableEventAlreadyStopped(): void
    {
        $event = new StoppableEvent();
        $event->stopPropagation(); // Stop BEFORE dispatch

        $listenerProvider = new SimpleListenerProvider();
        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'listener'; // Should NOT be called
        });

        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        // No listeners should have been called
        $this->assertCount(0, $result->listenersCalled);
        $this->assertTrue($result->isPropagationStopped());
    }

    public function testStoppableEventWithoutStopping(): void
    {
        $event = new StoppableEvent();
        $listenerProvider = new SimpleListenerProvider();

        // Add three listeners that don't stop propagation
        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'first';
        });

        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'second';
        });

        $listenerProvider->addListener(function (StoppableEvent $e): void {
            $e->listenersCalled[] = 'third';
        });

        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        // All listeners should have been called
        $this->assertCount(3, $result->listenersCalled);
        $this->assertSame(['first', 'second', 'third'], $result->listenersCalled);
        $this->assertFalse($result->isPropagationStopped());
    }

    public function testMultipleListenersOnSameEvent(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $callCount = 0;

        // Add multiple listeners for the same event type
        $listenerProvider->addListener(function (SomethingHappened $e) use (&$callCount): void {
            $callCount++;
            $e->handled = true;
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$callCount): void {
            $callCount++;
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$callCount): void {
            $callCount++;
        });

        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        $this->assertSame(3, $callCount);
        $this->assertTrue($result->handled);
    }

    public function testDispatcherWithLogger(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();
        $listenerProvider->addListener(new SomethingHappenedListener());

        $logger = new class extends AbstractLogger {
            public array $logs = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->logs[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };

        $dispatcher = new EventDispatcher($listenerProvider, $logger);

        $dispatcher->dispatch($event);

        $this->assertCount(1, $logger->logs);
        $this->assertSame('debug', $logger->logs[0]['level']);
        $this->assertStringContainsString('SomethingHappened', $logger->logs[0]['context']['eventType']);
        $this->assertSame(EventDispatcher::class, $logger->logs[0]['context']['library']);
    }

    public function testEmptyListenerProvider(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $dispatcher = new EventDispatcher($listenerProvider);
        $result = $dispatcher->dispatch($event);

        // Event should be returned unchanged
        $this->assertSame($event, $result);
        $this->assertFalse($result->handled);
    }
}
