<?php

namespace DDDominio\EventSourcing\Versioning;

use DDDominio\EventSourcing\EventStore\StoredEvent;

interface EventUpgraderInterface
{
    /**
     * @param Upgrade $upgrade
     */
    public function registerUpgrade(Upgrade $upgrade);

    /**
     * @param StoredEvent $storedEvent
     * @param Version $version
     */
    public function migrate(StoredEvent $storedEvent, $version = null);

    /**
     * @param StoredEvent $storedEvent
     * @param Version $version
     */
    public function upgrade(StoredEvent $storedEvent, $version = null);

    /**
     * @param StoredEvent $storedEvent
     * @param Version $version
     */
    public function downgrade(StoredEvent $storedEvent, $version = null);
}
