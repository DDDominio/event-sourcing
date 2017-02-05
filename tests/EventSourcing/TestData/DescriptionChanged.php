<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use JMS\Serializer\Annotation as Serializer;

class DescriptionChanged
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @param string $description
     */
    public function __construct($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }
}
