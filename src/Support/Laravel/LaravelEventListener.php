<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Support\Laravel;

use Illuminate\Support\Facades\Event;

/**
 * Register callbacks for Laravel events. This class acts as a proxy event-listener for Laravel events.
 *
 * It registers itself as a listener for given events (on behalf of the caller), and when the events occur, it calls
 * the caller's callbacks for them.
 *
 * The caller can remove the listeners for a given event, or all events for a given owner.
 */
final class LaravelEventListener
{
    /** @var string[] The events being listened for. */
    private array $listeningForEvents = [];

    /** @var array<string,array<string,callable[]>> The callbacks for the events, keyed by owner. */
    private array $callbacks = [];





    /**
     * Register a callback for a Laravel event.
     *
     * @param string       $owner      The owner of the listeners to register (so they can be removed later).
     * @param class-string $eventClass The event to listen for.
     * @param callable     $callback   The callback to call when the event occurs.
     * @return void
     */
    public static function registerListener(string $owner, string $eventClass, callable $callback): void
    {
        $instance = self::getLaravelScopedSelfInstance();

        $instance->listenForEventType($eventClass);

        $instance->addListener($owner, $eventClass, $callback);
    }

    /**
     * Remove the listeners for all events, for a given owner.
     *
     * @param string      $owner      The owner of the listeners to remove.
     * @param string|null $eventClass The event class to remove the listeners for (optional).
     * @return void
     */
    public static function removeListeners(string $owner, ?string $eventClass = null): void
    {
        $instance = self::getLaravelScopedSelfInstance();

        if ($eventClass !== null) {
            unset($instance->callbacks[$owner][$eventClass]);
        } else {
            unset($instance->callbacks[$owner]);
        }
    }





    /**
     * Get the scoped instance.
     *
     * @return self
     */
    private static function getLaravelScopedSelfInstance(): self
    {
        self::registerLaravelScopedSelfInstance();

        return \app(self::class);
    }

    /**
     * Register the scoped instance.
     *
     * Doesn't register it again if it's already registered.
     *
     * @return void
     */
    private static function registerLaravelScopedSelfInstance(): void
    {
        // only register if it's not already registered
        if (\app()->bound(self::class)) {
            return;
        }

        \app()->scoped(self::class, fn() => new self());
    }





    /**
     * Get Laravel to call handleEvent() when events of a certain type occur.
     *
     * @param class-string $eventClass The event to listen for.
     * @return void
     */
    private function listenForEventType(string $eventClass): void
    {
        if (\in_array($eventClass, $this->listeningForEvents, true)) {
            return;
        }

        $handleEvent = fn(object $event) => $this->handleEvent($eventClass, $event);

        Event::listen($eventClass, $handleEvent);

        $this->listeningForEvents[] = $eventClass;
    }

    /**
     * Add a callback for a given event.
     *
     * @param string       $owner      The owner of the listeners to register (so they can be removed later).
     * @param class-string $eventClass The event to listen for.
     * @param callable     $callback   The callback to call when the event occurs.
     * @return void
     */
    private function addListener(string $owner, string $eventClass, callable $callback): void
    {
        $this->callbacks[$owner][$eventClass][] = $callback;
    }





    /**
     * Call the callbacks for a given event.
     *
     * @param class-string $eventClass The type of event that was triggered.
     * @param object       $event      The event that was triggered.
     * @return void
     */
    private function handleEvent(string $eventClass, object $event): void
    {
        foreach ($this->callbacks as $callbacks) {
            foreach ($callbacks[$eventClass] ?? [] as $callback) {
                $callback($event);
            }
        }
    }
}
