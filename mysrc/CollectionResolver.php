<?php

namespace My;

use Amp\Promise;

interface CollectionResolver
{
    public function resolve(): Promise;
}
