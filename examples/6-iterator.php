<?php

require __DIR__ . '/../vendor/autoload.php';

use Amp\Coroutine;
use Amp\Deferred;
use Amp\Failure;
use function Amp\Internal\createTypeError;
use function Amp\Internal\formatStacktrace;
use Amp\Internal\ResolutionQueue;
use Amp\InvalidYieldError;
use Amp\Loop;
use Amp\MultiReasonException;
use Amp\Parallel\Worker\Pool;
use Amp\Promise;
use function Amp\call;
use function Amp\ParallelFunctions\parallel;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\adapt;
use function Amp\Promise\all;
use function Amp\Promise\wait;
use React\Promise\PromiseInterface as ReactPromise;

use Amp\Internal\Placeholder;
use Amp\Internal\PrivatePromise;

trait MyPlaceholder
{
    /** @var bool */
    private $resolved = false;

    /** @var mixed */
    private $result;

    /** @var callable|ResolutionQueue|null */
    private $onResolved;

    /** @var null|array */
    private $resolutionTrace;

    /**
     * @inheritdoc
     */
    public function onResolve(callable $onResolved)
    {
        if ($this->resolved) {
            if ($this->result instanceof Promise) {
                $this->result->onResolve($onResolved);
                return;
            }

            try {
                $result = $onResolved(null, $this->result);

                if ($result === null) {
                    return;
                }

                if ($result instanceof \Generator) {
                    $result = new Coroutine($result);
                }

                if ($result instanceof Promise || $result instanceof ReactPromise) {
                    Promise\rethrow($result);
                }
            } catch (\Throwable $exception) {
                Loop::defer(static function () use ($exception) {
                    throw $exception;
                });
            }
            return;
        }

        if (null === $this->onResolved) {
            $this->onResolved = $onResolved;
            return;
        }

        if (!$this->onResolved instanceof ResolutionQueue) {
            $this->onResolved = new ResolutionQueue($this->onResolved);
        }

        $this->onResolved->push($onResolved);
    }

    public function repeat()
    {
        $this->resolved = false;
    }

    /**
     * @param mixed $value
     *
     * @throws \Error Thrown if the promise has already been resolved.
     */
    private function resolve($value = null)
    {
        if ($this->resolved) {
            $message = "Promise has already been resolved";

            if (isset($this->resolutionTrace)) {
                $trace = formatStacktrace($this->resolutionTrace);
                $message .= ". Previous resolution trace:\n\n{$trace}\n\n";
            } else {
                // @codeCoverageIgnoreStart
                $message .= ", define environment variable AMP_DEBUG or const AMP_DEBUG = true and enable assertions "
                    . "for a stacktrace of the previous resolution.";
                // @codeCoverageIgnoreEnd
            }

            throw new \Error($message);
        }

        \assert((function () {
            $env = \getenv("AMP_DEBUG") ?: "0";
            if (($env !== "0" && $env !== "false") || (\defined("AMP_DEBUG") && \AMP_DEBUG)) {
                $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
                \array_shift($trace); // remove current closure
                $this->resolutionTrace = $trace;
            }

            return true;
        })());

        if ($value instanceof ReactPromise) {
            $value = Promise\adapt($value);
        }

        $this->resolved = true;
        $this->result = $value;

        if ($this->onResolved === null) {
            return;
        }

        $onResolved = $this->onResolved;
        $this->onResolved = null;

        if ($this->result instanceof Promise) {
            $this->result->onResolve($onResolved);
            return;
        }

        try {
            $result = $onResolved(null, $this->result);
            $onResolved = null; // allow garbage collection of $onResolved, to catch any exceptions from destructors

            if ($result === null) {
                return;
            }

            if ($result instanceof \Generator) {
                $result = new Coroutine($result);
            }

            if ($result instanceof Promise || $result instanceof ReactPromise) {
                Promise\rethrow($result);
            }
        } catch (\Throwable $exception) {
            Loop::defer(static function () use ($exception) {
                throw $exception;
            });
        }
    }

    /**
     * @param \Throwable $reason Failure reason.
     */
    private function fail(\Throwable $reason)
    {
        $this->resolve(new Failure($reason));
    }

    public function __destruct()
    {
        try {
            $this->result = null;
        } catch (\Throwable $e) {
            Loop::defer(static function () use ($e) {
                throw $e;
            });
        }
    }
}

/**
 * Deferred is a container for a promise that is resolved using the resolve() and fail() methods of this object.
 * The contained promise may be accessed using the promise() method. This object should not be part of a public
 * API, but used internally to create and resolve a promise.
 */
final class MyDeferred
{
    /** @var object Has public resolve and fail methods. */
    private $resolver;

    /** @var \Amp\Promise Hides placeholder methods */
    private $promise;

    public function __construct()
    {
        $this->resolver = new class implements Promise
        {
            use MyPlaceholder {
                resolve as public;
                fail as public;
            }
        };

        $this->promise = new PrivatePromise($this->resolver);
    }

    /**
     * @return \Amp\Promise
     */
    public function promise(): Promise
    {
        return $this->promise;
    }

    /**
     * Fulfill the promise with the given value.
     * @param mixed $value
     */
    public function resolve($value = null)
    {
        $this->resolver->resolve($value);
    }

    public function repeat()
    {
        $this->resolver->repeat();
    }
    /**
     * Fails the promise the the given reason.
     * @param \Throwable $reason
     */
    public function fail(\Throwable $reason)
    {
        $this->resolver->fail($reason);
    }
}

class MyCoroutine implements Promise
{
    use MyPlaceholder;

