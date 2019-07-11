<?php

namespace My\Strategy;

use Amp\Promise;
use My\Promises;

class FirstPromise extends ActionDispatcher implements ResolutionStrategy
{
    public function doInvoke(
        Promise $promise,
        Promises $promises,
        &$value,
        ?\Throwable &$exception
    ): bool {
        return $promises->successfully()->count() <= 0;
    }
}
