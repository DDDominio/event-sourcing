<?php

namespace Tests\EventSourcing\Common\Model;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use EventSourcing\Common\Model\DoctrineEventStore;
use EventSourcing\Common\Model\DomainEvent;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\Snapshot;

class DoctrineEventStoreTest extends \PHPUnit_Framework_TestCase
{
    private $connection;

    public function setUp()
    {
        $connectionParams = array(
            'dbname' => 'event_sourcing',
            'user' => 'event_sourcing',
            'password' => 'event_sourcing123',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        );
        $config = new Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);

        $this->connection->query('TRUNCATE events')->execute();
        $this->connection->query('DELETE FROM streams')->execute();
    }

    /**
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $eventStore = new DoctrineEventStore($this->connection);
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('streamId', [$domainEvent]);

        $stream = $eventStore->readFullStream('streamId');
        $this->assertInstanceOf(EventStream::class, $stream);
        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function appendAnEventToAnExistentStream()
    {
        $eventStore = new DoctrineEventStore($this->connection);
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('streamId', [$domainEvent]);
        $eventStore->appendToStream('streamId', [$domainEvent], 1);
        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(2, $stream);
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\ConcurrencyException
     */
    public function ifTheExpectedVersionOfTheStreamDoesNotMatchWithRealVersionAConcurrencyExceptionShouldBeThrown()
    {
        $domainEvent = $this->createMock(DomainEvent::class);
        $eventStore = new DoctrineEventStore($this->connection);
        $eventStore->appendToStream('streamId', [$domainEvent]);

        $eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $eventStore = new DoctrineEventStore($this->connection);
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $event = $this->createMock(DomainEvent::class);
        $eventStore = new DoctrineEventStore($this->connection);
        $eventStore->appendToStream('streamId', [$event]);

        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $eventStore = new DoctrineEventStore($this->connection);

        $stream = $eventStore->readFullStream('NonExistentStreamId');

        $this->assertTrue($stream->isEmpty());
        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function findLastSnapshotOfAStream()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $snapshot
            ->method('aggregateClass')
            ->willReturn('aggregateClass');
        $snapshot
            ->method('aggregateId')
            ->willReturn('aggregateId');
        $snapshot
            ->method('version')
            ->willReturn(10);
        $lastSnapshot = $this->createMock(Snapshot::class);
        $lastSnapshot
            ->method('aggregateClass')
            ->willReturn('aggregateClass');
        $lastSnapshot
            ->method('aggregateId')
            ->willReturn('aggregateId');
        $lastSnapshot
            ->method('version')
            ->willReturn(20);
        $eventStore = new DoctrineEventStore($this->connection);
        $eventStore->addSnapshot($snapshot);
        $eventStore->addSnapshot($lastSnapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot('aggregateClass', 'aggregateId');

        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals('aggregateClass', $retrievedSnapshot->aggregateClass());
        $this->assertEquals('aggregateId', $retrievedSnapshot->aggregateId());
        $this->assertEquals(20, $retrievedSnapshot->version());
    }

    /**
     * @test
     */
    public function addAnSnapshot()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $snapshot
            ->method('aggregateClass')
            ->willReturn('aggregateClass');
        $snapshot
            ->method('aggregateId')
            ->willReturn('aggregateId');
        $eventStore = new DoctrineEventStore($this->connection);

        $eventStore->addSnapshot($snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot('aggregateClass', 'aggregateId');
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
    }
}
