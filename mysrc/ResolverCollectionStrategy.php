<?php

namespace My;

use Amp\Promise;

interface ResolverCollectionStrategy
{
    public function __invoke(PromiseCollection $collection): Promise;
}