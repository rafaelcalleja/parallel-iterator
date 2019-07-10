<?php

namespace My;

use Amp\Promise;

class PromiseCollection implements \Countable, \Iterator
{
    /**
     * @var \Iterator
     */
    private $promises;

    /**
     * @var int
     */
    private $count;

    public function __construct(
        \Iterator $promises
    ) {
        $this->setPromises(...$promises);
    }

    private function setPromises(Promise ...$promises)
    {
        array_map(function () {++$this->count; }, $promises);

        $this->promises = $promises;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function current()
    {
        return $this->promises->current();
    }

    public function next()
    {
        $this->promises->next();
    }

    public function key()
    {
        return $this->promises->key();
    }

    public function valid()
    {
        return $this->promises->valid();
    }

    public function rewind()
    {
        $this->promises->rewind();
    }

    public function makeFromArray(array $promises)
    {
        return new self(
            new \ArrayIterator($promises)
        );
    }
}
