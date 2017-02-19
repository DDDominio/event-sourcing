<?php

namespace DDDominio\EventSourcing\EventStore;

interface InitializableInterface
{
    public function initialize();

    /**
     * @return bool
     */
    public function initialized();
}
