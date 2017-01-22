<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;
use JMS\Serializer\Annotation as Serializer;

class DummyCreated implements DomainEvent
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $id;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

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
     * @param string $id
     * @param string $name
     * @param string $description
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($id, $name, $description, \DateTimeImmutable $occurredOn)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->occurredOn = $occurredOn;
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
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
