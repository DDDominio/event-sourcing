<?php

namespace Tests\DDDominio\EventSourcing\Serialization;

use DDDominio\EventSourcing\Serialization\SymfonySerializer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function serialize()
    {
        $event = ['hello' => 'world'];
        $expectedSerializationResult = '{"hello":"world"}';
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($event, 'json')
            ->willReturn($expectedSerializationResult);
        $symfonySerializer = new SymfonySerializer($serializer);

        $serializationResult = $serializationResult = $symfonySerializer->serialize($event);

        $this->assertEquals($serializationResult, $expectedSerializationResult);
    }

    /**
     * @test
     */
    public function deserialize()
    {
        $serializedEvent = '{"hello":"world"}';
        $serializedEventType = 'App\Hello\World';
        $expectedDeserializationResult = '{"hello":"world"}';
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($serializedEvent, $serializedEventType, 'json')
            ->willReturn($expectedDeserializationResult);
        $symfonySerializer = new SymfonySerializer($serializer);

        $deserializationResult = $symfonySerializer->deserialize($serializedEvent, $serializedEventType);

        $this->assertSame($expectedDeserializationResult, $deserializationResult);
    }
}
