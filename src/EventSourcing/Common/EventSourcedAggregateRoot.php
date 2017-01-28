<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\Event;

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
     * @param mixed $domainEventData
     * @param bool $trackChanges
     * @throws DomainEventNotUnderstandableException
     */
    public function apply($domainEventData, $trackChanges = true)
    {
        $eventHandlerName = $this->getEventHandlerName($domainEventData);
        if (!method_exists($this, $eventHandlerName)) {
            if (!$this->applyRecursively($eventHandlerName, $domainEventData, $trackChanges)) {
                throw new DomainEventNotUnderstandableException();
            }
        } else {
            $this->executeEventHandler($this, $eventHandlerName, $domainEventData, $trackChanges);
        }
    }

    /**
     * @param string $eventHandlerName
     * @param mixed $domainEventData
     * @param bool $trackChanges
     * @return bool
     */
    private function applyRecursively($eventHandlerName, $domainEventData, $trackChanges)
    {
        $applied = false;
        $reflectedClass = new \ReflectionClass(get_class($this));
        foreach ($reflectedClass->getProperties() as $property) {
            $propertyValue = $this->{$property->getName()};
            if (is_object($propertyValue)) {
                if (method_exists($propertyValue, $eventHandlerName)) {
                    $this->executeEventHandler($propertyValue, $eventHandlerName, $domainEventData, $trackChanges);
                    $applied = true;
                }
            }
            if (is_array($propertyValue)) {
                foreach ($propertyValue as $item) {
                    if (method_exists($item, $eventHandlerName)) {
                        $this->executeEventHandler($item, $eventHandlerName, $domainEventData, $trackChanges);
                        $applied = true;
                    }
                }
            }
        }
        return $applied;
    }

    /**
     * @param Event $domainEvent
     * @return string
     */
    private function getEventHandlerName($domainEvent)
    {
        return 'when' . (new \ReflectionClass($domainEvent))->getShortName();
    }

    /**
     * @param object $entity
     * @param string $eventHandlerName
     * @param Event $domainEventData
     * @param bool $trackChanges
     */
    private function executeEventHandler($entity, $eventHandlerName, $domainEventData, $trackChanges)
    {
        $entity->{$eventHandlerName}($domainEventData);
        if ($trackChanges) {
            $this->changes[] = new DomainEvent(
                $domainEventData,
                [],
                new \DateTimeImmutable()
            );
        }
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
