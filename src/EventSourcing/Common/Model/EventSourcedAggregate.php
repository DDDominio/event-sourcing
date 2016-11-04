<?php

namespace EventSourcing\Common\Model;

trait EventSourcedAggregate
{
    /**
     * @var DomainEvent[]
     */
    private $changes = [];

    /**
     * @var int
     */
    private $version = 0;

    /**
     * @return mixed
     */
    public static function buildEmpty()
    {
        return (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
    }

    /**
     * @param DomainEvent $domainEvent
     * @param bool $trackChanges
     * @throws DomainEventNotUnderstandableException
     */
    public function apply(DomainEvent $domainEvent, $trackChanges = true)
    {
        $methodName = 'when' . (new \ReflectionClass($domainEvent))->getShortName();

        if (!method_exists($this, $methodName)) {
            throw new DomainEventNotUnderstandableException();
        }

        $this->$methodName($domainEvent);
        if ($trackChanges) {
            $this->changes[] = $domainEvent;
        }
        $this->increaseAggregateVersion();
    }

    private function increaseAggregateVersion()
    {
        $this->version++;
    }

    /**
     * @return array
     */
    public function changes()
    {
        return $this->changes;
    }

    /**
     * @return int
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function originalVersion()
    {
        return $this->version - count($this->changes());
    }

    public function commitChanges()
    {
        $this->changes = [];
    }
}
