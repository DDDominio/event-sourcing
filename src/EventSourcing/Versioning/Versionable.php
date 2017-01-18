<?php

namespace DDDominio\EventSourcing\Versioning;

interface Versionable
{
    /**
     * @return Version
     */
    public function version();
}
