<?php

namespace EventStore\Common\Model;

interface Snapshot
{
    /**
     * @return string
     */
    public function aggregateClass();
}
