<?php

namespace My;

class InMemoryIterable implements \Iterator, \Countable
{
    /**
     * @var FirstMapClosure
     */
    private $iterable;

    private $index = 0;

    /**
     * @var array
     */
    private $elements;

    /**
     * @var bool
     */
    private $rewinded = false;

    public function __construct(
        FirstMapClosure $iterable
    ) {
        $this->iterable = $iterable;
    }

    public function current()
    {
        if (true === $this->rewinded) {
            return $this->elements[$this->index];
        }

        return $this->elements[$this->iterable->key()] = $this->iterable->current();
    }

    public function next()
    {
        if (true === $this->rewinded) {
            $this->index++;
        }
    }

    public function key()
    {
        if (true === $this->rewinded) {
            return $this->index;
        }

        return $this->iterable->key();;
    }

    public function valid()
    {
        if (true === $this->rewinded) {
            return isset($this->elements[$this->index]);
        }

        return $this->iterable->valid();
    }

    public function rewind()
    {
        if ($this->iterable->count() === 0 && false === $this->iterable->valid()) {
            $this->index = 0;
            $this->rewinded = true;
        }
    }

    public function count()
    {
        if (true === $this->rewinded || ($this->iterable->count() === 0 && count($this->elements) > 0)) {
            return count($this->elements);
        }

        return $this->iterable->count();
    }
}
