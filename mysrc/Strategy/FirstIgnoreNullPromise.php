<?php

namespace My\Strategy;

use Amp\Promise;
use My\Promises;

class FirstIgnoreNullPromise extends ActionDispatcher implements ResolutionStrategy
{
    public function doInvoke(
        Promise $promise,
        Promises $promises,
        &$value,
        ?\Throwable &$exception
    ): bool {
        if (null === $value && null === $exception) {
            $exception = new \RuntimeException('Promise return null');
        }

        return $promises->successfully()->count() <= 0;
    }
}
