<?php

namespace My\Strategy;

use Amp\Promise;
use My\Promises;

interface ResolutionStrategy extends ResolutionActions
{
    public function __invoke(
        Promise $promise,
        Promises $promises,
        $value,
        ?\Throwable $exception): void;
}
