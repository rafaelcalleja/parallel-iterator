<?php

namespace My\Event;

use Amp\Promise;

class PromiseExceptionEvent extends PromiseEvent implements Event
{
    public const EVENT_NAME = self::class;

    /**
     * @var \Throwable
     */
    private $exception;

    public function __construct(
        Promise $promise,
        \Throwable $exception
    ) {
        parent::__construct($promise);
        $this->exception = $exception;
    }

    public function exception(): \Throwable
    {
        return $this->exception;
    }
}