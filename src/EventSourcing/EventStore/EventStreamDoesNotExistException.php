<?php

namespace DDDominio\EventSourcing\EventStore;

class EventStreamDoesNotExistException extends \Exception
{
    /**
     * @param $streamId
     * @param int $code
     * @param \Exception|null $previous
     * @return EventStreamDoesNotExistException
     */
    public static function fromStreamId($streamId, $code = 0, \Exception $previous = null)
    {
        return new self(
            sprintf('The stream of id %s does not exist', $streamId),
            $code,
            $previous
        );
    }
}
