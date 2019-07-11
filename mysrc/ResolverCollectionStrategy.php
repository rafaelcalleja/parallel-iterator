<?php

namespace My;

use Amp\Promise;

interface ResolverCollectionStrategy
{
    public function __invoke(Promises $collection): Promise;
}