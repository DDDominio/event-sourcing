<?php

namespace DDDominio\Tests\EventSourcing\Versioning\Annotation;

use DDDominio\EventSourcing\Versioning\Annotation\Version;
use DDDominio\Tests\EventSourcing\TestData\NameChanged;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        AnnotationRegistry::registerLoader('class_exists');
    }

    /**
     * @test
     */
    public function readVersionAnnotation()
    {
        $class = new \ReflectionClass(NameChanged::class);
        $annotationReader = new AnnotationReader();

        $annotation = $annotationReader->getClassAnnotation($class, Version::class);

        $this->assertEquals('3.0', $annotation->version);
    }
}