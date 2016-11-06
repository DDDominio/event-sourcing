<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\DomainEvent;

class DummyCreated implements DomainEvent
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;
    /**
     * @var
     */
    private $id;

    /**
     * @param $id
     * @param string $name
     * @param string $description
     */
    public function __construct($id, $name, $description)
    {
        $this->name = $name;
        $this->description = $description;
        $this->id = $id;
    }

    /**
     * @return mixed
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
}
