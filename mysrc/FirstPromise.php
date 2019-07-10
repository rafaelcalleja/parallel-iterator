<?php

namespace My;

use Amp\Deferred;
use Amp\MultiReasonException;
use Amp\Promise;

class FirstPromise implements ResolverCollectionStrategy
{
    /**
     * @throws MultiReasonException
     * @throws \Error
     */
    public function __invoke(PromiseCollection $collection): Promise
    {
        if (1 < $collection->count()) {
            throw new \Error("No promises provided");
        }

        $deferred = new Deferred();
        $result = $deferred->promise();

        $pending = $collection->count();
        $exceptions = [];

        foreach ($collection as $key => $promise) {
            $exceptions[$key] = null; // add entry to array to preserve order
            $promise->onResolve(function ($error, $value) use (&$deferred, &$exceptions, &$pending, $key) {
                if ($pending === 0) {
                    return;
                }

                if (!$error) {
                    $pending = 0;
                    $deferred->resolve($value);
                    $deferred = null;
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
}