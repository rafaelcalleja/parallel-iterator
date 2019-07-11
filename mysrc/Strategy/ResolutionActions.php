<?php

namespace My\Strategy;

use Amp\Promise;

interface ResolutionActions
{
    public function success(Promise $promise, $result): void;

    public function failed(Promise $promise, \Throwable $exception): void;

    public function endWithFailure(\Throwable $exception): void;
}