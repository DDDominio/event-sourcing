<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\EventSourcing\Versioning\VersionableInterface;
use JMS\Serializer\Annotation as Serializer;

class VersionedEvent extends DomainEvent implements VersionableInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @param string $username
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($username, \DateTimeImmutable $occurredOn)
    {
        $this->username = $username;
        parent::__construct([], $occurredOn);
    }

    /**
     * @return string
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * @return Version
     */
    public function version()
    {
        return Version::fromString('2.0');
    }
}