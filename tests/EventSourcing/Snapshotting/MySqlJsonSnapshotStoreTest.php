<?php

namespace tests\EventSourcing\Snapshotting;

use Doctrine\Common\Annotations\AnnotationRegistry;
use EventSourcing\Serialization\JsonSerializer;
use EventSourcing\Serialization\Serializer;
use EventSourcing\Snapshotting\MySqlJsonSnapshotStore;
use EventSourcing\Snapshotting\Snapshot;
use JMS\Serializer\SerializerBuilder;
use Tests\EventSourcing\Common\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\TestData\DummySnapshot;

class MySqlJsonSnapshotStoreTest extends \PHPUnit_Framework_TestCase
{
    const DB_HOST = 'localhost';
    const DB_USER = 'event_sourcing';
    const DB_PASS = 'event_sourcing123';
    const DB_NAME = 'json_event_store';

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    public function setUp()
    {
        $this->connection = new \PDO(
            'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME,
            self::DB_USER,
            self::DB_PASS
        );
        $this->connection->query('TRUNCATE snapshots')->execute();

        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation',
            __DIR__ . '/../../../vendor/jms/serializer/src'
        );

        $this->serializer = new JsonSerializer(
            SerializerBuilder::create()->build()
        );
    }

    /**
     * @test
     */
    public function findLastSnapshotOfAStream()
    {
        $snapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            3
        );
        $lastSnapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            10
        );
        $eventStore = new MysqlJsonSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->addSnapshot($snapshot);
        $eventStore->addSnapshot($lastSnapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot(
            DummyEventSourcedAggregate::class,
            'id'
        );

        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals(10, $retrievedSnapshot->version());
    }

    /**
     * @test
     */
    public function addAnSnapshot()
    {
        $snapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            3
        );
        $eventStore = new MySqlJsonSnapshotStore(
            $this->connection,
            $this->serializer
        );

        $eventStore->addSnapshot($snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot(
            DummyEventSourcedAggregate::class,
            'id'
        );
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
    }

    /**
     * @test
     */
    public function findSnapshotForEventVersion()
    {
        $eventStore = new MySqlJsonSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->addSnapshot(
            new DummySnapshot('id', 'new name', 'description', 2)
        );
        $eventStore->addSnapshot(
            new DummySnapshot('id', 'another name', 'new description', 4)
        );

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

        $this->assertEquals(2, $snapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForAnotherEventVersion()
    {
        $eventStore = new MySqlJsonSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->addSnapshot(
            new DummySnapshot('id', 'new name', 'description', 2)
        );
        $eventStore->addSnapshot(
            new DummySnapshot('id', 'another name', 'new description', 4)
        );

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
    }
}
