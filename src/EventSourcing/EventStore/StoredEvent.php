<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\Common\Event;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\EventSourcing\Versioning\Versionable;

class StoredEvent implements Event, Versionable
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
    private $body;

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
     * @param string $body
     * @param \DateTimeImmutable $occurredOn
     * @param Version $version
     */
    public function __construct($id, $streamId, $type, $body, $occurredOn, $version)
    {
        $this->id = $id;
        $this->streamId = $streamId;
        $this->type = $type;
        $this->body = $body;
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
    public function body()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
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
