<?php

namespace My\Event;

use Amp\Promise;

class PromiseExceptionEvent extends PromiseEvent implements Event
{
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(
        Promise $promise,
        \Exception $exception
    ) {
        parent::__construct($promise);
        $this->exception = $exception;
    }

    public function exception(): \Exception
    {
        return $this->exception;
    }
}