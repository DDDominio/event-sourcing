<?php

namespace DDDominio\Tests\EventSourcing\Snapshotting\Vendor;

use DDDominio\EventSourcing\Snapshotting\Vendor\DoctrineDbalSnapshotStore;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotInterface;
use JMS\Serializer\SerializerBuilder;
use DDDominio\Tests\EventSourcing\TestData\DummyEventSourcedAggregate;
use DDDominio\Tests\EventSourcing\TestData\DummySnapshot;

class DoctrineSnapshotStoreTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DB_PATH = __DIR__ . '/../test.db';

    /**
     * @var DoctrineDbalSnapshotStore
     */
    private $snapshotStore;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
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
        $this->createTestDatabase();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->destroySnapshotStore();
    }

    private function createTestDatabase()
    {
        touch(self::TEST_DB_PATH);
        $connectionParams = array(
            'path' => self::TEST_DB_PATH,
            'host' => 'localhost',
            'driver' => 'pdo_sqlite',
        );
        $config = new Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);
        $this->snapshotStore = new DoctrineDbalSnapshotStore(
            $this->connection,
            $this->serializer
        );
        $this->snapshotStore->initialize();
    }

    private function destroySnapshotStore()
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
}
