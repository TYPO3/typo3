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

use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test for DependencyUtility
 */
class DependencyUtilityTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManagerMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManagerMock = $this->getMockBuilder(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function checkTypo3DependencyErrorsIfVersionNumberIsTooLow(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('typo3');
        $dependency->setLowestVersion('15.0.0');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertSame(1399144499, $errors['foo'][0]['code']);
    }

    /**
     * @test
     */
    public function checkTypo3DependencyErrorsIfVersionNumberIsTooHigh(): void
    {
        $dependency = new Dependency();
        $dependency->setHighestVersion('3.0.0');
        $dependency->setLowestVersion('1.0.0');
        $dependency->setIdentifier('typo3');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertSame(1399144521, $errors['foo'][0]['code']);
    }

    /**
     * @test
     * @todo this can never happen with current code paths
     */
    public function checkTypo3DependencyThrowsExceptionIfIdentifierIsNotTypo3(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('123');
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1399144551);
        $dependencyUtility->_call('checkTypo3Dependency', $dependency);
    }

    /**
     * @test
     */
    public function checkTypo3DependencyReturnsTrueIfVersionNumberIsInRange(): void
    {
        $dependency = new Dependency();
        $dependency->setHighestVersion('15.0.0');
        $dependency->setLowestVersion('1.0.0');
        $dependency->setIdentifier('typo3');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        $dependency->setIdentifier('typo3');
        self::assertCount(0, $errors);
    }

    /**
     * @test
     */
    public function checkTypo3DependencyCanHandleEmptyVersionHighestVersion(): void
    {
        $dependency = new Dependency();
        $dependency->setHighestVersion('');
        $dependency->setLowestVersion('1.0.0');
        $dependency->setIdentifier('typo3');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        $dependency->setIdentifier('typo3');
        self::assertCount(0, $errors);
    }

    /**
     * @test
     */
    public function checkTypo3DependencyCanHandleEmptyVersionLowestVersion(): void
    {
        $dependency = new Dependency();
        $dependency->setHighestVersion('15.0.0');
        $dependency->setLowestVersion('');
        $dependency->setIdentifier('typo3');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        $dependency->setIdentifier('typo3');
        self::assertCount(0, $errors);
    }

    /**
     * @test
     */
    public function checkPhpDependencyErrorsIfVersionNumberIsTooLow(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('php');
        $dependency->setLowestVersion('15.0.0');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertSame(1377977857, $errors['foo'][0]['code']);
    }

    /**
     * @test
     */
    public function checkPhpDependencyThrowsExceptionIfVersionNumberIsTooHigh(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('php');
        $dependency->setHighestVersion('3.0.0');
        $dependency->setLowestVersion('1.0.0');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertSame(1377977856, $errors['foo'][0]['code']);
    }

    /**
     * @test
     * @todo there is no way for this to happen currently
     */
    public function checkPhpDependencyThrowsExceptionIfIdentifierIsNotPhp(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('123');
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);

        $this->expectException(ExtensionManagerException::class);
        $this->expectExceptionCode(1377977858);
        $dependencyUtility->_call('checkPhpDependency', $dependency);
    }

    /**
     * @test
     */
    public function checkPhpDependencyReturnsTrueIfVersionNumberIsInRange(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('php');
        $dependency->setHighestVersion('15.0.0');
        $dependency->setLowestVersion('1.0.0');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertCount(0, $errors);
    }

    /**
     * @test
     */
    public function checkPhpDependencyCanHandleEmptyVersionHighestVersion(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('php');
        $dependency->setHighestVersion('');
        $dependency->setLowestVersion('1.0.0');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertCount(0, $errors);
    }

    /**
     * @test
     */
    public function checkPhpDependencyCanHandleEmptyVersionLowestVersion(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('php');
        $dependency->setHighestVersion('15.0.0');
        $dependency->setLowestVersion('');
        $dependencies = new \SplObjectStorage();
        $dependencies->attach($dependency);

        $extension = new Extension();
        $extension->setExtensionKey('foo');
        $extension->setDependencies($dependencies);
        $dependencyUtility = new DependencyUtility();

        $dependencyUtility->checkDependencies($extension);
        $errors = $dependencyUtility->getDependencyErrors();

        self::assertCount(0, $errors);
    }

    /**
     * @test
     */
    public function isDependentExtensionAvailableReturnsTrueIfExtensionIsAvailable(): void
    {
        $availableExtensions = [
            'dummy' => [],
            'foo' => [],
            'bar' => []
        ];
        $listUtilityMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class)
            ->setMethods(['getAvailableExtensions'])
            ->getMock();
        $listUtilityMock->expects(self::atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('listUtility', $listUtilityMock);

        self::assertTrue($dependencyUtility->_call('isDependentExtensionAvailable', 'dummy'));
    }

    /**
     * @test
     */
    public function isDependentExtensionAvailableReturnsFalseIfExtensionIsNotAvailable(): void
    {
        $availableExtensions = [
            'dummy' => [],
            'foo' => [],
            'bar' => []
        ];
        $listUtilityMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\ListUtility::class)
            ->setMethods(['getAvailableExtensions'])
            ->getMock();
        $listUtilityMock->expects(self::atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('listUtility', $listUtilityMock);

        self::assertFalse($dependencyUtility->_call('isDependentExtensionAvailable', '42'));
    }

    /**
     * @test
     */
    public function isAvailableVersionCompatibleCallsIsVersionCompatibleWithExtensionVersion(): void
    {
        $emConfUtility = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\EmConfUtility::class)
            ->setMethods(['includeEmConf'])
            ->getMock();
        $emConfUtility->expects(self::once())->method('includeEmConf')->willReturn([
            'key' => 'dummy',
            'version' => '1.0.0'
        ]);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['setAvailableExtensions', 'isVersionCompatible']);
        $dependency = new Dependency();
        $dependency->setIdentifier('dummy');
        $dependencyUtility->_set('emConfUtility', $emConfUtility);
        $dependencyUtility->_set('availableExtensions', [
            'dummy' => [
                'foo' => '42'
            ]
        ]);
        $dependencyUtility->expects(self::once())->method('setAvailableExtensions');
        $dependencyUtility->expects(self::once())->method('isVersionCompatible')->with('1.0.0', self::anything());
        $dependencyUtility->_call('isAvailableVersionCompatible', $dependency);
    }

    /**
     * @test
     */
    public function isExtensionDownloadableFromTerReturnsTrueIfOneVersionExists(): void
    {
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByExtensionKey')->with('test123')->willReturn(1);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromTer', 'test123');

        self::assertTrue($count);
    }

    /**
     * @test
     */
    public function isExtensionDownloadableFromTerReturnsFalseIfNoVersionExists()
    {
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByExtensionKey')->with('test123')->willReturn(0);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromTer', 'test123');

        self::assertFalse($count);
    }

    /**
     * @test
     */
    public function isDownloadableVersionCompatibleReturnsTrueIfCompatibleVersionExists()
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('dummy');
        $dependency->setHighestVersion('10.0.0');
        $dependency->setLowestVersion('1.0.0');
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByVersionRangeAndExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 10000000)->willReturn(2);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);

        self::assertTrue($count);
    }

    /**
     * @test
     */
    public function isDownloadableVersionCompatibleReturnsFalseIfIncompatibleVersionExists(): void
    {
        $dependency = new Dependency();
        $dependency->setIdentifier('dummy');
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByVersionRangeAndExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 2000000)->willReturn(0);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['getLowestAndHighestIntegerVersions']);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $dependencyUtility->expects(self::once())->method('getLowestAndHighestIntegerVersions')->willReturn([
            'lowestIntegerVersion' => 1000000,
            'highestIntegerVersion' => 2000000
        ]);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);

        self::assertFalse($count);
    }

    /**
     * @test
     */
    public function getLowestAndHighestIntegerVersionsReturnsArrayWithVersions(): void
    {
        $expectedVersions = [
            'lowestIntegerVersion' => 1000000,
            'highestIntegerVersion' => 2000000
        ];

        $dependency = new Dependency();
        $dependency->setHighestVersion('2.0.0');
        $dependency->setLowestVersion('1.0.0');
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $versions = $dependencyUtility->_call('getLowestAndHighestIntegerVersions', $dependency);

        self::assertSame($expectedVersions, $versions);
    }

    /**
     * @test
     */
    public function getLatestCompatibleExtensionByIntegerVersionDependencyWillReturnExtensionModelOfLatestExtension(): void
    {
        $extension1 = new Extension();
        $extension1->setExtensionKey('foo');
        $extension1->setVersion('1.0.0');
        $extension2 = new Extension();
        $extension2->setExtensionKey('bar');
        $extension2->setVersion('1.0.42');

        $myStorage = new \TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures\LatestCompatibleExtensionObjectStorageFixture();
        $myStorage->extensions[] = $extension1;
        $myStorage->extensions[] = $extension2;
        $dependency = new Dependency();
        $dependency->setIdentifier('foobar');
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['getLowestAndHighestIntegerVersions']);
        $dependencyUtility->expects(self::once())->method('getLowestAndHighestIntegerVersions')->willReturn([
            'lowestIntegerVersion' => 1000000,
            'highestIntegerVersion' => 2000000
        ]);
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['findByVersionRangeAndExtensionKeyOrderedByVersion'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('foobar', 1000000, 2000000)->willReturn($myStorage);
        $dependencyUtility->_set('extensionRepository', $extensionRepositoryMock);
        $extension = $dependencyUtility->_call('getLatestCompatibleExtensionByIntegerVersionDependency', $dependency);

        self::assertInstanceOf(Extension::class, $extension);
        self::assertSame('foo', $extension->getExtensionKey());
    }

    /**
     * @test
     */
    public function filterYoungestVersionOfExtensionListFiltersAListToLatestVersion(): void
    {
        // foo2 should be kept
        $foo1 = new Extension();
        $foo1->setExtensionKey('foo');
        $foo1->setVersion('1.0.0');
        $foo2 = new Extension();
        $foo2->setExtensionKey('foo');
        $foo2->setVersion('1.0.1');

        // bar1 should be kept
        $bar1 = new Extension();
        $bar1->setExtensionKey('bar');
        $bar1->setVersion('1.1.2');
        $bar2 = new Extension();
        $bar2->setExtensionKey('bar');
        $bar2->setVersion('1.1.1');
        $bar3 = new Extension();
        $bar3->setExtensionKey('bar');
        $bar3->setVersion('1.0.3');

        $input = [$foo1, $foo2, $bar1, $bar2, $bar3];
        self::assertEquals(['foo' => $foo2, 'bar' => $bar1], (new DependencyUtility())->filterYoungestVersionOfExtensionList($input, true));
    }

    /**
     * @test
     */
    public function filterYoungestVersionOfExtensionListFiltersAListToLatestVersionWithOnlyCompatibleExtensions(): void
    {
        $suitableDependency = new Dependency();
        $suitableDependency->setIdentifier('typo3');
        $suitableDependency->setLowestVersion('3.6.1');

        $suitableDependencies = new \SplObjectStorage();
        $suitableDependencies->attach($suitableDependency);

        $unsuitableDependency = new Dependency();
        $unsuitableDependency->setIdentifier('typo3');
        $unsuitableDependency->setHighestVersion('4.3.0');

        $unsuitableDependencies = new \SplObjectStorage();
        $unsuitableDependencies->attach($unsuitableDependency);

        // foo1 should be kept
        $foo1 = new Extension();
        $foo1->setExtensionKey('foo');
        $foo1->setVersion('1.0.0');
        $foo1->setDependencies($suitableDependencies);

        $foo2 = new Extension();
        $foo2->setExtensionKey('foo');
        $foo2->setVersion('1.0.1');
        $foo2->setDependencies($unsuitableDependencies);

        // bar2 should be kept
        $bar1 = new Extension();
        $bar1->setExtensionKey('bar');
        $bar1->setVersion('1.1.2');
        $bar1->setDependencies($unsuitableDependencies);

        $bar2 = new Extension();
        $bar2->setExtensionKey('bar');
        $bar2->setVersion('1.1.1');
        $bar2->setDependencies($suitableDependencies);

        $input = [$foo1, $foo2, $bar1, $bar2];
        self::assertEquals(['foo' => $foo1, 'bar' => $bar2], (new DependencyUtility())->filterYoungestVersionOfExtensionList($input, false));
    }
}
