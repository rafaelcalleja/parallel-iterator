<?php

namespace My;

use Doctrine\Common\Annotations\Annotation;

class GetContents
{
    public function __invoke($url)
    {
        (new Annotation([]));
        return file_get_contents($url);
    }
}