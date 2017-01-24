<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\Event;

trait EventSourcedAggregateRoot
{
    /**
     * @var Event[]
     */
    private $changes = [];

    /**
     * @var int
     */
    private $version = 0;

    /**
     * @param Event $domainEvent
     * @param bool $trackChanges
     * @throws DomainEventNotUnderstandableException
     */
    public function apply(Event $domainEvent, $trackChanges = true)
    {
        $eventHandlerName = $this->getEventHandlerName($domainEvent);
        if (!method_exists($this, $eventHandlerName)) {
            if (!$this->applyRecursively($eventHandlerName, $domainEvent, $trackChanges)) {
                throw new DomainEventNotUnderstandableException();
            }
        } else {
            $this->executeEventHandler($this, $eventHandlerName, $domainEvent, $trackChanges);
        }
    }

    /**
     * @param string $eventHandlerName
     * @param Event $domainEvent
     * @param bool $trackChanges
     * @return bool
     */
    private function applyRecursively($eventHandlerName, Event $domainEvent, $trackChanges)
    {
        $applied = false;
        $reflectedClass = new \ReflectionClass(get_class($this));
        foreach ($reflectedClass->getProperties() as $property) {
            $propertyValue = $this->{$property->getName()};
            if (is_object($propertyValue)) {
                if (method_exists($propertyValue, $eventHandlerName)) {
                    $this->executeEventHandler($propertyValue, $eventHandlerName, $domainEvent, $trackChanges);
                    $applied = true;
                }
            }
            if (is_array($propertyValue)) {
                foreach ($propertyValue as $item) {
                    if (method_exists($item, $eventHandlerName)) {
                        $this->executeEventHandler($item, $eventHandlerName, $domainEvent, $trackChanges);
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
     * @param Event $domainEvent
     * @param bool $trackChanges
     */
    private function executeEventHandler($entity, $eventHandlerName, $domainEvent, $trackChanges)
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

    public function clearChanges()
    {
        $this->changes = [];
    }
}
