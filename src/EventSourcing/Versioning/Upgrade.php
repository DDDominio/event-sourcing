<?php

namespace DDDominio\EventSourcing\Versioning;

abstract class Upgrade implements UpgradeInterface
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
}
