<?php

namespace My;

use Amp\Deferred;
use Amp\MultiReasonException;
use Amp\Promise;

interface OnResolvePromise
{
    public function __invoke(...$arguments): callable;

    public function promises(): PromiseCollection;
}