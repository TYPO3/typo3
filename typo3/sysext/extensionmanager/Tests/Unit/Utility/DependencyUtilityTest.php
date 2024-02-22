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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures\LatestCompatibleExtensionObjectStorageFixture;
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\EmConfUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DependencyUtilityTest extends UnitTestCase
{
    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function isDependentExtensionAvailableReturnsTrueIfExtensionIsAvailable(): void
    {
        $availableExtensions = [
            'dummy' => [],
            'foo' => [],
            'bar' => [],
        ];
        $eventDispatcher = new NoopEventDispatcher();
        $listUtilityMock = $this->getMockBuilder(ListUtility::class)
            ->onlyMethods(['getAvailableExtensions'])
            ->getMock();
        $listUtilityMock->injectEventDispatcher($eventDispatcher);
        $listUtilityMock->expects(self::atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectListUtility($listUtilityMock);

        self::assertTrue($dependencyUtility->_call('isDependentExtensionAvailable', 'dummy'));
    }

    #[Test]
    public function isDependentExtensionAvailableReturnsFalseIfExtensionIsNotAvailable(): void
    {
        $availableExtensions = [
            'dummy' => [],
            'foo' => [],
            'bar' => [],
        ];
        $eventDispatcher = new NoopEventDispatcher();
        $listUtilityMock = $this->getMockBuilder(ListUtility::class)
            ->onlyMethods(['getAvailableExtensions'])
            ->getMock();
        $listUtilityMock->injectEventDispatcher($eventDispatcher);
        $listUtilityMock->expects(self::atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectListUtility($listUtilityMock);

        self::assertFalse($dependencyUtility->_call('isDependentExtensionAvailable', '42'));
    }

    #[Test]
    public function isAvailableVersionCompatibleCallsIsVersionCompatibleWithExtensionVersion(): void
    {
        $emConfUtility = $this->getMockBuilder(EmConfUtility::class)
            ->onlyMethods(['includeEmConf'])
            ->getMock();
        $emConfUtility->expects(self::once())->method('includeEmConf')->willReturn([
            'key' => 'dummy',
            'version' => '1.0.0',
        ]);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['setAvailableExtensions']);
        $dependency = Dependency::createFromEmConf('dummy');
        $dependencyUtility->injectEmConfUtility($emConfUtility);
        $dependencyUtility->_set('availableExtensions', [
            'dummy' => [
                'foo' => '42',
            ],
        ]);
        $dependencyUtility->expects(self::once())->method('setAvailableExtensions');
        $dependencyUtility->_call('isAvailableVersionCompatible', $dependency);
    }

    #[Test]
    public function isExtensionDownloadableFromRemoteReturnsTrueIfOneVersionExists(): void
    {
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->onlyMethods(['count'])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('count')->with(['extensionKey' => 'test123'])->willReturn(1);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromRemote', 'test123');

        self::assertTrue($count);
    }

    #[Test]
    public function isExtensionDownloadableFromRemoteReturnsFalseIfNoVersionExists(): void
    {
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->onlyMethods(['count'])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('count')->with(['extensionKey' => 'test123'])->willReturn(0);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromRemote', 'test123');

        self::assertFalse($count);
    }

    #[Test]
    public function isDownloadableVersionCompatibleReturnsTrueIfCompatibleVersionExists(): void
    {
        $dependency = Dependency::createFromEmConf('dummy', '1.0.0-10.0.0');
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->onlyMethods(['countByVersionRangeAndExtensionKey'])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 10000000)->willReturn(2);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);

        self::assertTrue($count);
    }

    #[Test]
    public function isDownloadableVersionCompatibleReturnsFalseIfIncompatibleVersionExists(): void
    {
        $dependency = Dependency::createFromEmConf('dummy', '1.0.0-2.0.0');
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->onlyMethods(['countByVersionRangeAndExtensionKey'])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('countByVersionRangeAndExtensionKey')->with('dummy', 1000000, 2000000)->willReturn(0);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);

        self::assertFalse($count);
    }

    #[Test]
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
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $extensionRepositoryMock = $this->getMockBuilder(ExtensionRepository::class)
            ->onlyMethods(['findByVersionRangeAndExtensionKeyOrderedByVersion'])
            ->getMock();
        $extensionRepositoryMock->expects(self::once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('foobar', 1000000, 2000000)->willReturn($myStorage);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $extension = $dependencyUtility->_call('getLatestCompatibleExtensionByDependency', $dependency);

        self::assertInstanceOf(Extension::class, $extension);
        self::assertSame('bar', $extension->getExtensionKey());
    }
}
