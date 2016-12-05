<?php

namespace EventSourcing\Versioning;

use EventSourcing\Common\Model\DomainEvent;

interface VersionableDomainEvent extends DomainEvent, Versionable
{
}
