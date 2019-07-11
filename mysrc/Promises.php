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
            $this->promises->attach(
                $promise,
                [
                    'state' => State::pending(),
                ]
            );
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

    public static function makeFromArray(array $promises)
    {
        return new static(
            new \ArrayIterator($promises)
        );
    }

    public function success(Promise $promise)
    {
        $this->assertPromisePending($promise);
        $this->promises->detach($promise);
        $this->promises->attach(
            $promise,
            [
                'state' => State::resolved(),
            ]
        );
    }

    public function failed(Promise $promise, \Throwable $exception)
    {
        $this->assertPromisePending($promise);
        $this->promises->detach($promise);
        $this->promises->attach(
            $promise,
            [
                'state' => State::failed(),
                'exception' => $exception,
            ]
        );
    }

    public function successfully(): Resolved
    {
        $success = [];

        foreach($this as $promise) {
            $info = $this->promises->getInfo();

            $state = $info['state'] ?? null;

            if ($state instanceof State
                && $state->equals(State::resolved())
            ) {
                $success[] = $promise;
            }
        }

        return Resolved::makeFromArray($success);
    }

    public function withFailure(): Failed
    {
        $failed = [];

        foreach($this as $promise) {
            $info = $this->promises->getInfo();

            $state = $info['state'] ?? null;

            if ($state instanceof State
                && $state->equals(State::failed())
            ) {
                $failed[] = $promise;
            }
        }

        return Failed::makeFromArray($failed);
    }

    public function pending(): self
    {
        $copy = clone $this;
        $copy->promises = clone $this->promises;

        $successfully = $this->successfully();
        $withFailure = $this->withFailure();

        foreach($this as $promise) {
            if (true === $successfully->promises->contains($promise)) {
                $copy->promises->detach($promise);
            }

            if (true === $withFailure->promises->contains($promise)) {
                $copy->promises->detach($promise);
            }
        }

        return $copy;
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

    /**
     * @throws \RuntimeException
     */
    public function assertPromisePending(Promise $promise): void
    {
        if (false === $this->pending()->promises->contains($promise)) {
            throw new \RuntimeException('Promise already resolved');
        }
    }
}
