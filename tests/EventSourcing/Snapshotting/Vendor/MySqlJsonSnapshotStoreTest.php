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
     * @var MySqlJsonSnapshotStore
     */
    private $snapshotStore;

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
        $this->connection->query('CREATE SCHEMA '.self::MYSQL_DB_NAME);
        $this->connection->query('USE '.self::MYSQL_DB_NAME);

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

        $this->destroySnapshotStore();
        $this->initializeSnapshotStore();
    }

    protected function tearDown()
    {
        $this->destroySnapshotStore();
    }

    private function initializeSnapshotStore()
    {
        $this->connection->query('CREATE SCHEMA '.self::MYSQL_DB_NAME);
        $this->connection->query('USE '.self::MYSQL_DB_NAME);
        $this->snapshotStore = new MySqlJsonSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $this->snapshotStore->initialize();
    }

    private function destroySnapshotStore()
    {
        $this->connection->query('DROP SCHEMA IF EXISTS '.self::MYSQL_DB_NAME);
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
        $this->snapshotStore->addSnapshot($snapshot);
        $this->snapshotStore->addSnapshot($lastSnapshot);

        $retrievedSnapshot = $this->snapshotStore->findLastSnapshot(
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

        $this->snapshotStore->addSnapshot($snapshot);

        $retrievedSnapshot = $this->snapshotStore->findLastSnapshot(
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
        $this->snapshotStore->addSnapshot(
            new DummySnapshot('id', 'new name', 'description', 2)
        );
        $this->snapshotStore->addSnapshot(
            new DummySnapshot('id', 'another name', 'new description', 4)
        );

        $snapshot = $this->snapshotStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

        $this->assertEquals(2, $snapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForAnotherEventVersion()
    {
        $this->snapshotStore->addSnapshot(
            new DummySnapshot('id', 'new name', 'description', 2)
        );
        $this->snapshotStore->addSnapshot(
            new DummySnapshot('id', 'another name', 'new description', 4)
        );

        $snapshot = $this->snapshotStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
    }

    /**
     * @test
     */
    public function initializedEventStore()
    {
        $this->assertTrue($this->snapshotStore->initialized());
    }

    /**
     * @test
     */
    public function notInitializedEventStore()
    {
        $this->connection->exec('DROP TABLE snapshots');

        $this->assertFalse($this->snapshotStore->initialized());
    }

    /**
     * @test
     */
    public function whenEventStoreThrowsAnExceptionItIsConsideredNotInitialized()
    {
        $connection = $this->createMock(\PDO::class);
        $connection->method('query')->willThrowException(new \Exception());
        $this->snapshotStore = new MySqlJsonSnapshotStore(
            $connection,
            $this->serializer
        );

        $this->assertFalse($this->snapshotStore->initialized());
    }
}
