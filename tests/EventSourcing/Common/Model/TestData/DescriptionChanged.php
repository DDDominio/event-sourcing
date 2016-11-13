<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\DomainEvent;
use JMS\Serializer\Annotation as Serializer;

class DescriptionChanged implements DomainEvent
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
