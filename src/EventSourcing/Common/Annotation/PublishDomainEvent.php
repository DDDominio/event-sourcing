<?php

namespace EventSourcing\Common\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("METHOD")
 */
class PublishDomainEvent
{
    /**
     * @var string
     *
     * @Required()
     */
    private $event;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->event = $values['event'];
    }

    /**
     * @return string
     */
    public function event()
    {
        return $this->event;
    }
}
