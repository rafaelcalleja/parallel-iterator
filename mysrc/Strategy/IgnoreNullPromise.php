<?php

namespace My\Strategy;

use Amp\Promise;
use My\Promises;

class IgnoreNullPromise extends ActionDispatcher implements ResolutionStrategy
{
    public function doInvoke(
        Promise $promise,
        Promises $promises,
        & $value,
        ?\Throwable &$exception
    ): bool {
        if (null === $value) {
            $exception = new \RuntimeException('Promise return null');
        }

        return true;
    }
}
