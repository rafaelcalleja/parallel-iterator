<?php

namespace My;

use Amp\Deferred;
use Amp\MultiReasonException;
use Amp\Promise;
use My\Event\EventDispatcher;
use My\Event\PromiseSuccessEvent;

class ResolverPromise
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(
        EventDispatcher $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke()
    {

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
    }
}