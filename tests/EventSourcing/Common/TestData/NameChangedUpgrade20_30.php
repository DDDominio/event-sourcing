<?php

namespace DDDominio\Tests\EventSourcing\Common\TestData;

use DDDominio\EventSourcing\Common\StoredEvent;
use DDDominio\EventSourcing\Versioning\Upgrade;
use DDDominio\EventSourcing\Versioning\Version;

class NameChangedUpgrade20_30 extends Upgrade
{
    /**
     * @param StoredEvent $event
     */
    public function upgrade(StoredEvent $event)
    {
        $this->eventAdapter->renameField($event, 'username', 'name');
        $this->eventAdapter->changeValue($event, 'name', function($body) {
            $value = json_decode('{}');
            $value->first = $body->name;
            $value->last = '';
            return $value;
        });
    }

    /**
     * @param StoredEvent $event
     */
    public function downgrade(StoredEvent $event)
    {
        $this->eventAdapter->renameField($event, 'name', 'username');
        $this->eventAdapter->changeValue($event, 'username', function($body) {
            return $body->username->first;
        });
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
        return Version::fromString("2.0");
    }

    /**
     * @return Version
     */
    public function to()
    {
        return Version::fromString("3.0");
    }
}
