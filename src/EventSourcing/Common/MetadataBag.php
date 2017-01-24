<?php

namespace DDDominio\EventSourcing\Common;

class MetadataBag
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @param array $metadata
     */
    public function __construct(array $metadata = [])
    {
        foreach ($metadata as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        return isset($this->metadata[$key]) ?
            $this->metadata[$key] : null;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->metadata;
    }
}
