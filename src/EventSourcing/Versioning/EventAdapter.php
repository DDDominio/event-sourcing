<?php

namespace EventSourcing\Versioning;

use EventSourcing\Common\Model\StoredEvent;
use EventSourcing\Versioning\JsonAdapter\JsonAdapter;

class EventAdapter
{
    /**
     * @var JsonAdapter
     */
    private $jsonAdapter;

    /**
     * @param JsonAdapter $jsonAdapter
     */
    public function __construct(JsonAdapter $jsonAdapter)
    {
        $this->jsonAdapter = $jsonAdapter;
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     * @param string $newName
     */
    public function renameField($storedEvent, $pathExpression, $newName)
    {
        $body = $this->jsonAdapter->renameKey(
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
        $storedEvent->setName($newName);
    }

    /**
     * @param StoredEvent $storedEvent
     * @param string $pathExpression
     * @param \Closure $closure
     */
    public function enrich($storedEvent, $pathExpression, \Closure $closure)
    {
        $value = $closure->call($this, json_decode($storedEvent->body()));
        $body = $this->jsonAdapter->addKey(
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
        $body = $this->jsonAdapter->removeKey(
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
        $body = $this->jsonAdapter->addKey(
            $storedEvent->body(),
            $pathExpression,
            $value
        );
        $storedEvent->setBody($body);
    }
}
