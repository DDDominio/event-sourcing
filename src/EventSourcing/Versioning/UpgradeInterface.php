<?php

namespace DDDominio\EventSourcing\Versioning;

use DDDominio\EventSourcing\EventStore\StoredEvent;

interface UpgradeInterface
{
    /**
     * @param StoredEvent $event
     */
    public function upgrade(StoredEvent $event);

    /**
     * @param StoredEvent $event
     */
    public function downgrade(StoredEvent $event);

    /**
     * @return string
     */
    public function eventClass();

    /**
     * @return Version
     */
    public function from();

    /**
     * @return Version
     */
    public function to();
}
