<?php

namespace My\Strategy;

use Amp\Promise;
use My\Promises;

class FacadePromise implements ResolutionStrategy
{
    /**
     * @var ResolutionStrategy
     */
    private $strategy;

    public function __construct(ResolutionStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function success(Promise $promise, $result): void
    {
        $this->strategy->success($promise, $result);
    }

    public function failed(Promise $promise, \Throwable $exception): void
    {
        $this->strategy->failed($promise, $exception);
    }

    public function endWithFailure(\Throwable $exception): void
    {
        $this->strategy->endWithFailure($exception);
    }

    public function __invoke(Promise $promise, Promises $promises, $value, ?\Throwable $exception): void
    {
        $this->strategy->__invoke($promise, $promises, $value, $exception);
    }
}
