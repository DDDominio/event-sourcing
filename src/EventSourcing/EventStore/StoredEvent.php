<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\Common\EventInterface;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\EventSourcing\Versioning\VersionableInterface;

class StoredEvent implements EventInterface, VersionableInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $streamId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $data;

    /**
     * @var string
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
     * @param int $id
     * @param string $streamId
     * @param string $type
     * @param string $data
     * @param string $metadata
     * @param \DateTimeImmutable $occurredOn
     * @param Version $version
     */
    public function __construct($id, $streamId, $type, $data, $metadata, $occurredOn, $version)
    {
        $this->id = $id;
        $this->streamId = $streamId;
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata;
        $this->occurredOn = $occurredOn;
        $this->version = $version;
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function streamId()
    {
        return $this->streamId;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
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
     * @return Version
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @param Version $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
}
