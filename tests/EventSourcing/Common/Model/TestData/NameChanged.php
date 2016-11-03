<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\DomainEvent;

class NameChanged implements DomainEvent
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }
}
