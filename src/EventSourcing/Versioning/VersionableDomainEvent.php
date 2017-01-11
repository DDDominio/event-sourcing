<?php

namespace EventSourcing\Versioning;

use EventSourcing\Common\DomainEvent;

interface VersionableDomainEvent extends DomainEvent, Versionable
{
}
