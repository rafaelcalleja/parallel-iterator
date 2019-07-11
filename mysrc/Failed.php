<?php

namespace My;

use Amp\Promise;

class Failed extends Promises
{
    public function successfully(): Resolved
    {
        throw new \RuntimeException('Promises contains only promises with failure');
    }

    public function withFailure(): Failed
    {
        return $this;
    }

    public function success(Promise $promise)
    {
        throw new \RuntimeException('Promises collection already resolved');
    }

    public function failed(Promise $promise, \Throwable $exception)
    {
        throw new \RuntimeException('Promises collection already resolved');
    }
}
