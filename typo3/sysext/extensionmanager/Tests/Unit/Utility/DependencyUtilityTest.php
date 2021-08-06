<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures\LatestCompatibleExtensionObjectStorageFixture;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test for DependencyUtility
 */
class DependencyUtilityTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
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
        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function checkTypo3DependencyErrorsIfVersionNumberIsTooLow(): void
    {
        $dependency = Dependency::createFromEmConf('typo3', '15.0.0-0');
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
        $dependency = Dependency::createFromEmConf('typo3', '1.0.0-3.0.0');
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
     */
    public function checkTypo3DependencyReturnsTrueIfVersionNumberIsInRange(): void
    {
        $dependency = Dependency::createFromEmConf('typo3', '1.0.0-25.0.0');
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
    public function checkTypo3DependencyCanHandleEmptyVersionHighestVersion(): void
    {
        $dependency = Dependency::createFromEmConf('typo3', '1.0.0');
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
    public function checkTypo3DependencyCanHandleEmptyVersionLowestVersion(): void
    {
        $dependency = Dependency::createFromEmConf('typo3', '-15.0.0');
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
    public function checkPhpDependencyErrorsIfVersionNumberIsTooLow(): void
    {
        $dependency = Dependency::createFromEmConf('php', '15.0.0');
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
        $dependency = Dependency::createFromEmConf('php', '1.0.0-3.0.0');
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
     */
    public function checkPhpDependencyReturnsTrueIfVersionNumberIsInRange(): void
    {
        $dependency = Dependency::createFromEmConf('php', '1.0.0-15.0.0');
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
        $dependency = Dependency::createFromEmConf('php', '1.0.0');
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
        $dependency = Dependency::createFromEmConf('typo3', '-25.0.0');
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
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $listUtilityMock = $this->getMockBuilder(ListUtility::class)
            ->setMethods(['getAvailableExtensions'])
            ->getMock();
        $listUtilityMock->injectEventDispatcher($eventDispatcher);
        $listUtilityMock->expects(self::atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->injectListUtility($listUtilityMock);

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
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $listUtilityMock = $this->getMockBuilder(ListUtility::class)
            ->setMethods(['getAvailableExtensions'])
            ->getMock();
        $listUtilityMock->injectEventDispatcher($eventDispatcher);
        $listUtilityMock->expects(self::atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->injectListUtility($listUtilityMock);

        self::assertFalse($dependencyUtility->_call('isDependentExtensionAvailable', '42'));
    }

    /**
     * @test
     */
    public function isAvailableVersionCompatibleCallsIsVersionCompatibleWithExtensionVersion(): void
    {
        $emConfUtility = $this->getMockBuilder(EmConfUtility::class)
            ->setMethods(['includeEmConf'])
            ->getMock();
        $emConfUtility->expects(self::once())->method('includeEmConf')->willReturn([
            'key' => 'dummy',
            'version' => '1.0.0'
        ]);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['setAvailableExtensions']);
        $dependency = Dependency::createFromEmConf('dummy');
        $dependencyUtility->injectEmConfUtility($emConfUtility);
        $dependencyUtility->_set('availableExtensions', [
            'dummy' => [
                'foo' => '42'
            ]
        ]);
        $dependencyUtility->expects(self::once())->method('setAvailableExtensions');
        $dependencyUtility->_call('isAvailableVersionCompatible', $dependency);
    }

    /**
     * @test
     */
    public function isExtensionDownloadableFromRemoteReturnsTrueIfOneVersionExists(): void
    {
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByExtensionKey')->with('test123')->willReturn(1);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromRemote', 'test123');

        self::assertTrue($count);
    }

    /**
     * @test
     */
    public function isExtensionDownloadableFromRemoteReturnsFalseIfNoVersionExists()
    {
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByExtensionKey')->with('test123')->willReturn(0);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromRemote', 'test123');

        self::assertFalse($count);
    }

    /**
     * @test
     */
    public function isDownloadableVersionCompatibleReturnsTrueIfCompatibleVersionExists()
    {
        $dependency = Dependency::createFromEmConf('dummy', '1.0.0-10.0.0');
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByVersionRangeAndExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 10000000)->willReturn(2);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['dummy']);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);

        self::assertTrue($count);
    }

    /**
     * @test
     */
    public function isDownloadableVersionCompatibleReturnsFalseIfIncompatibleVersionExists(): void
    {
        $dependency = Dependency::createFromEmConf('dummy', '1.0.0-2.0.0');
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['countByVersionRangeAndExtensionKey'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 2000000)->willReturn(0);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['getLowestAndHighestIntegerVersions']);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);

        self::assertFalse($count);
    }

    /**
     * @test
     */
    public function getLatestCompatibleExtensionByDependencyWillReturnExtensionModelOfLatestExtension(): void
    {
        $suitableDependency = Dependency::createFromEmConf('typo3', '3.6.1');
        $suitableDependencies = new \SplObjectStorage();
        $suitableDependencies->attach($suitableDependency);

        $unsuitableDependency = Dependency::createFromEmConf('typo3', '-4.3.0');
        $unsuitableDependencies = new \SplObjectStorage();
        $unsuitableDependencies->attach($unsuitableDependency);

        $extension1 = new Extension();
        $extension1->setExtensionKey('foo');
        $extension1->setVersion('1.0.0');
        $extension1->setDependencies($unsuitableDependencies);

        $extension2 = new Extension();
        $extension2->setExtensionKey('bar');
        $extension2->setVersion('1.0.42');
        $extension2->setDependencies($suitableDependencies);

        $myStorage = new LatestCompatibleExtensionObjectStorageFixture();
        $myStorage->extensions[] = $extension1;
        $myStorage->extensions[] = $extension2;
        $dependency = Dependency::createFromEmConf('foobar', '1.0.0-2.0.0');
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['getLowestAndHighestIntegerVersions']);
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->setMethods(['findByVersionRangeAndExtensionKeyOrderedByVersion'])
            ->setConstructorArgs([$this->objectManagerMock])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('foobar', 1000000, 2000000)->willReturn($myStorage);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $extension = $dependencyUtility->_call('getLatestCompatibleExtensionByDependency', $dependency);

        self::assertInstanceOf(Extension::class, $extension);
        self::assertSame('bar', $extension->getExtensionKey());
    }
}
