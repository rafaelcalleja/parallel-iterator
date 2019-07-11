<?php

namespace My;

use Amp\Promise;

class Promises implements \Countable, \Iterator
{
    /**
     * @var \SplObjectStorage
     */
    protected $promises;

    public function __construct(
        \Iterator $promises
    ) {
        $this->promises = new \SplObjectStorage();
        $this->setPromises(...$promises);
    }

    private function setPromises(Promise ...$promises)
    {
        foreach($promises as $promise) {
            $this->promises->attach($promise, State::pending());
        }
    }

    public function count(): int
    {
        return count($this->promises);
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

    public function success(Promise $promise)
    {
        $this->assertPromiseExists($promise);
        $this->promises->attach(
            $promise,
            [
                'state' => State::resolved()
            ]
        );
    }

    public function failed(Promise $promise, \Throwable $exception)
    {
        $this->assertPromiseExists($promise);
        $this->promises->attach(
            $promise,
            [
                'state' => State::failed(),
                'exception' => $exception,
            ]
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function assertPromiseExists(Promise $promise): void
    {
        if (false === $this->promises->contains($promise)) {
            throw new \RuntimeException('collection doesnt contains that promise');
        }
    }
}
