<?php

namespace EventSourcing\Snapshotting;

interface Snapshot
{
    /**
     * @return string
     */
    public function aggregateClass();

    /**
     * @return string
     */
    public function aggregateId();

    /**
     * @return int
     */
    public function version();
}
