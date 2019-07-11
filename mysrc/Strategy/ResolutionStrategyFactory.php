<?php

namespace My\Strategy;

class ResolutionStrategyFactory
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $arguments;

    public function __construct(string $class, ...$arguments)
    {
        $this->class = $class;
        $this->arguments = $arguments;
    }

    public function __invoke()
    {
        return new $this->class(...$this->arguments);
    }
}
