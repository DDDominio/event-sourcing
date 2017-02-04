<?php

namespace DDDominio\EventSourcing\Versioning;

interface VersionableInterface
{
    /**
     * @return Version
     */
    public function version();
}
