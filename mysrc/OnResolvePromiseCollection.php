<?php

namespace My;

use Amp\Deferred;
use Amp\MultiReasonException;
use Amp\Promise;

class OnResolvePromiseCollection implements OnResolvePromise
{
    /**
     * @var Deferred
     */
    private $deferred;

    /**
     * @var PendingPromises
     */
    private $pendingPromises;

    /**
     * @var Promise
     */
    private $resolvingPromise;

    /**
     * @var ExceptionPromises
     */
    private $exceptionPromises;

    public function __construct(
        Deferred $deferred,
        PendingPromises $pendingPromises,
        Promise $resolvingPromise,
        ExceptionPromises $exceptionPromises
    ) {
        $this->deferred = $deferred;
        $this->pendingPromises = $pendingPromises;
        $this->resolvingPromise = $resolvingPromise;
        $this->exceptionPromises = $exceptionPromises;
    }

    public function __invoke(...$arguments): callable
    {
        return function ($error, $value) use ($arguments) {
            if ( $this->pendingPromises->count() === 0) {
                return;
            }

            if (!$error) {
                $this->pendingPromises->clear();
                $this->deferred->resolve($value);
                $this->deferred = null;
                $this->pendingPromises->complete(
                    $this->resolvingPromise
                );
                return;
            }

            $this->exceptionPromises->add(
                $this->resolvingPromise,
                $error
            );

            if (1 === $this->pendingPromises->count()) {
                $this->deferred->fail(
                    new MultiReasonException(
                        iterator_to_array($this->exceptionPromises->promises())
                    )
                );
            }
        };
    }
}