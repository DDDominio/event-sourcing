<?php

namespace EventSourcing\Versioning;

interface UpgradableEventStore
{
    /**
     * @param string $type
     * @param Version $from
     * @param Version $to
     */
    public function migrate($type, $from, $to);
}
