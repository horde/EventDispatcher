# EventDispatcher

![Unit Tests](https://github.com/horde/eventdispatcher/actions/workflows/ci.yml/badge.svg)

A simple PSR-14 EventDispatcher / ListenerProvider system for Horde

This library contains a simple PSR-14 EventDispatcher and one simple ListenerProvider using functionality from fig/eventdispatcher-util.
It is similar in purpose to horde/pubsub but shares no code.

## Features

- **PSR-14 compliant** - Full implementation of PSR-14 Event Dispatcher
- **StoppableEventInterface support** - Events can halt propagation mid-chain
- **PSR-3 logging integration** - Optional debug logging of event dispatch
- **Type-safe listener matching** - Automatic filtering based on event type hints
- **Multiple callable types** - Closures, invokable objects, array callables, static methods

## Installation

```bash
composer require horde/eventdispatcher
```

## Basic Usage

```php
use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;

// Create a listener provider and add listeners
$provider = new SimpleListenerProvider();
$provider->addListener(function (MyEvent $event): void {
    // Handle the event
    $event->process();
});

// Create dispatcher
$dispatcher = new EventDispatcher($provider);

// Dispatch events
$event = new MyEvent();
$result = $dispatcher->dispatch($event);
```

## Exception Handling

**Important:** Exceptions thrown by listeners propagate directly to the caller of `dispatch()`.

The EventDispatcher does **not** catch or suppress exceptions from listeners. If a listener throws an exception:

1. The exception propagates immediately to the `dispatch()` caller
2. Subsequent listeners in the chain **are not executed**
3. Event modifications made before the exception **are preserved**
4. The partially-processed event object remains in its modified state

It is the job of the listener to

- harden against expected problems
- catch expected exceptions
- emit logs or CouldNotHandle events
- track any relevant markers in the event object

if that is appropriate for the handler, event type and circumstances.
Otherwise the originator of the event must handle or whatever is in layers beyond it.

### Example

```php
$provider = new SimpleListenerProvider();

$provider->addListener(function (MyEvent $event): void {
    $event->step1Complete = true; // This runs
});

$provider->addListener(function (MyEvent $event): void {
    throw new RuntimeException('Processing failed'); // Exception thrown here
});

$provider->addListener(function (MyEvent $event): void {
    $event->step3Complete = true; // This NEVER runs
});

$dispatcher = new EventDispatcher($provider);

try {
    $event = new MyEvent();
    $dispatcher->dispatch($event);
} catch (RuntimeException $e) {
    // Handle the exception
    // $event->step1Complete is true (modifications preserved)
    // $event->step3Complete is undefined (listener never ran)
}
```

### Best Practices

**For application code:**
- Wrap `dispatch()` calls in try-catch blocks if listener failures should be handled gracefully
- Consider using a wrapper dispatcher that catches and logs exceptions if you need resilient event handling
- Use StoppableEventInterface for controlled propagation stopping (doesn't throw)

**For listener code:**
- Only throw exceptions for truly exceptional conditions
- Consider catching and logging errors within the listener if the event chain should continue
- Document any exceptions your listener may throw

## StoppableEventInterface

Events implementing `StoppableEventInterface` can halt propagation:

```php
use Psr\EventDispatcher\StoppableEventInterface;

class MyStoppableEvent implements StoppableEventInterface
{
    private bool $stopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }
}

// In a listener
$listener = function (MyStoppableEvent $event): void {
    if ($event->shouldStop()) {
        $event->stopPropagation(); // Remaining listeners won't be called
    }
};
```

## Logging

Pass a PSR-3 logger to enable debug logging:

```php
use Psr\Log\Logger;

$logger = new Logger();
$dispatcher = new EventDispatcher($provider, $logger);

// Logs each listener execution at DEBUG level
$dispatcher->dispatch($event);
```

## License

BSD-3-Clause
