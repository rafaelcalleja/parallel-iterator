<?php

namespace My\Event;

use My\Promises;

class CollectionSubscriber
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var Promises
     */
    private $collection;

    public function __construct(
        EventDispatcher $eventDispatcher,
        Promises $collection
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->collection = $collection;
    }

    public function __invoke()
    {
        $this->eventDispatcher->subscribe(
            PromiseSuccessEvent::EVENT_NAME,
            function (PromiseSuccessEvent $event) {
                $this->collection->success(
                    $event->promise()
                );
            }
        );

        $this->eventDispatcher->subscribe(
            PromiseExceptionEvent::EVENT_NAME,
            function (PromiseExceptionEvent $event) {
                $this->collection->failed(
                    $event->promise(),
                    $event->exception()
                );
            }
        );
    }
}