<?php

namespace My\Test;

use Amp\Coroutine;
use Amp\Deferred;
use Amp\Parallel\Worker\DefaultPool;
use function Amp\ParallelFunctions\parallel;
use Amp\Promise;
use function Amp\Promise\wait;
use My\DequeueCollectionResolverFactory;
use My\DequeueIterator;
use My\Event\DefaultEventDispatcher;
use My\FirstMapClosure;
use My\GetFileRandom;
use My\InMemoryIterable;
use My\Strategy\FirstIgnoreNullPromise;
use My\StrategyCollectionResolver;
use My\Promises;
use My\Strategy\FirstPromise;
use MyCoroutine;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    public function testHappyPath()
    {
        $pool = new DefaultPool();

        $elements = [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ];

        $promises = \array_map(parallel([new GetFileRandom(), '__invoke'], $pool), $elements);
        $eventDispatcher = new DefaultEventDispatcher();
        $deferred = new Deferred();

        $resolver = new StrategyCollectionResolver(
            Promises::makeFromArray(
                $promises
            ),
            new FirstPromise(
                $deferred,
                $eventDispatcher
            ),
            $eventDispatcher,
            $deferred
        );

        $callback = function () use($resolver) {
            return yield $resolver->resolve();
        };

        $return = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $return
        );
    }

    /**
     * @expectedException \Amp\Parallel\Worker\TaskException
     */
    public function testErrorPath()
    {
        $pool = new DefaultPool();

        $elements = [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ];

        $callable = function() {
            throw new \InvalidArgumentException('test');
        };
        $promises = \array_map(parallel($callable, $pool), $elements);
        $eventDispatcher = new DefaultEventDispatcher();
        $deferred = new Deferred();

        $resolver = new StrategyCollectionResolver(
            Promises::makeFromArray(
                $promises
            ),
            new FirstPromise(
                $deferred,
                $eventDispatcher
            ),
            $eventDispatcher,
            $deferred
        );

        $callback = function () use($resolver) {
            return yield $resolver->resolve();
        };

        $return = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $return
        );
    }

    public function testIterable()
    {
        $pool = new DefaultPool();

        $elements = [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ];

        $promises = \array_map(parallel([new GetFileRandom(), '__invoke'], $pool), $elements);

        $collection = Promises::makeFromArray(
            $promises
        );

        $factory = new DequeueCollectionResolverFactory(
            $collection,
            FirstIgnoreNullPromise::class
        );

        $callback = function () use($factory) {
            return yield $factory->__invoke()->resolve();
        };

        $this->assertCount(3, $collection->pending());
        $returnA = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $returnA
        );

        $this->assertCount(2, $collection->pending());
        $returnB = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $returnB
        );

        $this->assertNotSame(
            $returnA,
            $returnB
        );

        $this->assertCount(1, $collection->pending());
        $returnC = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $returnC
        );

        $this->assertNotSame(
            $returnA,
            $returnC
        );

        $this->assertNotSame(
            $returnB,
            $returnC
        );

        $this->assertCount(0, $collection->pending());
    }

    public function testIterableLastItemSuccessEitherNull()
    {
        $pool = new DefaultPool();

        $elements = [
            [null],
            [null],
            [null],
            [null],
            [null],
            [['https://google.com/', 1]],
        ];

        $callable = function($params)
        {
            $params = current($params);
            if (null === $params) {
                return null;
            }

            $url = $params[0];
            $index = $params[1];
            file_get_contents($url);
            sleep($index);
            return (object) [$index, $url, $index];
        };

        $promises = \array_map(parallel($callable, $pool), $elements);

        $collection = Promises::makeFromArray(
            $promises
        );

        $factory = new DequeueCollectionResolverFactory(
            $collection,
            FirstIgnoreNullPromise::class
        );

        $callback = function () use($factory) {
            return yield $factory->__invoke()->resolve();
        };

        $this->assertCount(count($elements), $collection->pending());
        $returnA = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $returnA
        );

        $this->assertCount(0, $collection->pending());
    }

    public function testTwoIterationsMixedItemsSuccessEitherNull()
    {
        $pool = new DefaultPool();

        $elements = [
            [['https://google.com/', 1]],
            [null],
            [['https://google.com/', 1]],
            [null],
            [null],
            [['https://google.com/', 1]],
        ];

        $callable = function($params)
        {
            $params = current($params);
            if (null === $params) {
                return null;
            }

            $url = $params[0];
            $index = $params[1];
            file_get_contents($url);
            sleep($index);
            return (object) [$index, $url, $index];
        };

        $promises = \array_map(parallel($callable, $pool), $elements);

        $collection = Promises::makeFromArray(
            $promises
        );

        $factory = new DequeueCollectionResolverFactory(
            $collection,
            FirstIgnoreNullPromise::class
        );

        $callback = function () use($factory) {
            return yield $factory->__invoke()->resolve();
        };

        $this->assertCount(count($elements), $collection->pending());
        $returnA = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $returnA
        );

        $this->assertCount(2, $collection->pending());
        $returnB = wait(new Coroutine($callback()));

        $this->assertInstanceOf(
            \stdClass::class,
            $returnB
        );

        $this->assertCount(1, $collection->pending());
        wait(new Coroutine($callback()));
        $this->assertCount(0, $collection->pending());
    }

    public function testFirstMapClosure()
    {
        $arguments = [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ];

        $iterable = new FirstMapClosure(
            function($params) {
                $url = $params[0];
                $index = $params[1];
                file_get_contents($url);
                sleep($index);
                return [$url, $index];
            },
            $arguments
        );


        $this->assertCount(3, $iterable);

        $this->assertIteration($iterable, $arguments);
        $this->assertIteration($iterable, $arguments);
    }

    private function assertIteration(\Iterator $iterable, array $arguments)
    {
        $actual = [];
        foreach ($iterable as $value) {
            $actual[] = $value;
        }

        sort($arguments);
        sort($actual);
        $this->assertSame(
            $arguments,
            $actual
        );
    }

    public function testFirstMapClosureInMemory()
    {
        $arguments = [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ];

        $iterable = new InMemoryIterable (
            new FirstMapClosure(
                function($params) {
                    $url = $params[0];
                    $index = $params[1];
                    file_get_contents($url);
                    sleep($index);
                    return [$url, $index];
                },
                $arguments
            )
        );


        $this->assertCount(3, $iterable);
        $this->assertIteration($iterable, $arguments);

        $this->assertCount(3, $iterable);
        $this->assertIteration($iterable, $arguments);

        $this->assertCount(3, $iterable);
        $this->assertIteration($iterable, $arguments);

        $this->assertCount(3, $iterable);
        $this->assertIteration($iterable, $arguments);
    }

    public function testSingleRound()
    {
        $elements =  [
            1,2,3,4,5
        ];

        $iterable = new DequeueIterator(
            new \ArrayIterator($elements)
        );

        foreach($iterable as $item) {
        }

        $this->assertCount(0, $iterable);
    }

    public function testSingleContinue()
    {
        $elements =  [
            1,2,3,4,5
        ];

        $iterable = new DequeueIterator(
            new \ArrayIterator($elements)
        );

        $count = 0;
        foreach($iterable as $item) {
            if ($count === 2) {
                continue;
            }

            $count++;
        }

        $this->assertCount(0, $iterable);
    }

    public function testSingleBreak()
    {
        $elements =  [
            1,2,3,4,5
        ];

        $iterable = new DequeueIterator(
            new \ArrayIterator($elements)
        );

        $expectations = [
            0 => 1,
            1 => 2,
        ];

        $count = 0;
        foreach($iterable as $key => $item) {
            if ($count === 2) {
                break;
            }

            $this->assertSame(
                $expectations[$key],
                $item
            );

            $count++;
        }

        $expectedA = 3;
        $expectedB = 4;
        $expectedC = 5;

        $expectations = [
            2 => $expectedA,
            3 => $expectedB,
            4 => $expectedC,
        ];

        $this->assertCount(count($expectations), $iterable);

        foreach($iterable as $key => $item) {
            $this->assertSame(
                $expectations[$key],
                $item
            );
            $count++;
        }

        $this->assertSame(5, $count);
        $this->assertCount(0, $iterable);

        $actual = true;
        foreach($iterable as $item) {
            $actual = false;
        }

        $this->assertTrue($actual);
    }

    public function testDequeueIterableFailed()
    {
        $arguments = [
            ['https://google.com/', 1],
            ['https://github.com/', 2],
            ['https://stackoverflow.com/', 3],
        ];

        $iterable = new DequeueIterator(
            new InMemoryIterable (
                new FirstMapClosure(
                    function($params) {
                        $url = $params[0];
                        $index = $params[1];
                        file_get_contents($url);
                        sleep($index);
                        return [$url, $index];
                    },
                    $arguments
                )
            )
        );

        $this->assertCount(3, $iterable);

        $actual = [];
        foreach ($iterable as $value) {
            $actual[] = $value;
        }

        sort($arguments);
        sort($actual);
        $this->assertSame(
            $arguments,
            $actual
        );

        $this->assertCount(0, $iterable);
    }

}
