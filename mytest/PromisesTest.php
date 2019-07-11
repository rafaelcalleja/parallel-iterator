<?php

namespace My\Test;

use Amp\Promise;
use My\Promises;
use PHPUnit\Framework\TestCase;

class PromisesTest extends TestCase
{
    public function testResolvedPromises()
    {
        $futureSuccess = $this->getMockPromise();

        $promises = Promises::makeFromArray(
            [
                $this->getMockPromise(),
                $this->getMockPromise(),
                $futureSuccess
            ]
        );

        $this->assertCount(0,  $promises->successfully());
        $promises->success($futureSuccess);
        $this->assertCount(1, $promises->successfully());

        $this->assertInstanceOf(
            Promises::class,
            $promises->successfully()
        );

        $actual = $promises->successfully()->current();

        $this->assertSame(
            $futureSuccess,
            $actual
        );

        $this->assertCount(2, $promises->pending());
    }

    public function testFailedPromises()
    {
        $futureFailed = $this->getMockPromise();

        $promises = Promises::makeFromArray(
            [
                $this->getMockPromise(),
                $this->getMockPromise(),
                $futureFailed
            ]
        );

        $this->assertCount(0, $promises->withFailure());
        $promises->failed($futureFailed, new \Exception());
        $this->assertCount(1, $promises->withFailure());

        $this->assertInstanceOf(
            Promises::class,
            $promises->successfully()
        );

        $actual = $promises->withFailure()->current();

        $this->assertSame(
            $futureFailed,
            $actual
        );

        $this->assertCount(2, $promises->pending());
    }

    public function testPendingPromises()
    {
        $futureFailed = $this->getMockPromise();
        $futureSuccess = $this->getMockPromise();
        $promises = Promises::makeFromArray(
            [
                $this->getMockPromise(),
                $this->getMockPromise(),
                $futureFailed,
                $futureSuccess
            ]
        );

        $promises->failed($futureFailed, new \Exception());
        $promises->success($futureSuccess);

        $this->assertCount(2, $promises->pending());
        $this->assertCount(4, $promises);
        $this->assertCount(1, $promises->withFailure());
        $this->assertCount(1, $promises->successfully());
    }

    private function getMockPromise()
    {
        return $this->getMockBuilder(
            Promise::class
        )->getMock();
    }
}