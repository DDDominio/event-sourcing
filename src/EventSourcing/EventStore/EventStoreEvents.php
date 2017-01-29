<?php

namespace DDDominio\EventSourcing\EventStore;

final class EventStoreEvents
{
    const PRE_APPEND = 'event_store.pre_append';

    const POST_APPEND = 'event_store.post_append';
    
    private function __construct()
    {
    }
}
