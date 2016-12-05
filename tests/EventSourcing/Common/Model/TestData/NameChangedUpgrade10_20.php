<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\StoredEvent;
use EventSourcing\Versioning\Upgrade;
use EventSourcing\Versioning\Version;

class NameChangedUpgrade10_20 extends Upgrade
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
        return NameChanged::class;
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
