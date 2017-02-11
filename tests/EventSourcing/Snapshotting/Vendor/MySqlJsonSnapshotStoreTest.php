<?php

namespace DDDominio\Tests\EventSourcing\Snapshotting\Vendor;

use DDDominio\EventSourcing\Snapshotting\Vendor\MySqlJsonSnapshotStore;
use Doctrine\Common\Annotations\AnnotationRegistry;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotInterface;
use JMS\Serializer\SerializerBuilder;
use DDDominio\Tests\EventSourcing\TestData\DummyEventSourcedAggregate;
use DDDominio\Tests\EventSourcing\TestData\DummySnapshot;

class MySqlJsonSnapshotStoreTest extends \PHPUnit_Framework_TestCase
{
    const MYSQL_DB_HOST = 'localhost';
    const MYSQL_DB_USER = 'event_sourcing';
    const MYSQL_DB_PASS = 'event_sourcing123';
    const MYSQL_DB_NAME = 'json_snapshot_store';

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function setUp()
    {
        $this->connection = new \PDO(
            'mysql:host=' . self::MYSQL_DB_HOST,
            self::MYSQL_DB_USER,
            self::MYSQL_DB_PASS
        );
        $this->connection->query('DROP SCHEMA IF EXISTS '.self::MYSQL_DB_NAME);
        $this->connection->query('CREATE SCHEMA '.self::MYSQL_DB_NAME);
        $this->connection->query('USE '.self::MYSQL_DB_NAME);
        $this->connection->exec(
            file_get_contents(__DIR__ . '/../../TestData/mysql_json_snapshot_store_schema.sql')
        );

        AnnotationRegistry::registerLoader('class_exists');

        $this->serializer = new JsonSerializer(
            SerializerBuilder::create()
                ->addMetadataDir(
                    __DIR__ . '/../../TestData/Serializer',
                    'DDDominio\Tests\EventSourcing\TestData'
                )
                ->addMetadataDir(
                    __DIR__ . '/../../../../src/EventSourcing/Serialization/JmsMapping',
                    'DDDominio\EventSourcing\Common'
                )
                ->build()
        );
    }

    protected function tearDown()
    {
        $this->connection->query('DROP SCHEMA '.self::MYSQL_DB_NAME);
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
        $eventStore = new MySqlJsonSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->addSnapshot($snapshot);
        $eventStore->addSnapshot($lastSnapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot(
            DummyEventSourcedAggregate::class,
            'id'
        );

        $this->assertInstanceOf(SnapshotInterface::class, $retrievedSnapshot);
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
        $this->assertInstanceOf(SnapshotInterface::class, $retrievedSnapshot);
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
