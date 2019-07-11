<?php

namespace My;

use My\Event\EventDispatcher;
use My\Event\PromiseExceptionEvent;
use My\Event\PromiseSuccessEvent;

class PromiseCollectionResolver
{
    /**
     * @var Promises
     */
    private $collection;

    /**
     * @var ResolverCollectionStrategy
     */
    private $resolverCollectionStrategy;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(
        Promises $collection,
        ResolverCollectionStrategy $resolverCollectionStrategy,
        EventDispatcher $eventDispatcher
    ) {
        $this->collection = $collection;
        $this->resolverCollectionStrategy = $resolverCollectionStrategy;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function resolve(): Promises
    {
        foreach ($this->collection as $key => $promise) {
            $this->eventDispatcher->subscribe(
                PromiseSuccessEvent::EVENT_NAME,
                function(PromiseSuccessEvent $event) {
                    $this->collection->success(
                        $event->promise()
                    );
                }
            );

            $this->eventDispatcher->subscribe(
                PromiseExceptionEvent::EVENT_NAME,
                function(PromiseExceptionEvent $event) {
                    $this->collection->failed(
                        $event->promise(),
                        $event->exception()
                    );
                }
            );
        }
    }

    public function resolved(): Promises
    {

    }
}