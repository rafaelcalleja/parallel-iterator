<?php

namespace My\Strategy;

use Amp\Deferred;
use Amp\Promise;
use My\Event\EventDispatcher;
use My\Event\PromiseExceptionEvent;
use My\Event\PromiseSuccessEvent;
use My\Promises;

abstract class ActionDispatcher implements ResolutionActions, ResolutionStrategy
{
    /**
     * @var Deferred
     */
    private $deferred;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(
        Deferred $deferred,
        EventDispatcher $eventDispatcher
    ) {
        $this->deferred = $deferred;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function success(Promise $promise, $result): void
    {
        $this->eventDispatcher->dispatch(
            PromiseSuccessEvent::EVENT_NAME,
            new PromiseSuccessEvent(
                $promise,
                $result
            )
        );

        $this->deferred->resolve($result);
    }

    public function failed(Promise $promise, \Throwable $exception): void
    {
        $this->eventDispatcher->dispatch(
            PromiseExceptionEvent::EVENT_NAME,
            new PromiseExceptionEvent(
                $promise,
                $exception
            )
        );
    }

    public function endWithFailure(\Throwable $exception): void
    {
        $this->deferred->fail($exception);
    }

    abstract function doInvoke(Promise $promise,
                               Promises $promises,
                               &$value,
                               ?\Throwable &$exception): bool;

    public function __invoke(
        Promise $promise,
        Promises $promises,
        $value,
        ?\Throwable $exception
    ): void {
        $invoke = $this->doInvoke($promise, $promises, $value, $exception);

        if (false === $invoke) {
            return;
        }

        if (null === $exception) {
            $this->success($promise, $value);
            return;
        }

        $this->failed(
            $promise,
            $exception
        );

        if ($promises->pending()->count() < 1) {
            $this->endWithFailure($exception);
        }
    }
}
