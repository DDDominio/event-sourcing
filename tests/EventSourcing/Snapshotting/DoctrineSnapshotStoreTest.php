<?php

namespace DDDominio\Tests\EventSourcing\Snapshotting;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Serialization\Serializer;
use DDDominio\EventSourcing\Snapshotting\DoctrineSnapshotStore;
use DDDominio\EventSourcing\Snapshotting\Snapshot;
use JMS\Serializer\SerializerBuilder;
use DDDominio\Tests\EventSourcing\Common\TestData\DummyEventSourcedAggregate;
use DDDominio\Tests\EventSourcing\Common\TestData\DummySnapshot;

class DoctrineSnapshotStoreTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DB_PATH = __DIR__ . '/../test.db';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        touch(self::TEST_DB_PATH);
        $connectionParams = array(
            'path' => self::TEST_DB_PATH,
            'host' => 'localhost',
            'driver' => 'pdo_sqlite',
        );
        $config = new Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);
        $this->connection->exec(
            file_get_contents(__DIR__ . '/../dbal_event_store_schema.sql')
        );

        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation',
            __DIR__ . '/../../../vendor/jms/serializer/src'
        );
        $this->serializer = new JsonSerializer(
            SerializerBuilder::create()->build()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (file_exists(self::TEST_DB_PATH)) {
            unlink(self::TEST_DB_PATH);
        }
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
        $snapshotStore = new DoctrineSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $snapshotStore->addSnapshot($snapshot);
        $snapshotStore->addSnapshot($lastSnapshot);

        $retrievedSnapshot = $snapshotStore->findLastSnapshot(
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
        $snapshotStore = new DoctrineSnapshotStore(
            $this->connection,
            $this->serializer
        );

        $snapshotStore->addSnapshot($snapshot);

        $retrievedSnapshot = $snapshotStore->findLastSnapshot(
            DummyEventSourcedAggregate::class,
            'id'
        );
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals('id', $retrievedSnapshot->id());
        $this->assertEquals('name', $retrievedSnapshot->name());
        $this->assertEquals('description', $retrievedSnapshot->description());
        $this->assertEquals(3, $retrievedSnapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForEventVersion()
    {
        $snapshotStore = new DoctrineSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $snapshotStore->addSnapshot(
            new DummySnapshot('id', 'new name', 'description', 2)
        );
        $snapshotStore->addSnapshot(
            new DummySnapshot('id', 'another name', 'new description', 4)
        );

        $snapshot = $snapshotStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

        $this->assertEquals(2, $snapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForAnotherEventVersion()
    {
        $snapshotStore = new DoctrineSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $snapshotStore->addSnapshot(
            new DummySnapshot('id', 'new name', 'description', 2)
        );
        $snapshotStore->addSnapshot(
            new DummySnapshot('id', 'another name', 'new description', 4)
        );

        $snapshot = $snapshotStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
    }
}
