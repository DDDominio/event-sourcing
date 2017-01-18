<?php

namespace DDDominio\EventSourcing\Versioning;

use DDDominio\EventSourcing\Common\DomainEvent;

interface VersionableDomainEvent extends DomainEvent, Versionable
{
}
