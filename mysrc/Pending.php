<?php

namespace My;

use Amp\Promise;

class Pending extends Promises
{
    private $resolved;

    private $failed;

    public function __construct(\Iterator $promises)
    {
        parent::__construct($promises);
        $this->resolved = new \SplObjectStorage();
        $this->failed = new \SplObjectStorage();
    }

    public function resolved(Promise $promise)
    {
        if (true === $this->promises->contains($promise)) {
            $this->promises->detach($promise);
            $this->resolved->attach($promise);
        }
    }

    public function failed(Promise $promise)
    {
        if (true === $this->promises->contains($promise)) {
            $this->promises->detach($promise);
            $this->failed->attach($promise);
        }
    }
}
