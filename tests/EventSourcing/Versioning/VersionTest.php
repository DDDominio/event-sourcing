<?php

namespace DDDominio\Tests\EventSourcing\Versioning;

use DDDominio\EventSourcing\Versioning\Version;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function makeAVersion()
    {
        $version = new Version(1, 0);

        $this->assertSame(1, $version->major());
        $this->assertSame(0, $version->minor());
    }

    /**
     * @test
     */
    public function makeAnotherVersion()
    {
        $version = new Version(2, 5);

        $this->assertSame(2, $version->major());
        $this->assertSame(5, $version->minor());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function majorVersionShouldBeGreaterThanZero()
    {
        new Version(0, 5);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function minorVersionShouldBeGreaterOrEqualToZero()
    {
        new Version(1, -1);
    }

    /**
     * @test
     */
    public function makeVersionFromString()
    {
        $version = Version::fromString('1.0');

        $this->assertSame(1, $version->major());
        $this->assertSame(0, $version->minor());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidVersionProvider
     */
    public function invalidVersionsShouldThrowExceptions($version)
    {
        Version::fromString($version);
    }

    public function invalidVersionProvider()
    {
        return [
            ['X.1'], ['1.X'], ['X.X'], ['1.0.0'], ['1'], ['1:2']
        ];
    }

    /**
     * @test
     */
    public function compareTwoEqualVersions()
    {
        $versionA = new Version(1, 0);
        $versionB = new Version(1, 0);

        $this->assertTrue($versionA->equalTo($versionB));
    }

    /**
     * @test
     */
    public function compareTwoVersionsWithDifferentMajor()
    {
        $versionA = new Version(1, 0);
        $versionB = new Version(2, 0);

        $this->assertFalse($versionA->equalTo($versionB));
    }

    /**
     * @test
     */
    public function compareTwoVersionsWithDifferentMinor()
    {
        $versionA = new Version(1, 0);
        $versionB = new Version(1, 2);

        $this->assertFalse($versionA->equalTo($versionB));
    }

    /**
     * @test
     */
    public function aVersionIsGreaterThanOtherIfHasGreaterMajorNumber()
    {
        $versionA = new Version(2, 0);
        $versionB = new Version(1, 5);

        $this->assertTrue($versionA->greaterThan($versionB));
        $this->assertFalse($versionB->greaterThan($versionA));
    }

    /**
     * @test
     */
    public function aVersionIsGreaterThanOtherIfHavingSameMajorNumberHasGreaterMinorNumber()
    {
        $versionA = new Version(1, 5);
        $versionB = new Version(1, 4);

        $this->assertTrue($versionA->greaterThan($versionB));
        $this->assertFalse($versionB->greaterThan($versionA));
    }
}
