<?php

namespace EventSourcing\Versioning;

use EventSourcing\Common\StoredEvent;

class EventUpgrader
{
    /**
     * @var array
     */
    private $upgrades;

    /**
     * @var EventAdapter
     */
    private $eventAdapter;

    /**
     * @param EventAdapter $eventAdapter
     */
    public function __construct(EventAdapter $eventAdapter)
    {
        $this->eventAdapter = $eventAdapter;
    }

    /**
     * @param Upgrade $upgrade
     */
    public function registerUpgrade(Upgrade $upgrade)
    {
        $this->upgrades[$upgrade->eventClass()][] = $upgrade;
    }

    /**
     * @param StoredEvent $storedEvent
     * @param Version $version
     */
    public function migrate(StoredEvent $storedEvent, $version = null)
    {
        if (is_null($version)) {
            $this->upgrade($storedEvent);
        } else {
            if ($storedEvent->version()->greaterThan($version)) {
                $this->downgrade($storedEvent, $version);
            } else {
                $this->upgrade($storedEvent, $version);
            }
        }
    }

    /**
     * @param StoredEvent $storedEvent
     * @param Version $version
     */
    public function upgrade(StoredEvent $storedEvent, $version = null)
    {
        if (isset($this->upgrades[$storedEvent->name()])) {
            foreach ($this->upgrades[$storedEvent->name()] as $upgrade) {
                if ($storedEvent->version()->equalTo($upgrade->from())) {
                    $upgrade->upgrade($storedEvent);
                    $this->eventAdapter->changeValue($storedEvent, "version", function() use ($upgrade) {
                        return (string)$upgrade->to();
                    });
                    $storedEvent->setVersion($upgrade->to());
                    if (!is_null($version) && $upgrade->to()->equalTo($version)) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * @param StoredEvent $storedEvent
     * @param Version $version
     */
    public function downgrade(StoredEvent $storedEvent, $version = null)
    {
        if (isset($this->upgrades[$storedEvent->name()])) {
            $upgrades = array_reverse($this->upgrades[$storedEvent->name()]);
            foreach ($upgrades as $upgrade) {
                if ($storedEvent->version()->equalTo($upgrade->to())) {
                    $upgrade->downgrade($storedEvent);
                    $this->eventAdapter->changeValue($storedEvent, "version", function() use ($upgrade) {
                        return (string)$upgrade->from();
                    });
                    $storedEvent->setVersion($upgrade->from());
                    if (!is_null($version) && $upgrade->to()->equalTo($version)) {
                        return;
                    }
                }
            }
        }
    }
}
