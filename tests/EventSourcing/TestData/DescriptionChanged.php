<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;
use JMS\Serializer\Annotation as Serializer;

class DescriptionChanged implements DomainEvent
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @var \DateTimeImmutable
     *
     * @Serializer\Type("DateTimeImmutable<'Y-m-d H:i:s'>")
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
