<?php

namespace EventSourcing\Versioning\JsonTransformer;

class ObjectAccessToken
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @param string $fieldName
     */
    public function __construct($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function fieldName()
    {
        return $this->fieldName;
    }
}
