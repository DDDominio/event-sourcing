<?php

namespace DDDominio\Tests\EventSourcing\Common\TestData;

use DDDominio\EventSourcing\Common\StoredEvent;
use DDDominio\EventSourcing\Versioning\Upgrade;
use DDDominio\EventSourcing\Versioning\Version;

class VersionedEventUpgrade10_20 extends Upgrade
{
    /**
     * @param StoredEvent $event
     */
    public function upgrade(StoredEvent $event)
    {
        $this->eventAdapter->renameField($event, 'name', 'username');
    }

    /**
     * @param StoredEvent $event
     */
    public function downgrade(StoredEvent $event)
    {
        $this->eventAdapter->renameField($event, 'username', 'name');
    }

    /**
     * @return string
     */
    public function eventClass()
    {
        return VersionedEvent::class;
    }

    /**
     * @return Version
     */
    public function from()
    {
        return Version::fromString("1.0");
    }

    /**
     * @return Version
     */
    public function to()
    {
        return Version::fromString("2.0");
    }
}