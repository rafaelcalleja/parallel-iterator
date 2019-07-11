<?php

namespace My;

class DequeueIterator implements \Iterator, \Countable
{
    /**
     * @var \Iterator|\Countable
     */
    private $iterable;

    private $count = 0;

    public function __construct(
        \Iterator $iterable
    ) {
        $this->iterable = $iterable;
        $this->count = $this->iterable->count();
    }

    public function current()
    {
        return $this->iterable->current();
    }

    public function next()
    {
        $this->count--;
        $this->iterable->next();
    }

    public function key()
    {
        return $this->iterable->key();
    }

    public function valid()
    {
        return $this->iterable->valid();
    }

    public function rewind()
    {
    }

    public function count()
    {
        return $this->count;
    }
}
