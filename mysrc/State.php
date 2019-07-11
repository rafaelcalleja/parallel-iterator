<?php

namespace My;

class State
{
    private static $STATES = [
        0  => 'pending',
        1  => 'resolved',
        2  => 'failed'
    ];

    /**
     * @var int
     */
    private $state;

    public function __construct(int $state)
    {
        $this->setState($state);
    }

    public static function pending()
    {
        return new self(0);
    }

    public static function resolved()
    {
        return new self(1);
    }

    public static function failed()
    {
        return new self(2);
    }

    public function equals(State $state)
    {
        return $state->state() === $this->state();
    }

    public function state(): int
    {
        return $this->state;
    }

    private function setState(int $state)
    {
        if (false === array_key_exists($state, self::$STATES)) {
            throw new \InvalidArgumentException("unknown state {$state}");
        }

        $this->state = $state;
    }

    public function __toString(): string
    {
        return self::$STATES[$this->state];
    }
}