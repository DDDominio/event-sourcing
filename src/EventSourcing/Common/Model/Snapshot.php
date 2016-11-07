<?php

namespace EventSourcing\Common\Model;

interface Snapshot
{
    /**
     * @return string
     */
    public function aggregateClass();

    /**
     * @return int
     */
    public function version();
}
