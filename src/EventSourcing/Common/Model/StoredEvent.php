<?php

namespace EventSourcing\Common\Model;

use EventSourcing\Versioning\Version;

class StoredEvent
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
    private $name;

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
     * @param string $name
     * @param string $body
     * @param \DateTimeImmutable $occurredOn
     * @param Version $version
     */
    public function __construct($id, $streamId, $name, $body, \DateTimeImmutable $occurredOn, $version)
    {
        $this->id = $id;
        $this->streamId = $streamId;
        $this->name = $name;
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
    public function name()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
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
    public function setBody(string $body)
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
