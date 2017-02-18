<?php

namespace DDDominio\EventSourcing\Snapshotting;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRootInterface;

abstract class ReflectionSnapshotTranslator implements SnapshotTranslatorInterface
{
    /**
     * @return string
     */
    abstract protected function aggregateClass();

    /**
     * @return string
     */
    abstract protected function snapshotClass();

    /**
     * @return array
     */
    abstract protected function aggregateToSnapshotPropertyDictionary();

    /**
     * @param EventSourcedAggregateRootInterface $aggregate
     * @return object
     */
    public function buildSnapshotFromAggregate($aggregate)
    {
        $snapshotClass = $this->snapshotClass();
        $snapshot = (new \ReflectionClass($snapshotClass))->newInstanceWithoutConstructor();
        $reflectedClass = new \ReflectionClass($snapshotClass);

        $dictionary = $this->aggregateToSnapshotPropertyDictionary();

        foreach ($dictionary as $aggregateProperty => $snapshotProperty) {
            $nameProperty = $reflectedClass->getProperty($snapshotProperty);
            $nameProperty->setAccessible(true);
            $nameProperty->setValue($snapshot, $aggregate->$aggregateProperty());
        }

        return $snapshot;
    }

    /**
     * @param SnapshotInterface $snapshot
     * @return object
     */
    public function buildAggregateFromSnapshot($snapshot)
    {
        $aggregateClass = $this->aggregateClass();
        $aggregate = (new \ReflectionClass($aggregateClass))->newInstanceWithoutConstructor();
        $reflectedClass = new \ReflectionClass($aggregateClass);

        $dictionary = $this->aggregateToSnapshotPropertyDictionary();
        unset($dictionary['version']);
        $this->setPropertyValue('version', $snapshot->version(), $aggregate, $reflectedClass->getParentClass());

        foreach ($dictionary as $aggregateProperty => $snapshotProperty) {
            $this->setPropertyValue(
                $aggregateProperty,
                $snapshot->$snapshotProperty(),
                $aggregate,
                $reflectedClass
            );
        }

        return $aggregate;
    }

    /**
     * @param string $propertyName
     * @param string $propertyValue
     * @param object $aggregate
     * @param \ReflectionClass $reflectedClass
     */
    private function setPropertyValue($propertyName, $propertyValue, $aggregate, $reflectedClass)
    {
        $nameProperty = $reflectedClass->getProperty($propertyName);
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($aggregate, $propertyValue);
    }
}
