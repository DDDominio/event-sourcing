<?php

namespace DDDominio\EventSourcing\Common;

trait EventSourcedAggregateRoot
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
     * @param mixed $domainEvent
     * @param bool $trackChanges
     * @throws DomainEventNotUnderstandableException
     */
    public function apply($domainEvent, $trackChanges = true)
    {
        if (!$domainEvent instanceof DomainEvent) {
            $domainEvent = DomainEvent::record($domainEvent);
        }
        if ($trackChanges) {
            $this->changes[] = $domainEvent;
        }
        $eventHandlerName = $this->getEventHandlerName($domainEvent);
        if (method_exists($this, $eventHandlerName)) {
            $this->executeEventHandler($this, $eventHandlerName, $domainEvent);
        } else {
            $this->applyRecursively($eventHandlerName, $domainEvent);
        }
    }

    /**
     * @param string $eventHandlerName
     * @param mixed $domainEventData
     * @throws DomainEventNotUnderstandableException
     */
    private function applyRecursively($eventHandlerName, $domainEventData)
    {
        $applied = false;
        $reflectedClass = new \ReflectionClass(get_class($this));
        foreach ($reflectedClass->getProperties() as $property) {
            $propertyValue = $this->{$property->getName()};
            if (is_object($propertyValue)) {
                if (method_exists($propertyValue, $eventHandlerName)) {
                    $this->executeEventHandler($propertyValue, $eventHandlerName, $domainEventData);
                    $applied = true;
                }
            }
            if (is_array($propertyValue)) {
                foreach ($propertyValue as $item) {
                    if (method_exists($item, $eventHandlerName)) {
                        $this->executeEventHandler($item, $eventHandlerName, $domainEventData);
                        $applied = true;
                    }
                }
            }
        }
        if (!$applied) {
            throw new DomainEventNotUnderstandableException();
        }
    }

    /**
     * @param DomainEvent $domainEvent
     * @return string
     */
    private function getEventHandlerName($domainEvent)
    {
        return 'when' . (new \ReflectionClass($domainEvent->data()))->getShortName();
    }

    /**
     * @param object $entity
     * @param string $eventHandlerName
     * @param DomainEvent $domainEvent
     */
    private function executeEventHandler($entity, $eventHandlerName, $domainEvent)
    {
        $entity->{$eventHandlerName}($domainEvent->data(), $domainEvent->occurredOn());
        $this->increaseAggregateVersion();
    }

    private function increaseAggregateVersion()
    {
        $this->version++;
    }

    /**
     * @return DomainEvent[]
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

    public function clearChanges()
    {
        $this->changes = [];
    }
}
