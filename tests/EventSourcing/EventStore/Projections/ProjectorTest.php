<?php

namespace Tests\EventSourcing\EventStore\Projections;

use DDDominio\Tests\EventSourcing\TestData\NameChanged;
use DDDominio\EventSourcing\EventStore\Projection\Projector;

class ProjectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function emitMultipleEventsToAStream()
    {
        $projector = new Projector();

        $projector->emit('stream', new NameChanged('name1'));
        $projector->emit('stream', new NameChanged('name2'));

        $this->assertCount(1, $projector->emittedEventsByStream());
        $this->assertCount(2, $projector->emittedEventsByStream()['stream']);
        $this->assertEquals('name1', $projector->emittedEventsByStream()['stream'][0]->data()->name());
        $this->assertEquals('name2', $projector->emittedEventsByStream()['stream'][1]->data()->name());
    }

    /**
     * @test
     */
    public function emitMultipleEventsToDifferentStreams()
    {
        $projector = new Projector();

        $projector->emit('stream1', new NameChanged('stream1-name1'));
        $projector->emit('stream2', new NameChanged('stream2-name1'));
        $projector->emit('stream1', new NameChanged('stream1-name2'));
        $projector->emit('stream1', new NameChanged('stream1-name3'));
        $projector->emit('stream2', new NameChanged('stream2-name2'));
        $projector->emit('stream1', new NameChanged('stream1-name4'));

        $this->assertCount(2, $projector->emittedEventsByStream());
        $this->assertCount(4, $projector->emittedEventsByStream()['stream1']);
        $this->assertCount(2, $projector->emittedEventsByStream()['stream2']);
        $this->assertEquals('stream1-name1', $projector->emittedEventsByStream()['stream1'][0]->data()->name());
        $this->assertEquals('stream1-name2', $projector->emittedEventsByStream()['stream1'][1]->data()->name());
        $this->assertEquals('stream1-name3', $projector->emittedEventsByStream()['stream1'][2]->data()->name());
        $this->assertEquals('stream1-name4', $projector->emittedEventsByStream()['stream1'][3]->data()->name());
        $this->assertEquals('stream2-name1', $projector->emittedEventsByStream()['stream2'][0]->data()->name());
        $this->assertEquals('stream2-name2', $projector->emittedEventsByStream()['stream2'][1]->data()->name());
    }
}
