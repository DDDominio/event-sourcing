<?php

namespace EventSourcing\Versioning;

interface Versionable
{
    /**
     * @return Version
     */
    public function version();
}
