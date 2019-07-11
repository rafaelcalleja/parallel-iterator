<?php

namespace My\Strategy;

use Amp\Deferred;
use Amp\Promise;
use My\Event\EventDispatcher;

class ResolutionActionBuilder
{
    /**
     * @var Deferred
     */
    private $deferred;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public static function create()
    {
        return new self();
    }

    public function __invoke()
    {

    }
}