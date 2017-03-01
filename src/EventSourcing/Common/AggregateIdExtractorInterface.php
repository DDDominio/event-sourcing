<?php

namespace DDDominio\EventSourcing\Common;

interface AggregateIdExtractorInterface
{
    /**
     * @param object $aggregate
     * @return string
     * @throws AggregateIdNotFoundException
     */
    public function extract($aggregate);
}
