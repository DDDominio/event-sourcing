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
     * @return static
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
        $eventHandlerName = $this->getEventHandlerName($domainEvent);
        if (!method_exists($this, $eventHandlerName)) {
            if (!$this->applyRecursively($eventHandlerName, $domainEvent, $trackChanges)) {
                throw new DomainEventNotUnderstandableException();
            }
        } else {
            $this->executeDomainEventHandler($this, $eventHandlerName, $domainEvent, $trackChanges);
        }
    }

    /**
     * @param string $eventHandlerName
     * @param DomainEvent $domainEvent
     * @param bool $trackChanges
     * @return bool
     */
    private function applyRecursively($eventHandlerName, DomainEvent $domainEvent, $trackChanges)
    {
        $applied = false;
        $reflectedClass = new \ReflectionClass(get_class($this));
        foreach ($reflectedClass->getProperties() as $property) {
            $propertyValue = $this->{$property->getName()};
            if (is_object($propertyValue)) {
                if (method_exists($propertyValue, $eventHandlerName)) {
                    $this->executeDomainEventHandler($propertyValue, $eventHandlerName, $domainEvent, $trackChanges);
                    $applied = true;
                }
            }
            if (is_array($propertyValue)) {
                foreach ($propertyValue as $item) {
                    if (method_exists($item, $eventHandlerName)) {
                        $this->executeDomainEventHandler($item, $eventHandlerName, $domainEvent, $trackChanges);
                        $applied = true;
                    }
                }
            }
        }
        return $applied;
    }

    /**
     * @param DomainEvent $domainEvent
     * @return string
     */
    private function getEventHandlerName($domainEvent)
    {
        return 'when' . (new \ReflectionClass($domainEvent))->getShortName();
    }

    /**
     * @param object $entity
     * @param string $eventHandlerName
     * @param DomainEvent $domainEvent
     * @param bool $trackChanges
     */
    private function executeDomainEventHandler($entity, $eventHandlerName, $domainEvent, $trackChanges)
    {
        $entity->{$eventHandlerName}($domainEvent);
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
