<?php

namespace EventSourcing\Common\Model;

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
