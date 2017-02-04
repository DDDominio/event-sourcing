<?php

namespace DDDominio\EventSourcing\EventStore;

class ConcurrencyException extends \Exception
{
    public static function fromVersions($currentVersion, $expectedVersion, $code = 0, \Exception $previous = null)
    {
        return new self(
            sprintf(
                'Current stream version %d does not match with the expected version %d',
                $currentVersion,
                $expectedVersion
            ),
            $code,
            $previous
        );
    }
}
