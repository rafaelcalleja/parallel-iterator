<?php

namespace My\Event;

interface EventDispatcher
{
    public function dispatch(string $eventName, Event $event): void;

    public function subscribe(string $eventName, callable $listener): void;
}