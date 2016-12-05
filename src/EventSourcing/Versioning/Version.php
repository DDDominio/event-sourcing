<?php

namespace EventSourcing\Versioning;

class Version
{
    /**
     * @var int
     */
    private $major;

    /**
     * @var int
     */
    private $minor;

    /**
     * @param int $major
     * @param int $minor
     */
    public function __construct($major, $minor)
    {
        if ($major < 1 || $minor < 0) {
            throw new \InvalidArgumentException('Invalid version.');
        }
        $this->major = $major;
        $this->minor = $minor;
    }

    /**
     * @param $version
     * @return Version
     */
    public static function fromString($version)
    {
        $versionPattern = '/(\d+)\.(\d+)/';
        if (preg_match($versionPattern, $version, $matches) !== 1) {
            throw new \InvalidArgumentException('Invalid version format.');
        }
        return new self((int)$matches[1], (int)$matches[2]);
    }

    /**
     * @return int
     */
    public function major()
    {
        return $this->major;
    }

    /**
     * @return int
     */
    public function minor()
    {
        return $this->minor;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->major() . '.' . $this->minor();
    }

    /**
     * @param Version $version
     * @return bool
     */
    public function equalTo($version)
    {
        return $this->major() === $version->major() &&
            $this->minor() === $version->minor();
    }

    /**
     * @param Version $version
     * @return bool
     */
    public function greaterThan($version)
    {
        return $this->major() > $version->major() ||
            $this->major() == $version->major() && $this->minor() > $version->minor();
    }
}