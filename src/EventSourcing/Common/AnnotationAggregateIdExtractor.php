<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\EventSourcing\Common\Annotation\AggregateId;
use Doctrine\Common\Annotations\AnnotationReader;

class AnnotationAggregateIdExtractor implements AggregateIdExtractorInterface
{
    /**
     * @param object $aggregate
     * @return string
     */
    public function extract($aggregate)
    {
        $reflection = new \ReflectionClass($aggregate);
        $annotationReader = new AnnotationReader();
        $aggregateIdMethodName = null;
        foreach ($reflection->getMethods() as $reflectionMethod) {
            $annotation = $annotationReader->getMethodAnnotation(
                $reflectionMethod,
                AggregateId::class
            );
            if (!is_null($annotation)) {
                $aggregateIdMethodName = $reflectionMethod->getName();
                break;
            }
        }
        if (is_null($aggregateIdMethodName)) {
            throw new AggregateIdNotFoundException(sprintf('No "@AggregateId" annotation found in %s', get_class($aggregate)));
        }
        return (string) $aggregate->{$aggregateIdMethodName}();
    }
}
