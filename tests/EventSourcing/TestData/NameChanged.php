<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\EventSourcing\Versioning\Versionable;
use JMS\Serializer\Annotation as Serializer;

class NameChanged implements Versionable
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
