<?php
namespace Horde\EventDispatcher\Test;
use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;
use \PHPUnit\Framework\TestCase;

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 */
class BasicDispatchTest extends TestCase
{
    public function testOnlyMatchingDispatcher()
    {
        $matchingEvent = new SomethingHappened;
        $listenerProvider = new SimpleListenerProvider();
        $listenerProvider->addListener(new SomethingHappenedListener);
        $dispatcher = new EventDispatcher($listenerProvider);
        $res = $dispatcher->dispatch($matchingEvent);
        $this->assertInstanceOf(SomethingHappened::class, $res);
    }

}