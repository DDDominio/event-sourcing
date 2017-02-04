<?php

namespace DDDominio\EventSourcing\Versioning\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Version
{
    /**
     * @var string
     * @Required
     */
    public $version;
}
