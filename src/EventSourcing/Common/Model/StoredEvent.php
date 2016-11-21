<?php

namespace EventSourcing\Common\Model;

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
     * @var \DateTime
     */
    private $occurredOn;

    /**
     * @param int $id
     * @param string $streamId
     * @param string $name
     * @param string $body
     * @param \DateTime $occurredOn
     */
    public function __construct($id, $streamId, $name, $body, \DateTime $occurredOn)
    {
        $this->id = $id;
        $this->streamId = $streamId;
        $this->name = $name;
        $this->body = $body;
        $this->occurredOn = $occurredOn;
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
     * @return \DateTime
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
