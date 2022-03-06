<?php

require __DIR__ . '/../vendor/autoload.php';

#[Attribute]
class ListensTo
{
    public string $event;

    public function __construct(string $event)
    {
        $this->event = $event;
    }
}

class ProductCreated {}

class ProductDeleted {}

class ProductSubscriber
{
    #[ListensTo(ProductCreated::class)]
    public function onProductCreated(ProductCreated $event)
    { /* … */
    }

    #[ListensTo(ProductDeleted::class)]
    public function onProductDeleted(ProductDeleted $event)
    { /* … */
    }
}

class EventServiceProvider
{
    // In real life scenarios, 
    //  we'd automatically resolve and cache all subscribers
    //  instead of using a manual array.
    private array $subscribers = [
        ProductSubscriber::class,
    ];

    private array $listeners = [];

    public function register(): void
    {
        // The event dispatcher is resolved from the container
        // $eventDispatcher = $this->app->make(EventDispatcher::class);

        foreach ($this->subscribers as $subscriber) {
            // 0: $subscriber = 'ProductSubscriber';
            // We'll resolve all listeners registered 
            //  in the subscriber class,
            //  and add them to the dispatcher.
            foreach ($this->resolveListeners($subscriber)
                as [$event, $listener]) {
                $this->listeners[$event] = $listener;
                // $eventDispatcher->listen($event, $listener);
            }
            pre($this->listeners);
        }
    }

    private function resolveListeners(string $subscriberClass): array
    {
        // $subscriberClass = 'ProductSubscriber';
        $reflectionClass = new ReflectionClass($subscriberClass);
        // $reflectionClass = ProductSubscriber::object
        $listeners = [];

        foreach ($reflectionClass->getMethods() as $method) {
            // 0: $method = 'onProductCreated'
            // 1: $method = 'onProductDeleted'
            $attributes = $method->getAttributes(ListensTo::class);
            foreach ($attributes as $attribute) {
                // $listener = ListenTo::object
                // ListenTo::$event = (string) $method
                $listener = $attribute->newInstance();
                $listeners[] = [
                    // The event that's configured on the attribute
                    // 0: $listener->event = 'ProductCreated'
                    // 1: $listener->event = 'ProductDeleted'
                    $listener->event,

                    // The listener for this event 
                    // $subscriberClass = 'ProductSubscriber'
                    // 0: $method->getName() = 'onProductCreated'
                    // 1: $method->getName() = 'onProductDeleted'
                    [$subscriberClass, $method->getName()],
                ];
            }
        }
        return $listeners;
    }
}

$esp = new EventServiceProvider;
$esp->register();
