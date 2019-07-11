<?php

namespace My;

use Amp\Promise;

interface PendingPromises extends \Countable, \Iterator
{
    public function clear(): void;

    public function complete(Promise $promise): void;

    public function promises(): Promises;
}