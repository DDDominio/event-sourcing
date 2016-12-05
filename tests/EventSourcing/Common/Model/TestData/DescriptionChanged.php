<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\DomainEvent;

class DescriptionChanged implements DomainEvent
{
    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param string $description
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($description, \DateTimeImmutable $occurredOn)
    {
        $this->description = $description;
        $this->occurredOn = $occurredOn;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
