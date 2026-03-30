# EventDispatcher vs PubSub Comparison

This document compares `horde/eventdispatcher` and `horde/pubsub` to help developers choose the appropriate library for their use case.

## TL;DR - Which Should I Use?

- **Use EventDispatcher** for type-safe, object-based event systems following PSR-14 standards
- **Use PubSub** for simple string-topic-based pub/sub with flexible argument passing

## Overview

Both libraries implement publish-subscribe patterns but with different philosophies and use cases.

| Aspect | EventDispatcher | PubSub |
|--------|----------------|---------|
| **Standard** | PSR-14 compliant | Custom (based on Phly_PubSub) |
| **Event Identity** | PHP object types | String topic names |
| **Type Safety** | Full type hints, automatic matching | Manual, no type checking |
| **Interoperability** | Works with any PSR-14 library | Horde-specific |
| **State Management** | Instance-based | Static OR instance-based |
| **Arguments** | Single event object | Variable arguments |
| **PHP Version** | PHP 8+ | PHP 7.4+ |
| **Architecture** | Modern (2021, PSR-4) | Legacy (2008, PSR-0) |

## Intent & Usage Patterns

### EventDispatcher - Object-Centric Events

**Philosophy:** Events are first-class PHP objects with typed properties and methods.

**Use when:**
- Events have complex state or behavior
- You want type safety and IDE autocomplete
- Multiple parts of the system need to react to domain events
- You're building a modern, testable architecture
- You need PSR-14 interoperability with third-party libraries

**Example:**
```php
use Horde\EventDispatcher\EventDispatcher;
use Horde\EventDispatcher\SimpleListenerProvider;

// Define typed event
class UserRegistered
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public \DateTimeImmutable $timestamp,
    ) {}
}

// Type-safe listener with IDE support
$provider = new SimpleListenerProvider();
$provider->addListener(function (UserRegistered $event): void {
    // $event-> gives autocomplete for userId, email, timestamp
    sendWelcomeEmail($event->email);
});

$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new UserRegistered('123', 'user@example.com', new \DateTimeImmutable()));
```

### PubSub - Topic-Centric Messages

**Philosophy:** String topics with arbitrary arguments, like a message bus.

**Use when:**
- Simple pub/sub with string topics is sufficient
- You have variable or loosely-structured data
- Dynamic topic names at runtime
- Backward compatibility with existing Horde code
- You need global static access (though instance-based is also available)

**Example:**
```php
use Horde_PubSub;

// Subscribe with topic strings
Horde_PubSub::subscribe('user.registered', function ($userId, $email) {
    sendWelcomeEmail($email);
});

// Publish with variable args
Horde_PubSub::publish('user.registered', '123', 'user@example.com');
```

## Implementation Comparison

### Event/Topic Definition

**EventDispatcher:**
```php
// Events are classes
class OrderPlaced
{
    public function __construct(
        public readonly string $orderId,
        public readonly float $amount,
    ) {}
}

// Dispatch strongly-typed object
$dispatcher->dispatch(new OrderPlaced('ORD-123', 99.99));
```

**PubSub:**
```php
// Topics are strings
$topic = 'order.placed';

// Publish with loose arguments
Horde_PubSub::publish('order.placed', 'ORD-123', 99.99);
```

### Listener Registration

**EventDispatcher:**
```php
// Type hint determines what events this receives
$provider->addListener(function (OrderPlaced $event): void {
    // Only called for OrderPlaced events
    processOrder($event->orderId, $event->amount);
});

// No manual topic matching needed
```

**PubSub:**
```php
// Explicit topic string subscription
Horde_PubSub::subscribe('order.placed', function ($orderId, $amount) {
    // Called when 'order.placed' topic published
    processOrder($orderId, $amount);
});

// Must manually track and use correct topic strings
```

### State Management

**EventDispatcher:**
```php
// Instance-based (dependency injection friendly)
class OrderService
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function placeOrder(Order $order): void
    {
        $this->dispatcher->dispatch(new OrderPlaced($order->id));
    }
}
```

**PubSub:**
```php
// Static global access (simple but less testable)
class OrderService
{
    public function placeOrder(Order $order): void
    {
        Horde_PubSub::publish('order.placed', $order->id);
    }
}

// OR instance-based
class OrderService
{
    public function __construct(
        private readonly Horde_PubSub_Provider $pubsub,
    ) {}

    public function placeOrder(Order $order): void
    {
        $this->pubsub->publish('order.placed', $order->id);
    }
}
```

### Unsubscribing

**EventDispatcher:**
```php
// No built-in unsubscribe in SimpleListenerProvider
// Would need custom ListenerProvider implementation
```

**PubSub:**
```php
// Built-in handle-based unsubscribe
$handle = Horde_PubSub::subscribe('topic', $callback);
// ... later ...
Horde_PubSub::unsubscribe($handle);
```

### Stopping Propagation

**EventDispatcher:**
```php
// Implement StoppableEventInterface
use Psr\EventDispatcher\StoppableEventInterface;

class UserRegistered implements StoppableEventInterface
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

// Listener can stop propagation
$provider->addListener(function (UserRegistered $event): void {
    if ($event->email === 'admin@example.com') {
        $event->stopPropagation(); // Remaining listeners won't run
    }
});
```

**PubSub:**
```php
// No propagation control mechanism
// All subscribed handlers always execute
```

## Key Differences

### Type Safety

**EventDispatcher:**
- ✅ Full type hints on event properties and listener parameters
- ✅ IDE autocomplete and refactoring support
- ✅ Type errors caught at development time
- ✅ Automatic listener filtering by type

