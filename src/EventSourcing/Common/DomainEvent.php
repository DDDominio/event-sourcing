<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\Event;
use DDDominio\EventSourcing\Versioning\Version;

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
     * @var Version
     */
    private $version;

    /**
     * @param mixed $data
     * @param array $metadata
     * @param \DateTimeImmutable $occurredOn
     * @param Version|null $version
     */
    public function __construct($data, array $metadata = [], \DateTimeImmutable $occurredOn, $version = null)
    {
        $this->data = $data;
        $this->metadata = new MetadataBag($metadata);
        $this->occurredOn = $occurredOn;
        $this->version = $version;
    }

    /**
     * @param mixed $data
     * @param array $metadata
     * @param Version|null $version
     * @return DomainEvent
     */
    public static function record($data, array $metadata = [], $version = null)
    {
        return new self($data, $metadata, new \DateTimeImmutable(), $version);
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

    /**
     * @return Version|null
     */
    public function version()
    {
        return $this->version;
    }
}
