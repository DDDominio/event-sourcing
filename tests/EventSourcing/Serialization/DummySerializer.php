<?php

namespace DDDominio\Tests\EventSourcing\Serialization;

use DDDominio\EventSourcing\Serialization\SerializerInterface;

class DummySerializer implements SerializerInterface
{
    /**
     * @param object|array $object
     * @return string
     */
    public function serialize($object)
    {
        return $object;
    }

    /**
     * @param string $serializedObject
     * @param string $class
     * @return object|array
     */
    public function deserialize($serializedObject, $class)
    {
        return $serializedObject;
    }
}
