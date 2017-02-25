<?php

namespace tests\EventSourcing\Common;

use DDDominio\EventSourcing\Common\Metadata;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function buildEmptyMetadata()
    {
        $metadata = new Metadata();

        $this->assertCount(0, $metadata->all());
    }

    /**
     * @test
     */
    public function buildMetadataWithInitialData()
    {
        $object = new \stdClass();
        $array = ['key' => 'value'];

        $metadata = new Metadata([
            'integer' => 12345,
            'string' => 'metadata2',
            'object' => $object,
            'array' => $array
        ]);

        $this->assertCount(4, $metadata->all());
        $this->assertEquals(12345, $metadata->get('integer'));
        $this->assertEquals('metadata2', $metadata->get('string'));
        $this->assertEquals($object, $metadata->get('object'));
        $this->assertEquals($array, $metadata->get('array'));
    }

    /**
     * @test
     */
    public function setMetadata()
    {
        $metadata = new Metadata();

        $metadata->set('name', 'value');

        $this->assertEquals('value', $metadata->get('name'));
    }

    /**
     * @test
     */
    public function setExistingMetadataReplaceTheValue()
    {
        $metadata = new Metadata(['name' => 'initial-value']);

        $metadata->set('name', 'new-value');

        $this->assertEquals('new-value', $metadata->get('name'));
    }

    /**
     * @test
     */
    public function getNonExistingMetadataReturnsNull()
    {
        $metadata = new Metadata();

        $value = $metadata->get('non-existing-metadata');

        $this->assertNull($value);
    }

    /**
     * @test
     */
    public function metadataShouldBeIterable()
    {
        $metadata = new Metadata([
            'name1' => 'value1',
            'name2' => 'value2'
        ]);

        $this->assertInstanceOf(\IteratorAggregate::class, $metadata);
        $iterator = $metadata->getIterator();
        $this->assertCount(2, $iterator);
        $this->assertEquals('value1', $iterator->current());
        $iterator->next();
        $this->assertEquals('value2', $iterator->current());
    }

    /**
     * @test
     */
    public function metadataShouldBeCountable()
    {
        $metadata = new Metadata([
            'name1' => 'value1',
            'name2' => 'value2'
        ]);

        $this->assertInstanceOf(\Countable::class, $metadata);
        $this->assertCount(2, $metadata);
    }
}
