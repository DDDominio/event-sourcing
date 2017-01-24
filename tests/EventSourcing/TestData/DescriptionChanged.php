<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;

class DescriptionChanged extends DomainEvent
{
    /**
     * @var string
     */
    private $description;

    /**
     * @param string $description
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($description, \DateTimeImmutable $occurredOn)
    {
        $this->description = $description;
        parent::__construct([], $occurredOn);
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }
}
