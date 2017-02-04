<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\EventStore\StoredEvent;
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
        $this->eventAdapter->changeValue($event, 'name', function($data) {
            $value = json_decode('{}');
            $value->first = $data->name;
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
        $this->eventAdapter->changeValue($event, 'username', function($data) {
            return $data->username->first;
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
