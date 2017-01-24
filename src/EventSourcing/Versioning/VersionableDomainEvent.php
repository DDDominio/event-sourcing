<?php

namespace DDDominio\EventSourcing\Versioning;

use DDDominio\Common\Event;

interface VersionableDomainEvent extends Event, Versionable
{
}
