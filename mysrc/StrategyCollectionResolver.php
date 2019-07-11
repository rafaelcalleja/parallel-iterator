<?php

namespace My;

use Amp\Deferred;
use Amp\Promise;
use My\Event\CollectionSubscriber;
use My\Event\EventDispatcher;
use My\Event\PromiseExceptionEvent;
use My\Event\PromiseSuccessEvent;
use My\Strategy\ResolutionStrategy;

class StrategyCollectionResolver implements CollectionResolver
{
    /**
     * @var Promises
     */
    private $collection;

    /**
     * @var ResolutionStrategy
     */
    private $resolverCollectionStrategy;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var Deferred
     */
    private $deferred;

    public function __construct(
        Promises $collection,
        ResolutionStrategy $resolverCollectionStrategy,
        EventDispatcher $eventDispatcher,
        Deferred $deferred
    ) {
        $this->collection = $collection;
        $this->resolverCollectionStrategy = $resolverCollectionStrategy;
        $this->eventDispatcher = $eventDispatcher;
        $this->deferred = $deferred;
    }

    public function resolve(): Promise
    {
        (new CollectionSubscriber($this->eventDispatcher, $this->collection))->__invoke();

        foreach ($this->collection as $key => $promise) {
            $promise->onResolve(function ($error, $value) use ($promise) {
                $this->resolverCollectionStrategy->__invoke(
                    $promise,
                    $this->collection,
                    $value,
                    $error
                );
            });
        }

        return $this->deferred->promise();
    }
}
