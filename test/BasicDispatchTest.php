<?php
namespace Horde\EventDispatcher\Test;
use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;
use \PHPUnit\Framework\TestCase;

/**
 * @author     Ralf Lang <lang@b1-systems.de>
 * @license    http://www.horde.org/licenses/bsd BSD-3-Clause
 * @category   Horde
 * @package    EventDispatcher
 * @subpackage UnitTests
 */
class BasicDispatchTest extends TestCase
{
    public function testDispatcherReturnsEvents()
    {
        $matchingEvent = new SomethingHappened;
        $nonmatchingEvent = new SomethingElseHappened;
        $listenerProvider = new SimpleListenerProvider();
        $listenerProvider->addListener(new SomethingHappenedListener);
        $dispatcher = new EventDispatcher($listenerProvider);
        $res = $dispatcher->dispatch($matchingEvent);
        $this->assertInstanceOf(SomethingHappened::class, $res);
        $this->assertTrue($res->handled);
        $res = $dispatcher->dispatch($nonmatchingEvent);
        $this->assertFalse($res->handled);
        $this->assertInstanceOf(SomethingElseHappened::class, $res);
     }
}