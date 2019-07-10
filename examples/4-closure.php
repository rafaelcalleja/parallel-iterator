<?php

require __DIR__ . '/../vendor/autoload.php';

use function Amp\call;
use Amp\MultiReasonException;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;
use function Amp\ParallelFunctions\parallel;
use function Amp\ParallelFunctions\parallelMap;
use Amp\Promise;
use function Amp\Promise\any;
use function Amp\Promise\first;
use function Amp\Promise\wait;

// Parallel function execution is nice, but it's even better being able to use closures instead of having to write a
// function that has to be autoloadable.
$pool = new DefaultPool();

function parallelFirst(array $array, callable $callable, Pool $pool = null): Promise {
    return call(function () use ($array, $callable, $pool) {
        return yield first(\array_map(parallel($callable, $pool), $array));
    });
}
$time_start = microtime(true);


\var_dump(wait(parallelFirst([
    ['https://google.com/', 1],
    ['https://github.com/', 2],
    ['https://stackoverflow.com/', 3],
], function ($params) {
    $url = $params[0];
    $index = $params[1];
    $seconds = rand(1, 5);
    sleep($seconds);
    return (object) [$index, file_get_contents($url), $seconds];
}, $pool)));

$time_end = microtime(true);

$execution_time = ($time_end - $time_start);

echo '<b>Total Execution Time:</b> '.$execution_time.' Mins'. PHP_EOL;
$parrallelIterator = new ParallelIterator(
    [
        ['https://google.com/', 1],
        ['https://github.com/', 2],
        ['https://stackoverflow.com/', 3],
    ], function ($params) {
    $url = $params[0];
    $index = $params[1];
    $seconds = rand(1, 5);
    sleep($seconds);
    return (object) [$index, file_get_contents($url), $seconds];
}
);

class ParallelIterator implements Iterator
{

    /**
     * @var \SplQueue
     */
    private $splQueue;

    public function __construct(
        array $elements,
        callable $callable
    ) {
        $this->splQueue = new \SplQueue();
        $this->splQueue->setIteratorMode(\SplQueue::IT_MODE_FIFO | \SplQueue::IT_MODE_DELETE);

        foreach($elements as $element) {
            $this->splQueue->enqueue($element);
        }
    }

    public function current()
    {
        return $this->splQueue->dequeue();
    }

    public function next()
    {
    }

    public function key()
    {
        return $this->splQueue->key();
    }

    public function valid()
    {
        return false === $this->splQueue->isEmpty();
    }

    public function rewind()
    {
    }

    private function parallelFirst(array $array, callable $callable, Pool $pool = null): Promise {
        return call(function () use ($array, $callable, $pool) {
            return yield first(\array_map(parallel($callable, $pool), $array));
        });
    }
}

class MyQueue
{
    /**
     * @var SplDoublyLinkedList
     */
    private $queue;

    /**
     * @var array
     */
    private $elements;

    public function __construct(array $elements)
    {
        $this->setElements(...$elements);
        $this->queue = new SplDoublyLinkedList();
        $this->queue->setIteratorMode(
            SplDoublyLinkedList::IT_MODE_FIFO |SplDoublyLinkedList::IT_MODE_DELETE
        );

        array_map([$this->queue, 'push'], $elements);
    }

    private function setElements(Serializable... $elements): void
    {
        $this->elements = $elements;
    }
}
