<?php
namespace TYPO3\CMS\Install\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class CoreVersionServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function updateVersionMatrixStoresVersionMatrixInRegistry()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['fetchVersionMatrixFromRemote'], [], '', false);
        $registry = $this->getMock(Registry::class);
        $versionArray = [7 => []];
        $registry->expects($this->once())->method('set')->with('TYPO3.CMS.Install', 'coreVersionMatrix', $versionArray);
        $instance->_set('registry', $registry);
        $instance->expects($this->once())->method('fetchVersionMatrixFromRemote')->will($this->returnValue($versionArray));
        $instance->updateVersionMatrix();
    }

    /**
     * @test
     */
    public function updateVersionMatrixRemovesOldReleasesFromMatrix()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['fetchVersionMatrixFromRemote'], [], '', false);
        $registry = $this->getMock(Registry::class);
        $versionArray = [
            '7' => [],
            '6.2' => [],
        ];
        $registry
            ->expects($this->once())
            ->method('set')
            ->with('TYPO3.CMS.Install', 'coreVersionMatrix', $this->logicalNot($this->arrayHasKey('6.2')));
        $instance->_set('registry', $registry);
        $instance->expects($this->once())->method('fetchVersionMatrixFromRemote')->will($this->returnValue($versionArray));
        $instance->updateVersionMatrix();
    }

    /**
     * @test
     */
    public function isInstalledVersionAReleasedVersionReturnsTrueForNonDevelopmentVersion()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertTrue($instance->isInstalledVersionAReleasedVersion());
    }

    /**
     * @test
     */
    public function isInstalledVersionAReleasedVersionReturnsFalseForDevelopmentVersion()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('7.4-dev'));
        $this->assertFalse($instance->isInstalledVersionAReleasedVersion());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
     */
    public function getTarGzSha1OfVersionThrowsExceptionIfSha1DoesNotExistInMatrix()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getMajorVersion', 'ensureVersionExistsInMatrix'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.2.0' => [],
                ],
            ],
        ];
        $instance->expects($this->once())->method('getMajorVersion')->will($this->returnValue('7'));
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $this->assertTrue($instance->getTarGzSha1OfVersion('7.2.0'));
    }

    /**
     * @test
     */
    public function getTarGzSha1OfVersionReturnsSha1OfSpecifiedVersion()
    {
        $versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getMajorVersion', 'ensureVersionExistsInMatrix'],
            [],
            '',
            false
        );
        $instance->expects($this->any())->method('getMajorVersion')->will($this->returnValue('7'));
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
        $this->assertSame('3dc156eed4b99577232f537d798a8691493f8a83', $instance->getTarGzSha1OfVersion('7.2.0'));
    }

    /**
     * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
     *
     * @test
     */
    public function isYoungerPatchReleaseAvailableReturnsTrueIfYoungerReleaseIsAvailable()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getInstalledVersion'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.2.1' => [
                        'type' => 'security',
                        'date' => '2013-12-01 18:24:25 UTC',
                    ],
                    '7.2.0' => [
                        'type' => 'regular',
                        'date' => '2013-11-01 18:24:25 UTC',
                    ],
                ],
            ],
        ];
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertTrue($instance->isYoungerPatchReleaseAvailable());
    }

    /**
     * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
     *
     * @test
     */
    public function isYoungerReleaseAvailableReturnsFalseIfNoYoungerReleaseExists()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getInstalledVersion'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.2.0' => [
                        'type' => 'regular',
                        'date' => '2013-12-01 18:24:25 UTC',
                    ],
                    '7.1.0' => [
                        'type' => 'regular',
                        'date' => '2013-11-01 18:24:25 UTC',
                    ],
                ],
            ],
        ];
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertFalse($instance->isYoungerPatchReleaseAvailable());
    }

    /**
     * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
     *
     * @test
     */
    public function isYoungerReleaseAvailableReturnsFalseIfOnlyADevelopmentReleaseIsYounger()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getInstalledVersion'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.3.0' => [
                        'type' => 'development',
                        'date' => '2013-12-01 18:24:25 UTC',
                    ],
                    '7.2.0' => [
                        'type' => 'regular',
                        'date' => '2013-11-01 18:24:25 UTC',
                    ],
                ],
            ],
        ];
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertFalse($instance->isYoungerPatchReleaseAvailable());
    }

    /**
     * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
     *
     * @test
     */
    public function isYoungerDevelopmentReleaseAvailableReturnsTrueIfADevelopmentReleaseIsYounger()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getInstalledVersion'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.3.0' => [
                        'type' => 'development',
                        'date' => '2013-12-01 18:24:25 UTC',
                    ],
                    '7.2.0' => [
                        'type' => 'regular',
                        'date' => '2013-11-01 18:24:25 UTC',
                    ],
                ],
            ],
        ];
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertTrue($instance->isYoungerPatchDevelopmentReleaseAvailable());
    }

    /**
     * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
     *
     * @test
     */
    public function isUpdateSecurityRelevantReturnsTrueIfAnUpdateIsSecurityRelevant()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getInstalledVersion'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.3.0' => [
                        'type' => 'security',
                        'date' => '2013-12-01 18:24:25 UTC',
                    ],
                    '7.2.0' => [
                        'type' => 'regular',
                        'date' => '2013-11-01 18:24:25 UTC',
                    ],
                ],
            ],
        ];
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertTrue($instance->isUpdateSecurityRelevant());
    }

    /**
     * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
     *
     * @test
     */
    public function isUpdateSecurityRelevantReturnsFalseIfUpdateIsNotSecurityRelevant()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getInstalledVersion'],
            [],
            '',
            false
        );
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.3.0' => [
                        'type' => 'regular',
                        'date' => '2013-12-01 18:24:25 UTC',
                    ],
                    '7.2.0' => [
                        'type' => 'regular',
                        'date' => '2013-11-01 18:24:25 UTC',
                    ],
                ],
            ],
        ];
        $instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertFalse($instance->isUpdateSecurityRelevant());
    }

    /**
     * @test
     */
    public function getInstalledMajorVersionFetchesInstalledVersionNumber()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('7.2.0'));
        $this->assertSame('7', $instance->_call('getInstalledMajorVersion'));
    }

    /**
     * Data provider
     */
    public function getMajorVersionDataProvider()
    {
        return [
            '7.2' => [
                '7.2.0',
                '7',
            ],
            '7.4-dev' => [
                '7.4-dev',
                '7',
            ],
            '4.5' => [
                '4.5.40',
                '4',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getMajorVersionDataProvider
     * @param string $version
     * @param string $expectedMajor
     * @throws \InvalidArgumentException
     */
    public function getMajorVersionReturnsCorrectMajorVersion($version, $expectedMajor)
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['dummy'], [], '', false);
        $this->assertSame($expectedMajor, $instance->_call('getMajorVersion', $version));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
     */
    public function getVersionMatrixThrowsExceptionIfVersionMatrixIsNotYetSetInRegistry()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['fetchVersionMatrixFromRemote'], [], '', false);
        $registry = $this->getMock(Registry::class);
        $registry->expects($this->once())->method('get')->will($this->returnValue(null));
        $instance->_set('registry', $registry);
        $instance->_call('getVersionMatrix');
    }

    /**
     * @test
     */
    public function getVersionMatrixReturnsMatrixFromRegistry()
    {
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['fetchVersionMatrixFromRemote'], [], '', false);
        $registry = $this->getMock(Registry::class);
        $versionArray = [$this->getUniqueId()];
        $registry->expects($this->once())->method('get')->will($this->returnValue($versionArray));
        $instance->_set('registry', $registry);
        $this->assertSame($versionArray, $instance->_call('getVersionMatrix'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
     */
    public function getReleaseTimestampOfVersionThrowsExceptionIfReleaseDateIsNotDefined()
    {
        $versionMatrix = [
            '7' => [
                'releases' => [
                    '7.2.0' => []
                ],
            ],
        ];
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getMajorVersion', 'ensureVersionExistsInMatrix'],
            [],
            '',
            false
        );
        $instance->expects($this->once())->method('getMajorVersion')->will($this->returnValue('7'));
        $instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
        $instance->_call('getReleaseTimestampOfVersion', '7.2.0');
    }

    /**
     * @test
     */
    public function getReleaseTimestampOfVersionReturnsTimestamp()
    {
        $versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getMajorVersion', 'ensureVersionExistsInMatrix'],
            [],
            '',
            false
        );
        $instance->expects($this->once())->method('getMajorVersion')->will($this->returnValue('7'));
        $instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
        $this->assertSame(1398968665, $instance->_call('getReleaseTimestampOfVersion', '7.3.1'));
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
     */
    public function ensureVersionExistsInMatrixThrowsExceptionIfMinorVersionDoesNotExist()
    {
        $versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getMajorVersion'],
            [],
            '',
            false
        );
        $instance->expects($this->once())->method('getMajorVersion')->will($this->returnValue('2'));
        $instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
        $instance->_call('ensureVersionExistsInMatrix', '2.0.42');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
     */
    public function ensureVersionExistsInMatrixThrowsExceptionIfPatchLevelDoesNotExist()
    {
        $versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
        /** @var $instance CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $instance = $this->getAccessibleMock(
            CoreVersionService::class,
            ['getVersionMatrix', 'getMajorVersion'],
            [],
            '',
            false
        );
        $instance->expects($this->once())->method('getMajorVersion')->will($this->returnValue('7'));
        $instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
        $instance->_call('ensureVersionExistsInMatrix', '7.2.5');
    }
}
