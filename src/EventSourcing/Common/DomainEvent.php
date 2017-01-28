<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\Event;
use JMS\Serializer\Annotation as Serializer;

class DomainEvent implements Event
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var MetadataBag
     */
    private $metadata;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param mixed $data
     * @param array $metadata
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($data, array $metadata = [], \DateTimeImmutable $occurredOn)
    {
        $this->data = $data;
        $this->metadata = new MetadataBag($metadata);
        $this->occurredOn = $occurredOn;
    }

    /**
     * @param mixed $data
     * @param array $metadata
     * @return DomainEvent
     */
    public static function record($data, array $metadata = [])
    {
        return new self($data, $metadata, new \DateTimeImmutable());
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->data;
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
