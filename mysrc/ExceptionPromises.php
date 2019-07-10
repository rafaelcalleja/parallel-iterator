<?php

namespace My;

use Amp\Promise;

interface ExceptionPromises extends \Countable
{
    public function add(Promise $promise, \Throwable $error): void;

    public function promises(): PromiseCollection;
}