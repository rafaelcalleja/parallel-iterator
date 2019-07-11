<?php

namespace My;

use Amp\Deferred;
use My\Event\DefaultEventDispatcher;
use My\Event\EventDispatcher;
use My\Strategy\ResolutionStrategy;
use My\Strategy\ResolutionStrategyFactory;

class DequeueCollectionResolverBuilder
{
    /**
     * @var Promises
     */
    private $promises;

    /**
     * @var ResolutionStrategy
     */
    private $strategy;

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

    public function withDeferred(Deferred $deferred)
    {
        $this->deferred = $deferred;
    }

    public function withResolutionStrategy(ResolutionStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function withPromises(Promises $promises)
    {
        $this->promises = $promises;
    }

    public function withEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(): CollectionResolver
    {
        $pending = $this->promises->pending();

        if ($pending->count() < 1) {
            throw new \InvalidArgumentException('No pending promises');
        }

        $strategy = new StrategyCollectionResolver(
            $pending,
            $this->strategy,
            $this->eventDispatcher,
            $this->deferred
        );

        return $strategy;
    }
}
