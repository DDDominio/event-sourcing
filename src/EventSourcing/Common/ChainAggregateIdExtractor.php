<?php

namespace DDDominio\EventSourcing\Common;

class ChainAggregateIdExtractor implements AggregateIdExtractorInterface
{
    /**
     * @var AggregateIdExtractorInterface[]
     */
    private $extractors = [];

    /**
     * @param AggregateIdExtractorInterface $aggregateIdExtractor
     */
    public function add(AggregateIdExtractorInterface $aggregateIdExtractor)
    {
        $this->extractors[] = $aggregateIdExtractor;
    }

    /**
     * @param object $aggregate
     * @return string
     */
    public function extract($aggregate)
    {
        foreach ($this->extractors as $extractor) {
            try {
                return $extractor->extract($aggregate);
            } catch (AggregateIdNotFoundException $e) {
            }
        }
        throw new AggregateIdNotFoundException(sprintf('Id cannot be extracted from %s', get_class($aggregate)));
    }
}
