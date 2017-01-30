<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\Annotation\AggregateDeleter;

/**
 * @AggregateDeleter()
 */
class DummyDeleted
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param string $id
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($id, \DateTimeImmutable $occurredOn)
    {
        $this->id = $id;
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
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
