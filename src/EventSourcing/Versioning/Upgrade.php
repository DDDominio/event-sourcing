<?php

namespace DDDominio\EventSourcing\Versioning;

use DDDominio\EventSourcing\EventStore\StoredEvent;

abstract class Upgrade
{
    /**
     * @var EventAdapter
     */
    protected $eventAdapter;

    /**
     * @param EventAdapter $eventAdapter
     */
    public function __construct(EventAdapter $eventAdapter)
    {
        $this->eventAdapter = $eventAdapter;
    }

    /**
     * @param StoredEvent $event
     */
    abstract public function upgrade(StoredEvent $event);

    /**
     * @param StoredEvent $event
     */
    abstract public function downgrade(StoredEvent $event);

    /**
     * @return string
     */
    abstract public function eventClass();

    /**
     * @return Version
     */
    abstract public function from();


    /**
     * @return Version
     */
    abstract public function to();
}
