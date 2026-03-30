<?php

declare(strict_types=1);

namespace Horde\EventDispatcher\Test;

use Exception;
use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for exception handling in listeners
 *
 * PSR-14 does not specify exception handling, but it's important
 * to document and test the behavior
 *
 * @author     Ralf Lang <ralf.lang@ralf-lang.de>
 * @license    http://www.horde.org/licenses/bsd BSD-3-Clause
 * @category   Horde
 * @package    EventDispatcher
 * @subpackage UnitTests
 */
#[CoversClass(EventDispatcher::class)]
#[CoversClass(SimpleListenerProvider::class)]
class ExceptionInListenerTest extends TestCase
{
    public function testExceptionInListenerPropagates(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $listenerProvider->addListener(function (SomethingHappened $e): void {
            throw new RuntimeException('Listener failed');
        });

        $dispatcher = new EventDispatcher($listenerProvider);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Listener failed');

        $dispatcher->dispatch($event);
    }

    public function testExceptionPreventsSubsequentListeners(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $callCount = 0;

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$callCount): void {
            $callCount++;
        });

        $listenerProvider->addListener(function (SomethingHappened $e): void {
            throw new RuntimeException('Second listener throws');
        });

        $listenerProvider->addListener(function (SomethingHappened $e) use (&$callCount): void {
            $callCount++; // Should never be called
        });

        $dispatcher = new EventDispatcher($listenerProvider);

        try {
            $dispatcher->dispatch($event);
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            // Expected
            $this->assertSame('Second listener throws', $e->getMessage());
        }

        // Only first listener should have executed
        $this->assertSame(1, $callCount);
    }

    public function testExceptionDoesNotAffectEventObject(): void
    {
        $event = new SomethingHappened();
        $listenerProvider = new SimpleListenerProvider();

        $listenerProvider->addListener(function (SomethingHappened $e): void {
            $e->handled = true; // Modify event before throwing
            throw new RuntimeException('After modifying event');
        });

        $dispatcher = new EventDispatcher($listenerProvider);

        try {
            $dispatcher->dispatch($event);
        } catch (RuntimeException $e) {
            // Expected
        }

        // Event modification should persist even though exception was thrown
        $this->assertTrue($event->handled);
    }
}
