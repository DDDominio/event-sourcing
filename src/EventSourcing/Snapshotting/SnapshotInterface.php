<?php

namespace DDDominio\EventSourcing\Snapshotting;

interface SnapshotInterface
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
