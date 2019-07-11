<?php

namespace My;

use Amp\Deferred;
use Amp\MultiReasonException;
use Amp\Promise;

interface OnResolvePromise
{
    public function __invoke($error, $value);
}