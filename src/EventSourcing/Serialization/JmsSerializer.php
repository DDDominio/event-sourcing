<?php

namespace DDDominio\EventSourcing\Serialization;

use JMS\Serializer\Serializer;

class JmsSerializer implements SerializerInterface
{
    /**
     * @var Serializer
     */
    private $jmsSerializer;

    /**
     * @param Serializer $jmsSerializer
     */
    public function __construct(Serializer $jmsSerializer)
    {
        $this->jmsSerializer = $jmsSerializer;
    }

    /**
     * @param object $object
     * @return string
     */
    public function serialize($object)
    {
        return $this->jmsSerializer->serialize($object, 'json');
    }

    /**
     * @param string $serializedObject
     * @param string $class
     * @return object
     */
    public function deserialize($serializedObject, $class)
    {
        return $this->jmsSerializer->deserialize($serializedObject, $class, 'json');
    }
}
