<?php

namespace My\Event;

use Amp\Promise;

class PromiseEvent implements Event
{
    public const EVENT_NAME = self::class;

    /**
     * @var Promise
     */
    protected $promise;

    public function __construct(Promise $promise)
    {
        $this->promise = $promise;
    }

    public function promise(): Promise
    {
        return $this->promise;
    }
}