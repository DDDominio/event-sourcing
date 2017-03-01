<?php

namespace DDDominio\EventSourcing\Common;

class MethodAggregateIdExtractor implements AggregateIdExtractorInterface
{
    /**
     * @var string
     */
    private $method;

    /**
     * @param string $method
     */
    public function __construct($method)
    {
        $this->method = $method;
    }

    /**
     * @param object $aggregate
     * @return string
     */
    public function extract($aggregate)
    {
        if (!method_exists($aggregate, $this->method)) {
            throw new AggregateIdNotFoundException(sprintf('No method "%s" found in %s', $this->method, get_class($aggregate)));
        }
        return (string) $aggregate->{$this->method}();
    }
}
