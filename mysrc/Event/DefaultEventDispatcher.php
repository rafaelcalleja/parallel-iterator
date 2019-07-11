<?php

namespace My\Event;

class DefaultEventDispatcher implements EventDispatcher
{
    /**
     * @var callable[]|array
     */
    private $listeners = [];

    public function dispatch(string $eventName, Event $event): void
    {
        if (false === $this->hasListener($eventName)) {
            return;
        }

        foreach ($this->listeners[$eventName] as $key => $listener) {
            call_user_func_array($listener, [$event]);
        }
    }

    public function subscribe(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    private function hasListener(string $eventName): bool
    {
        if (false === array_key_exists($eventName, $this->listeners)) {
            return false;
        }

        return count($this->listeners[$eventName]) > 0;
    }
}