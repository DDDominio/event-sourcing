<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\EventSourcing\Versioning\VersionableDomainEvent;
use JMS\Serializer\Annotation as Serializer;

class NameChanged extends DomainEvent implements VersionableDomainEvent
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @param string $name
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($name, \DateTimeImmutable $occurredOn)
    {
        $this->name = $name;
        parent::__construct([], $occurredOn);
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return Version
     */
    public function version()
    {
        return Version::fromString('3.0');
    }
}
