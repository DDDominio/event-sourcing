<?php

namespace EventSourcing\Serialization;

interface Serializer
{
    /**
     * @param object $object
     * @return string
     */
    public function serialize($object);

    /**
     * @param string $serializedObject
     * @param string $class
     * @return object
     */
    public function deserialize($serializedObject, $class);
}