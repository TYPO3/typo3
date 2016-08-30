<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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

/**
 * Test for DependencyUtility
 *
 */
class DependencyUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManagerMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerMock = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
    }

    /**
     * @test
     * @return void
     */
    public function checkTypo3DependencyThrowsExceptionIfVersionNumberIsTooLow()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->setIdentifier('typo3');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->setExpectedException(\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException::class, '', 1399144499);
        $dependencyUtility->_call('checkTypo3Dependency', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkTypo3DependencyThrowsExceptionIfVersionNumberIsTooHigh()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('3.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyMock->setIdentifier('typo3');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->setExpectedException(\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException::class, '', 1399144521);
        $dependencyUtility->_call('checkTypo3Dependency', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkTypo3DependencyThrowsExceptionIfIdentifierIsNotTypo3()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->setIdentifier('123');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->setExpectedException(\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException::class, '', 1399144551);
        $dependencyUtility->_call('checkTypo3Dependency', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkTypo3DependencyReturnsTrueIfVersionNumberIsInRange()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyMock->setIdentifier('typo3');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function checkTypo3DependencyCanHandleEmptyVersionHighestVersion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue(''));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyMock->setIdentifier('typo3');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function checkTypo3DependencyCanHandleEmptyVersionLowestVersion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue(''));
        $dependencyMock->setIdentifier('typo3');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('checkTypo3Dependency', $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function checkPhpDependencyThrowsExceptionIfVersionNumberIsTooLow()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->setIdentifier('php');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->setExpectedException(\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException::class, '', 1377977857);
        $dependencyUtility->_call('checkPhpDependency', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkPhpDependencyThrowsExceptionIfVersionNumberIsTooHigh()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('3.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyMock->setIdentifier('php');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->setExpectedException(\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException::class, '', 1377977856);
        $dependencyUtility->_call('checkPhpDependency', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkPhpDependencyThrowsExceptionIfIdentifierIsNotTypo3()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->setIdentifier('123');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->setExpectedException(\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException::class, '', 1377977858);
        $dependencyUtility->_call('checkPhpDependency', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkPhpDependencyReturnsTrueIfVersionNumberIsInRange()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyMock->setIdentifier('php');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('checkPhpDependency', $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function checkPhpDependencyCanHandleEmptyVersionHighestVersion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue(''));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyMock->setIdentifier('php');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('checkPhpDependency', $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function checkPhpDependencyCanHandleEmptyVersionLowestVersion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue(''));
        $dependencyMock->setIdentifier('php');
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('checkPhpDependency', $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function checkDependenciesCallsMethodToCheckPhpDependencies()
    {
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock */
        $extensionMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->setIdentifier('php');
        $dependencyStorage = new \SplObjectStorage();
        $dependencyStorage->attach($dependencyMock);
        $extensionMock->setDependencies($dependencyStorage);
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility */
        $dependencyUtility = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['checkPhpDependency', 'checkTypo3Dependency']);
        $dependencyUtility->expects($this->atLeastOnce())->method('checkPhpDependency');
        $dependencyUtility->checkDependencies($extensionMock);
    }

    /**
     * @test
     * @return void
     */
    public function checkDependenciesCallsMethodToCheckTypo3Dependencies()
    {
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extensionMock */
        $extensionMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, ['dummy']);
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->setIdentifier('typo3');
        $dependencyStorage = new \SplObjectStorage();
        $dependencyStorage->attach($dependencyMock);
        $extensionMock->setDependencies($dependencyStorage);
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility $dependencyUtility */
        $dependencyUtility = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['checkPhpDependency', 'checkTypo3Dependency']);

        $dependencyUtility->expects($this->atLeastOnce())->method('checkTypo3Dependency');
        $dependencyUtility->checkDependencies($extensionMock);
    }

    /**
     * @test
     * @return void
     */
    public function isVersionCompatibleReturnsTrueForCompatibleVersion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('15.0.0'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $version = '3.3.3';
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertTrue($dependencyUtility->_call('isVersionCompatible', $version, $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function isVersionCompatibleReturnsFalseForIncompatibleVersion()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency $dependencyMock */
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->atLeastOnce())->method('getHighestVersion')->will($this->returnValue('1.0.1'));
        $dependencyMock->expects($this->atLeastOnce())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $version = '3.3.3';
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);

        $this->assertFalse($dependencyUtility->_call('isVersionCompatible', $version, $dependencyMock));
    }

    /**
     * @test
     * @return void
     */
    public function isDependentExtensionAvailableReturnsTrueIfExtensionIsAvailable()
    {
        $availableExtensions = [
            'dummy' => [],
            'foo' => [],
            'bar' => []
        ];
        $listUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class, ['getAvailableExtensions']);
        $listUtilityMock->expects($this->atLeastOnce())->method('getAvailableExtensions')->will($this->returnValue($availableExtensions));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('listUtility', $listUtilityMock);

        $this->assertTrue($dependencyUtility->_call('isDependentExtensionAvailable', 'dummy'));
    }

    /**
     * @test
     * @return void
     */
    public function isDependentExtensionAvailableReturnsFalseIfExtensionIsNotAvailable()
    {
        $availableExtensions = [
            'dummy' => [],
            'foo' => [],
            'bar' => []
        ];
        $listUtilityMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class, ['getAvailableExtensions']);
        $listUtilityMock->expects($this->atLeastOnce())->method('getAvailableExtensions')->will($this->returnValue($availableExtensions));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('listUtility', $listUtilityMock);

        $this->assertFalse($dependencyUtility->_call('isDependentExtensionAvailable', '42'));
    }

    /**
     * @test
     * @return void
     */
    public function isAvailableVersionCompatibleCallsIsVersionCompatibleWithExtensionVersion()
    {
        $emConfUtility = $this->getMock(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility::class, ['includeEmConf']);
        $emConfUtility->expects($this->once())->method('includeEmConf')->will($this->returnValue([
            'key' => 'dummy',
            'version' => '1.0.0'
        ]));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['setAvailableExtensions', 'isVersionCompatible']);
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getIdentifier']);
        $dependencyMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('dummy'));
        $dependencyUtility->_set('emConfUtility', $emConfUtility);
        $dependencyUtility->_set('availableExtensions', [
            'dummy' => [
                'foo' => '42'
            ]
        ]);
        $dependencyUtility->expects($this->once())->method('setAvailableExtensions');
        $dependencyUtility->expects($this->once())->method('isVersionCompatible')->with('1.0.0', $this->anything());
        $dependencyUtility->_call('isAvailableVersionCompatible', $dependencyMock);
    }

    /**
     * @test
     * @return void
     */
    public function isExtensionDownloadableFromTerReturnsTrueIfOneVersionExists()
    {
        $extensionRepositoryMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, ['countByExtensionKey'], [$this->objectManagerMock]);
        $extensionRepositoryMock->expects($this->once())->method('countByExtensionKey')->with('test123')->will($this->returnValue(1));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromTer', 'test123');

        $this->assertTrue($count);
    }

    /**
     * @test
     * @return void
     */
    public function isExtensionDownloadableFromTerReturnsFalseIfNoVersionExists()
    {
        $extensionRepositoryMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, ['countByExtensionKey'], [$this->objectManagerMock]);
        $extensionRepositoryMock->expects($this->once())->method('countByExtensionKey')->with('test123')->will($this->returnValue(0));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromTer', 'test123');

        $this->assertFalse($count);
    }

    /**
     * @test
     * @return void
     */
    public function isDownloadableVersionCompatibleReturnsTrueIfCompatibleVersionExists()
    {
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getIdentifier', 'getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('dummy'));
        $dependencyMock->expects($this->once())->method('getHighestVersion')->will($this->returnValue('10.0.0'));
        $dependencyMock->expects($this->once())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $extensionRepositoryMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, ['countByVersionRangeAndExtensionKey'], [$this->objectManagerMock]);
        $extensionRepositoryMock->expects($this->once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 10000000)->will($this->returnValue(2));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependencyMock);

        $this->assertTrue($count);
    }

    /**
     * @test
     * @return void
     */
    public function isDownloadableVersionCompatibleReturnsFalseIfIncompatibleVersionExists()
    {
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getIdentifier']);
        $dependencyMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('dummy'));
        $extensionRepositoryMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, ['countByVersionRangeAndExtensionKey'], [$this->objectManagerMock]);
        $extensionRepositoryMock->expects($this->once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 2000000)->will($this->returnValue(0));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['getLowestAndHighestIntegerVersions']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $dependencyUtility->expects($this->once())->method('getLowestAndHighestIntegerVersions')->will($this->returnValue([
            'lowestIntegerVersion' => 1000000,
            'highestIntegerVersion' => 2000000
        ]));
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependencyMock);

        $this->assertFalse($count);
    }

    /**
     * @test
     * @return void
     */
    public function getLowestAndHighestIntegerVersionsReturnsArrayWithVersions()
    {
        $expectedVersions = [
            'lowestIntegerVersion' => 1000000,
            'highestIntegerVersion' => 2000000
        ];

        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getHighestVersion', 'getLowestVersion']);
        $dependencyMock->expects($this->once())->method('getHighestVersion')->will($this->returnValue('2.0.0'));
        $dependencyMock->expects($this->once())->method('getLowestVersion')->will($this->returnValue('1.0.0'));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['dummy']);
        $versions = $dependencyUtility->_call('getLowestAndHighestIntegerVersions', $dependencyMock);

        $this->assertSame($expectedVersions, $versions);
    }

    /**
     * @test
     * @return void
     */
    public function getLatestCompatibleExtensionByIntegerVersionDependencyWillReturnExtensionModelOfLatestExtension()
    {
        $extension1 = new \TYPO3\CMS\Extensionmanager\Domain\Model\Extension();
        $extension1->setExtensionKey('foo');
        $extension1->setVersion('1.0.0');
        $extension2 = new \TYPO3\CMS\Extensionmanager\Domain\Model\Extension();
        $extension2->setExtensionKey('bar');
        $extension2->setVersion('1.0.42');

        $myStorage = new \TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures\LatestCompatibleExtensionObjectStorageFixture();
        $myStorage->extensions[] = $extension1;
        $myStorage->extensions[] = $extension2;
        $dependencyMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Model\Dependency::class, ['getIdentifier']);
        $dependencyMock->expects($this->once())->method('getIdentifier')->will($this->returnValue('foobar'));
        $dependencyUtility = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\DependencyUtility::class, ['getLowestAndHighestIntegerVersions']);
        $dependencyUtility->expects($this->once())->method('getLowestAndHighestIntegerVersions')->will($this->returnValue([
            'lowestIntegerVersion' => 1000000,
            'highestIntegerVersion' => 2000000
        ]));
        $extensionRepositoryMock = $this->getMock(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class, ['findByVersionRangeAndExtensionKeyOrderedByVersion'], [$this->objectManagerMock]);
        $extensionRepositoryMock->expects($this->once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('foobar', 1000000, 2000000)->will($this->returnValue($myStorage));
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $extension = $dependencyUtility->_call('getLatestCompatibleExtensionByIntegerVersionDependency', $dependencyMock);

        $this->assertInstanceOf(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class, $extension);
        $this->assertSame('foo', $extension->getExtensionKey());
    }
}
