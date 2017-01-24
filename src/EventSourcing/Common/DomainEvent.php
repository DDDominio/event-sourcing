<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\Event;
use JMS\Serializer\Annotation as Serializer;

class DomainEvent implements Event
{
    /**
     * @var MetadataBag
     */
    private $metadata;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param array $metadata
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct(array $metadata = [], \DateTimeImmutable $occurredOn)
    {
        $this->metadata = new MetadataBag($metadata);
        $this->occurredOn = $occurredOn;
    }

    /**
     * @return MetadataBag
     */
    public function metadata()
    {
        return $this->metadata;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
