<?php

namespace My\Event;

use Amp\Promise;

class PromiseSuccessEvent extends PromiseEvent implements Event
{
    public const EVENT_NAME = self::class;

    private $result;

    public function __construct(
        Promise $promise,
        $result
    ) {
        parent::__construct($promise);
        $this->result = $result;
    }

    public function result()
    {
        return $this->result;
    }
}