**PubSub:**
- ❌ No type checking
- ❌ Topic strings prone to typos
- ❌ Arguments are loosely typed
- ⚠️ Runtime errors if arguments mismatch

### Exception Handling

**EventDispatcher:**
```php
try {
    $dispatcher->dispatch($event);
} catch (RuntimeException $e) {
    // Exceptions from listeners propagate
    // Event modifications are preserved
    // Subsequent listeners did not run
}
```

**PubSub:**
```php
try {
    Horde_PubSub::publish('topic', $arg1, $arg2);
} catch (Exception $e) {
    // Exceptions from handlers propagate
    // Subsequent handlers did not run
}
```

Both propagate exceptions; neither provides automatic exception handling.

### Testing & Mocking

**EventDispatcher:**
```php
// Easy to test - inject mock dispatcher
class OrderServiceTest extends TestCase
{
    public function testDispatchesEvent(): void
    {
        $mockDispatcher = $this->createMock(EventDispatcherInterface::class);
        $mockDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(OrderPlaced::class));

        $service = new OrderService($mockDispatcher);
        $service->placeOrder($order);
    }
}
```

**PubSub:**
```php
// Static calls harder to test - requires global state manipulation
class OrderServiceTest extends TestCase
{
    public function testPublishesEvent(): void
    {
        $called = false;
        $handle = Horde_PubSub::subscribe('order.placed', function () use (&$called) {
            $called = true;
        });

        $service = new OrderService();
        $service->placeOrder($order);

        $this->assertTrue($called);
        Horde_PubSub::unsubscribe($handle); // Cleanup
    }
}

// Instance-based PubSub_Provider is more testable
```

### Discovery & Introspection

**EventDispatcher:**
- Event types are discoverable via class hierarchy
- Can use reflection to find all event classes
- Listeners are type-checked at registration

**PubSub:**
- Topics are runtime strings (not discoverable)
- `getTopics()` shows registered topics
- `getSubscribedHandles($topic)` shows handlers for topic
- `clearHandles($topic)` bulk removal support

## Migration Path

### When to Migrate from PubSub to EventDispatcher

Consider migrating when:
- Building new features that fit the event model
- Events have complex state (more than 2-3 arguments)
- You want PSR-14 interoperability
- Type safety and refactoring support are valuable
- Testing and DI are important

### When to Keep PubSub

Keep PubSub when:
- Simple string topics are sufficient
- Legacy code heavily uses it
- Dynamic topic names at runtime
- Need unsubscribe functionality
- Variable argument counts per topic

### Side-by-Side Usage

Both libraries can coexist in the same application:

```php
// EventDispatcher for domain events
$dispatcher->dispatch(new OrderPlaced($orderId));

// PubSub for simple notifications
Horde_PubSub::publish('cache.clear', $cacheKey);
```

## Feature Matrix

| Feature | EventDispatcher | PubSub (Static) | PubSub (Provider) |
|---------|----------------|-----------------|-------------------|
| PSR-14 Standard | ✅ | ❌ | ❌ |
| Type Safety | ✅ | ❌ | ❌ |
| Dependency Injection | ✅ | ❌ | ✅ |
| Unsubscribe Support | ❌* | ✅ | ✅ |
| Stoppable Events | ✅ | ❌ | ❌ |
| PSR-3 Logging | ✅ | ❌ | ❌ |
| IDE Autocomplete | ✅ | ⚠️ | ⚠️ |
| Testing Friendly | ✅ | ⚠️ | ✅ |
| Introspection | ⚠️ | ✅ | ✅ |
| Variable Args | ❌ | ✅ | ✅ |

\* SimpleListenerProvider doesn't support unsubscribe; custom provider could add this

## Code Examples

### Real-World Scenario: User Registration

**EventDispatcher approach:**
```php
// Domain event
class UserRegistered
{
    public function __construct(
        public readonly User $user,
        public readonly \DateTimeImmutable $registeredAt,
        public bool $welcomeEmailSent = false,
        public bool $analyticsTracked = false,
    ) {}
}

// Multiple type-safe listeners
$provider->addListener(function (UserRegistered $event): void {
    sendWelcomeEmail($event->user->email);
    $event->welcomeEmailSent = true;
});

$provider->addListener(function (UserRegistered $event): void {
    trackAnalytics('user.registered', $event->user->id);
    $event->analyticsTracked = true;
});

// Dispatch
$event = new UserRegistered($user, new \DateTimeImmutable());
$result = $dispatcher->dispatch($event);

// Check what happened
assert($result->welcomeEmailSent);
assert($result->analyticsTracked);
```

**PubSub approach:**
```php
// String-based topics
Horde_PubSub::subscribe('user.registered', function ($user, $registeredAt) {
    sendWelcomeEmail($user->email);
});

Horde_PubSub::subscribe('user.registered', function ($user, $registeredAt) {
    trackAnalytics('user.registered', $user->id);
});

// Publish
Horde_PubSub::publish('user.registered', $user, new \DateTimeImmutable());

// No built-in way to track what happened
```

## Migration Status

**PubSub is not officially deprecated**, but new development should consider EventDispatcher when:
- The use case fits the domain event model
- Type safety provides value
- PSR-14 compatibility is desired

Existing PubSub code can remain unchanged. Migration is only recommended when refactoring or when the benefits justify the effort.

## Further Reading

- [PSR-14: Event Dispatcher](https://www.php-fig.org/psr/psr-14/)
- [Phly_PubSub (original inspiration for PubSub)](http://weierophinney.net/matthew/archives/199-A-Simple-PHP-Publish-Subscribe-System.html)
