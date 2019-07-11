<?php

namespace My;

class GetFileRandom
{
    /**
     * @var GetContents
     */
    private $getContents;

    public function __construct()
    {
        $this->getContents = new GetContents();
    }

    public function __invoke($params): ?\stdClass
    {
        $url = $params[0];
        $index = $params[1];
        $seconds = rand(1, 5);

        $this->getContents->__invoke($url);
        sleep($seconds);
        return (object) [$index, $url, $seconds];
    }
}