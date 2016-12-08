<?php

namespace Tests\EventSourcing\Common\TestData;

use EventSourcing\Snapshotting\Snapshot;

class DummySnapshot implements Snapshot
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $version;

    /**
     * @param string $id
     * @param string $name
     * @param string $description
     * @param int $version
     */
    public function __construct($id, $name, $description, $version)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function aggregateClass()
    {
        return DummyEventSourcedAggregate::class;
    }

    /**
     * @return string
     */
    public function aggregateId()
    {
        return $this->id();
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function version()
    {
        return $this->version;
    }
}