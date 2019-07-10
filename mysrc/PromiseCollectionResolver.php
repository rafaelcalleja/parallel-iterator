<?php

namespace My;

use Amp\Deferred;
use Amp\MultiReasonException;
use Amp\Promise;

class PromiseCollectionResolver
{
    /**
     * @var PromiseCollection
     */
    private $collection;

    /**
     * @var ResolverCollectionStrategy
     */
    private $resolverCollectionStrategy;

    public function __construct(
        PromiseCollection $collection,
        ResolverCollectionStrategy $resolverCollectionStrategy
    ) {
        $this->collection = $collection;
        $this->resolverCollectionStrategy = $resolverCollectionStrategy;
    }

    public function resolve(): PromiseCollectionResolved
    {
        $this->resolverCollectionStrategy->__invoke(
            $this->collection
        );
    }

    public function resolved(): PromiseCollection
    {

    }
}