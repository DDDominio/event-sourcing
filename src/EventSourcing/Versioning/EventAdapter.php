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
        $body = $this->jsonTransformer->renameKey(
            $storedEvent->body(),
            $pathExpression,
            $newName
        );
        $storedEvent->setBody($body);
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
        $value = $closure->call($this, json_decode($storedEvent->body()));
        $body = $this->jsonTransformer->addKey(
            $storedEvent->body(),
            $pathExpression,
            $value
        );
        $storedEvent->setBody($body);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     */
    public function removeField($storedEvent, $pathExpression)
    {
        $body = $this->jsonTransformer->removeKey(
            $storedEvent->body(),
            $pathExpression
        );
        $storedEvent->setBody($body);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     * @param \Closure $closure
     */
    public function changeValue($storedEvent, $pathExpression, \Closure $closure)
    {
        $value = $closure->call($this, json_decode($storedEvent->body()));
        $body = $this->jsonTransformer->addKey(
            $storedEvent->body(),
            $pathExpression,
            $value
        );
        $storedEvent->setBody($body);
    }
}
