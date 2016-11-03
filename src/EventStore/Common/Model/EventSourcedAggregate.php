<?php

namespace EventStore\Common\Model;

trait EventSourcedAggregate
{
    /**
     * @var DomainEvent[]
     */
    private $changes = [];

    /**
     * @var bool
     */
    private $applyingDomainEvent = false;

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
     * @throws DomainEventNotUnderstandableException
     */
    public function apply(DomainEvent $domainEvent)
    {
        $methodName = 'when' . (new \ReflectionClass($domainEvent))->getShortName();

        if (!method_exists($this, $methodName)) {
            throw new DomainEventNotUnderstandableException();
        }

        $this->disableDomainEventPublication();
        $this->$methodName($domainEvent);
        $this->increaseAggregateVersion();
        $this->enableDomainEventPublication();
    }

    private function disableDomainEventPublication()
    {
        $this->setApplyingDomainEvent(true);
    }

    private function enableDomainEventPublication()
    {
        $this->setApplyingDomainEvent(false);
    }

    /**
     * @param boolean $applyingDomainEvent
     */
    private function setApplyingDomainEvent($applyingDomainEvent)
    {
        $this->applyingDomainEvent = $applyingDomainEvent;
    }

    /**
     * @param DomainEvent $domainEvent
     */
    protected function publishDomainEvent(DomainEvent $domainEvent)
    {
        if ($this->isDomainEventsPublicationEnabled()) {
            $this->changes[] = $domainEvent;
            $this->increaseAggregateVersion();
        }
    }

    private function increaseAggregateVersion()
    {
        $this->version++;
    }

    private function isDomainEventsPublicationEnabled()
    {
        return !$this->applyingDomainEvent;
    }

    /**
     * @return array
     */
    public function changes()
    {
        return $this->changes;
    }

    public function version()
    {
        return $this->version;
    }
}