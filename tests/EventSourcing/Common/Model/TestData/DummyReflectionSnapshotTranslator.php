<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Snapshotting\ReflectionSnapshotTranslator;

class DummyReflectionSnapshotTranslator extends ReflectionSnapshotTranslator
{
    /**
     * @return string
     */
    public function aggregateClass()
    {
        return DummyEventSourcedAggregate::class;
    }

    /**
     * @return string
     */
    public function snapshotClass()
    {
        return DummySnapshot::class;
    }

    /**
     * @return array
     */
    public function aggregateToSnapshotPropertyDictionary()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'description' => 'description',
            'version' => 'version'
        ];
    }
}