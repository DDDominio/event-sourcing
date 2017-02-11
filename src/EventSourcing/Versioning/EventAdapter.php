<?php

namespace DDDominio\EventSourcing\Versioning;

use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\Versioning\JsonTransformer\JsonTransformer;

class EventAdapter
{
    /**
     * @var JsonTransformer
     */
    private $jsonTransformer;

    /**
     * @param JsonTransformer $jsonTransformer
     */
    public function __construct(JsonTransformer $jsonTransformer)
    {
        $this->jsonTransformer = $jsonTransformer;
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     * @param string $newName
     */
    public function renameField($storedEvent, $pathExpression, $newName)
    {
        $data = $this->jsonTransformer->renameKey(
            $storedEvent->data(),
            $pathExpression,
            $newName
        );
        $storedEvent->setData($data);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $newName
     */
    public function rename($storedEvent, $newName)
    {
        $storedEvent->setType($newName);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     * @param \Closure $closure
     */
    public function enrich($storedEvent, $pathExpression, \Closure $closure)
    {
        $value = $closure(json_decode($storedEvent->data()));
        $data = $this->jsonTransformer->addKey(
            $storedEvent->data(),
            $pathExpression,
            $value
        );
        $storedEvent->setData($data);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     */
    public function removeField($storedEvent, $pathExpression)
    {
        $data = $this->jsonTransformer->removeKey(
            $storedEvent->data(),
            $pathExpression
        );
        $storedEvent->setData($data);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     * @param \Closure $closure
     */
    public function changeValue($storedEvent, $pathExpression, \Closure $closure)
    {
        $value = $closure(json_decode($storedEvent->data()));
        $data = $this->jsonTransformer->addKey(
            $storedEvent->data(),
            $pathExpression,
            $value
        );
        $storedEvent->setData($data);
    }
}
