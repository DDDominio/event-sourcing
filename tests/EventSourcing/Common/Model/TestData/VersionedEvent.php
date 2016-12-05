<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Versioning\Version;
use EventSourcing\Versioning\VersionableDomainEvent;
use JMS\Serializer\Annotation as Serializer;

class VersionedEvent implements VersionableDomainEvent
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $username;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param string $username
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($username, \DateTimeImmutable $occurredOn)
    {
        $this->username = $username;
        $this->occurredOn = $occurredOn;
    }

    /**
     * @return string
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }

    /**
     * @return Version
     */
    public function version()
    {
        return Version::fromString('2.0');
    }
}