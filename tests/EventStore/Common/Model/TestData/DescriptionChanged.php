<?php

namespace Tests\EventStore\Common\Model\TestData;

use EventStore\Common\Model\DomainEvent;

class DescriptionChanged implements DomainEvent
{
    /**
     * @var string
     */
    private $description;

    /**
     * @param string $description
     */
    public function __construct($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }
}
