<?php

namespace My;

use Amp\Deferred;
use My\Event\CollectionSubscriber;
use My\Event\DefaultEventDispatcher;
use My\Strategy\ResolutionStrategyFactory;

class DequeueCollectionResolverFactory
{
    /**
     * @var Promises
     */
    private $collection;

    /**
     * @var string
     */
    private $resolutionStrategyClass;

    public function __construct(
        Promises $collection,
        string $resolutionStrategyClass
    ) {
        $this->collection = $collection;
        $this->resolutionStrategyClass = $resolutionStrategyClass;
    }

    public function __invoke(): CollectionResolver
    {
        $eventDispatcher = new DefaultEventDispatcher();
        $deferred = new Deferred();

        (new CollectionSubscriber($eventDispatcher, $this->collection))->__invoke();

        $strategyFactory = new ResolutionStrategyFactory(
            $this->resolutionStrategyClass,
            $deferred,
            $eventDispatcher
        );

        if ($this->collection->count() < 1) {
            throw new \InvalidArgumentException('No pending promises');
        }

        return new StrategyCollectionResolver(
            $this->collection->pending(),
            $strategyFactory->__invoke(),
            $eventDispatcher,
            $deferred
        );
    }
}
