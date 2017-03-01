<?php

namespace DDDominio\Tests\EventSourcing\Common;

use DDDominio\EventSourcing\Common\Annotation\AggregateId;
use DDDominio\EventSourcing\Common\AnnotationAggregateIdExtractor;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationAggregateIdExtractorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        AnnotationRegistry::registerLoader('class_exists');
    }

    /**
     * @test
     */
    public function extractIdUsingMethodAnnotation()
    {
        $aggregate = new AnnotationAggregate();
        $aggregateIdExtractor = new AnnotationAggregateIdExtractor();

        $id = $aggregateIdExtractor->extract($aggregate);

        $this->assertEquals('code', $id);
    }

    /**
     * @test
     */
    public function extractIdOfAnotherAggregateUsingMethodAnnotation()
    {
        $aggregate = new AnotherAnnotationAggregate();
        $aggregateIdExtractor = new AnnotationAggregateIdExtractor();

        $id = $aggregateIdExtractor->extract($aggregate);

        $this->assertEquals('name', $id);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\Common\AggregateIdNotFoundException
     */
    public function ifIdCannotBeExtractedAnExceptionIsThrown()
    {
        $aggregate = new \stdClass();
        $aggregateIdExtractor = new AnnotationAggregateIdExtractor();

        $aggregateIdExtractor->extract($aggregate);
    }
}

class AnnotationAggregate
{
    /**
     * @AggregateId()
     */
    public function code()
    {
        return 'code';
    }

    public function name()
    {
        return 'name';
    }
}

class AnotherAnnotationAggregate
{
    public function code()
    {
        return 'code';
    }

    /**
     * @AggregateId()
     */
    public function name()
    {
        return 'name';
    }
}
