<?php

namespace My;

use Amp\Coroutine;
use Amp\Parallel\Sync\SerializationException;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;
use Amp\ParallelFunctions\Internal\SerializedCallableTask;
use Amp\Promise;
use function Amp\Promise\wait;
use My\Strategy\FirstIgnoreNullPromise;
use Opis\Closure\SerializableClosure;

class FirstMapClosure implements \Iterator, \Countable
{
    /**
     * @var \Closure
     */
    private $callback;

    /**
     * @var Promises
     */
    private $collection;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var array
     */
    private $arguments;

    public function __construct(
        callable $callable,
        array $arguments
    ) {
        $this->callable = $callable;
        $this->arguments = $arguments;
        $this->initIterator();
    }

    public function current()
    {
        return wait(
            new Coroutine(
                call_user_func($this->callback)
            )
        );
    }

    public function next()
    {
    }

    public function key()
    {
        return $this->collection->successfully()->count();
    }

    public function valid()
    {
        return $this->collection->pending()->count() > 0;
    }

    public function rewind()
    {
        $this->initIterator();
    }

    public function count()
    {
        return $this->collection->pending()->count();
    }

    private function parallel(callable $callable, Pool $pool): callable {
        if ($callable instanceof \Closure) {
            $callable = new SerializableClosure($callable);
        }

        try {
            $callable = \serialize($callable);
        } catch (\Throwable $e) {
            throw new SerializationException("Unsupported callable: " . $e->getMessage(), 0, $e);
        }

        return function (...$args) use ($pool, $callable): Promise {
            $task = new SerializedCallableTask($callable, $args);
            return $pool->enqueue($task);
        };
    }

    private function initIterator(): void
    {
        $promises = array_map(
            $this->parallel($this->callable, new DefaultPool()),
            $this->arguments
        );

        $this->collection = Promises::makeFromArray(
            $promises
        );

        $factory = new DequeueCollectionResolverFactory(
            $this->collection,
            FirstIgnoreNullPromise::class
        );

        $this->callback = function () use ($factory) {
            return yield $factory->__invoke()->resolve();
        };
    }
}