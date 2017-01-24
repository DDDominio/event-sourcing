<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;
use JMS\Serializer\Annotation as Serializer;

class DummyCreated extends DomainEvent
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
        parent::__construct([], $occurredOn);
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
}
