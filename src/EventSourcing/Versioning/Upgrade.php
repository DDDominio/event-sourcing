<?php

namespace EventSourcing\Versioning;

use EventSourcing\Common\StoredEvent;

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
    public abstract function upgrade(StoredEvent $event);

    /**
     * @param StoredEvent $event
     */
    public abstract function downgrade(StoredEvent $event);

    /**
     * @return string
     */
    public abstract function eventClass();

    /**
     * @return Version
     */
    public abstract function from();


    /**
     * @return Version
     */
    public abstract function to();
}
