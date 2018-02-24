<?php

namespace DDDominio\EventSourcing\Serialization;

class SymfonySerializer implements SerializerInterface
{
    /**
     * @var \Symfony\Component\Serializer\SerializerInterface
     */
    private $symfonySerializer;

    /**
     * SymfonySerializer constructor.
     * @param \Symfony\Component\Serializer\SerializerInterface $symfonySerializer
     */
    public function __construct(\Symfony\Component\Serializer\SerializerInterface $symfonySerializer)
    {
        $this->symfonySerializer = $symfonySerializer;
    }

    /**
     * @param object|array $object
     * @return string
     */
    public function serialize($object)
    {
        return $this->symfonySerializer->serialize($object, 'json');
    }

    /**
     * @param string $serializedObject
     * @param string $class
     * @return object|array
     */
    public function deserialize($serializedObject, $class)
    {
        return $this->symfonySerializer->deserialize($serializedObject, $class, 'json');
    }
}