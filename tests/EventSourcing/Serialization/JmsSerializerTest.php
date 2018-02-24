<?php

namespace Tests\DDDominio\EventSourcing\Serialization;

use DDDominio\EventSourcing\Serialization\JmsSerializer;
use JMS\Serializer\Serializer;

class JmsSerializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function serialize()
    {
        $event = ['hello' => 'world'];
        $expectedSerializationResult = '{"hello":"world"}';
        $serializer = $this->createMock(Serializer::class);
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($event, 'json')
            ->willReturn($expectedSerializationResult);
        $jmsSerializer = new JmsSerializer($serializer);

        $serializationResult = $serializationResult = $jmsSerializer->serialize($event);

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
        $serializer = $this->createMock(Serializer::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with($serializedEvent, $serializedEventType, 'json')
            ->willReturn($expectedDeserializationResult);
        $jmsSerializer = new JmsSerializer($serializer);

        $deserializationResult = $jmsSerializer->deserialize($serializedEvent, $serializedEventType);

        $this->assertSame($expectedDeserializationResult, $deserializationResult);
    }
}
