<?php

namespace DDDominio\EventSourcing\Common;

class DomainEventNotUnderstandableException extends \Exception
{
    /**
     * @param string $aggregateType
     * @param string $eventType
     * @param int $code
     * @param \Exception|null $previous
     * @return DomainEventNotUnderstandableException
     */
    public static function fromAggreagteAndEventTypes($aggregateType, $eventType, $code = 0, \Exception $previous = null)
    {
        return new self(
            sprintf(
                'The aggregate of type %s does not understand events of type %s',
                $aggregateType,
                $eventType
            ),
            $code,
            $previous
        );
    }
}
