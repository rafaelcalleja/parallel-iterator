<?php

namespace My;

use Amp\Promise;

class Resolved extends Promises
{
    public function successfully(): Resolved
    {
        return $this;
    }

    public function withFailure(): Failed
    {
        throw new \RuntimeException('Promises contains only successfully promises');
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