    /**
     * Attempts to transform the non-promise yielded from the generator into a promise, otherwise returns an instance
     * `Amp\Failure` failed with an instance of `Amp\InvalidYieldError`.
     *
     * @param mixed      $yielded Non-promise yielded from generator.
     * @param \Generator $generator No type for performance, we already know the type.
     *
     * @return Promise
     */
    private static function transform($yielded, $generator): Promise
    {
        try {
            if (\is_array($yielded)) {
                return Promise\all($yielded);
            }

            if ($yielded instanceof ReactPromise) {
                return Promise\adapt($yielded);
            }

            // No match, continue to returning Failure below.
        } catch (\Throwable $exception) {
            // Conversion to promise failed, fall-through to returning Failure below.
        }

        return new Failure(new InvalidYieldError(
            $generator,
            \sprintf(
                "Unexpected yield; Expected an instance of %s or %s or an array of such instances",
                Promise::class,
                ReactPromise::class
            ),
            $exception ?? null
        ));
    }

    /**
     * @param \Generator $generator
     */
    public function __construct(\Generator $generator)
    {
        try {
            $yielded = $generator->current();

            if (!$yielded instanceof Promise) {
                if (!$generator->valid()) {
                    $this->resolve($generator->getReturn());
                    return;
                }

                $yielded = self::transform($yielded, $generator);
            }
        } catch (\Throwable $exception) {
            $this->fail($exception);
            return;
        }

        /**
         * @param \Throwable|null $e Exception to be thrown into the generator.
         * @param mixed           $v Value to be sent into the generator.
         */
        $onResolve = function ($e, $v) use ($generator, &$onResolve) {
            /** @var bool Used to control iterative coroutine continuation. */
            static $immediate = true;

            /** @var \Throwable|null Promise failure reason when executing next coroutine step, null at all other times. */
            static $exception;

            /** @var mixed Promise success value when executing next coroutine step, null at all other times. */
            static $value;

            $exception = $e;
            $value = $v;

            if (!$immediate) {
                $immediate = true;
                return;
            }

            try {
                try {
                    do {
                        if ($exception) {
                            // Throw exception at current execution point.
                            $yielded = $generator->throw($exception);
                        } else {
                            // Send the new value and execute to next yield statement.
                            $yielded = $generator->send($value);
                        }

                        if (!$yielded instanceof Promise) {
                            if (!$generator->valid()) {
                                $this->resolve($generator->getReturn());
                                $onResolve = null;
                                return;
                            }

                            $yielded = self::transform($yielded, $generator);
                        }

                        $immediate = false;
                        $yielded->onResolve($onResolve);
                    } while ($immediate);

                    $immediate = true;
                } catch (\Throwable $exception) {
                    $this->fail($exception);
                    $onResolve = null;
                } finally {
                    $exception = null;
                    $value = null;
                }
            } catch (\Throwable $e) {
                Loop::defer(static function () use ($e) {
                    throw $e;
                });
            }
        };

        try {
            $yielded->onResolve($onResolve);

            unset($generator, $yielded, $onResolve);
        } catch (\Throwable $e) {
            Loop::defer(static function () use ($e) {
                throw $e;
            });
        }
    }
}



class ParallelIterator implements Iterator, Countable
{
    private $index;

    /**
     * @var array
     */
    private $elements;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var \SplDoublyLinkedList
     */
    private $queue;

    /**
     * @var Pool|null
     */
    private $pool;

    public function __construct(
        array $elements,
        callable $callable,
        ?Pool $pool = null
    ) {
        $this->queue = new \SplDoublyLinkedList();
        $this->queue->setIteratorMode(
            SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP
        );

        $this->elements = $elements;
        $this->callable = $callable;
        $this->pool = $pool;
        $this->index = 0;
        $this->createPromises();
    }

    public function current()
    {
        $callback = function () {
            return yield $this->first();
        };

        return wait(new MyCoroutine($callback()));
    }

    public function next()
    {
        ++$this->index;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return $this->index < count($this->elements);
    }

    public function rewind()
    {
    }

    private function first(): Promise
    {
        if (true === $this->queue->isEmpty()) {
            throw new \Error("No promises provided");
        }

        $deferred = new Deferred();
        $result = $deferred->promise();

        $pending = $this->queue->count();
        $exceptions = [];

        foreach ($this->queue as $key => $promise) {
            $exceptions[$key] = null; // add entry to array to preserve order
            $promise->onResolve(function ($error, $value) use (&$deferred, &$exceptions, &$pending, $key) {
                if ($pending === 0) {
                    return;
                }

                if (!$error) {
                    $pending = 0;
                    $deferred->resolve($value);
                    $deferred = null;
                    $this->queue->offsetUnset($key);
                    return;
                }

                $exceptions[$key] = $error;
                if (0 === --$pending) {
                    $deferred->fail(new MultiReasonException($exceptions));
                }
            });
        }

        return $result;
    }

    private function createPromises()
    {
       $promises = \array_map(parallel($this->callable, $this->pool), $this->elements);
       foreach($promises as $key => $promise)
       {
           $this->queue->add($key, $promise);
       }
    }

    public function count()
    {
        return $this->queue->count();
    }
}

(static function () : void {
    $time_start = microtime(true);

    $iterator = new ParallelIterator(
        [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ], function ($params) {
        $url = $params[0];
        $index = $params[1];
        $seconds = rand(1, 5);
        file_get_contents($url);
        sleep($seconds);
        return (object) [$index, $url, $seconds];
    }, new \Amp\Parallel\Worker\DefaultPool()
    );
    var_dump(count($iterator));

    foreach($iterator as $element) {
        var_dump($element);
        echo '<b>Total Execution Time:</b> '.(microtime(true) - $time_start).' Mins'. PHP_EOL;
    }

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    echo '<b>Total Execution Time:</b> '.$execution_time.' Mins'. PHP_EOL;
})();
