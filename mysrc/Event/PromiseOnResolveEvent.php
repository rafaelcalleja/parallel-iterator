<?php

namespace My\Event;

use Amp\Promise;
use My\Promises;

class PromiseOnResolveEvent extends PromiseEvent implements Event
{
    public const EVENT_NAME = self::class;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var Promises
     */
    private $promises;

    private $value;

    public function __construct(
        Promise $promise,
        Promises $promises,
        $value,
        ?\Throwable $exception
    ) {
        parent::__construct($promise);
        $this->exception = $exception;
        $this->promise = $promise;
        $this->promises = $promises;
        $this->value = $value;
    }

    public function exception(): \Exception
    {
        return $this->exception;
    }

    public function promises(): Promises
    {
        return $this->promises;
    }

    public function value()
    {
        return $this->value;
    }
}