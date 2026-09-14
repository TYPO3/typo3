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
use TYPO3\CMS\Extensionmanager\Utility\DependencyUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DependencyUtilityTest extends UnitTestCase
{
    #[Test]
    public function checkTypo3DependencyErrorsIfVersionNumberIsTooLow(): void
    {
        $dependency = Dependency::createFromEmConf('typo3', '15.0.0-0');
        $dependencies = new \SplObjectStorage();
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $dependencies->offsetSet($dependency);

        $extension = new Extension();
        $extension->extensionKey = 'foo';
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
        $listUtilityMock->expects($this->atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
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
        $listUtilityMock->expects($this->atLeastOnce())->method('getAvailableExtensions')->willReturn($availableExtensions);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectListUtility($listUtilityMock);

        self::assertFalse($dependencyUtility->_call('isDependentExtensionAvailable', '42'));
    }

    /**
     * The version of an available extension is the one the package metadata
     * reports, which comes from composer.json. ext_emconf.php is not consulted.
     */
    #[Test]
    public function isAvailableVersionCompatibleComparesTheVersionOfTheAvailableExtension(): void
    {
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, ['setAvailableExtensions']);
        $dependencyUtility->expects($this->exactly(2))->method('setAvailableExtensions');
        $dependencyUtility->_set('availableExtensions', [
            'dummy' => [
                'packagePath' => '/does/not/exist/',
                'version' => '1.5.0',
            ],
        ]);

        self::assertTrue($dependencyUtility->_call('isAvailableVersionCompatible', Dependency::createFromEmConf('dummy', '1.0.0-1.9.99')));
        self::assertFalse($dependencyUtility->_call('isAvailableVersionCompatible', Dependency::createFromEmConf('dummy', '2.0.0-2.9.99')));
    }

    #[Test]
    public function isExtensionDownloadableFromRemoteReturnsTrueIfOneVersionExists(): void
    {
        $extensionRepositoryMock = $this->createMock(ExtensionRepository::class);
        $mockExtension = $this->createMock(Extension::class);
        $extensionRepositoryMock->expects($this->once())
            ->method('findByExtensionKeyOrderedByVersion')
            ->with('test123')
            ->willReturn([$mockExtension]);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromRemote', 'test123');
        self::assertTrue($count);
    }

    #[Test]
    public function isExtensionDownloadableFromRemoteReturnsFalseIfNoVersionExists(): void
    {
        $extensionRepositoryMock = $this->createMock(ExtensionRepository::class);
        $extensionRepositoryMock->expects($this->once())
            ->method('findByExtensionKeyOrderedByVersion')
            ->with('test123')
            ->willReturn([]);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isExtensionDownloadableFromRemote', 'test123');
        self::assertFalse($count);
    }

    #[Test]
    public function isDownloadableVersionCompatibleReturnsTrueIfCompatibleVersionExists(): void
    {
        $dependency = Dependency::createFromEmConf('dummy', '1.0.0-10.0.0');
        $extensionRepositoryMock = $this->createMock(ExtensionRepository::class);
        $extensionRepositoryMock->expects($this->once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('dummy', 1000000, 10000000)->willReturn(['foo', 'bar']);
        $dependencyUtility = $this->getAccessibleMock(DependencyUtility::class, null);
        $dependencyUtility->injectExtensionRepository($extensionRepositoryMock);
        $count = $dependencyUtility->_call('isDownloadableVersionCompatible', $dependency);
        self::assertTrue($count);
    }

    #[Test]
    public function isDownloadableVersionCompatibleReturnsFalseIfIncompatibleVersionExists(): void
    {
        $dependency = Dependency::createFromEmConf('dummy', '1.0.0-2.0.0');
        $extensionRepositoryMock = $this->createMock(ExtensionRepository::class);
        $extensionRepositoryMock->expects($this->once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('dummy', 1000000, 2000000)->willReturn([]);
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
        $suitableDependencies->offsetSet($suitableDependency);

        $unsuitableDependency = Dependency::createFromEmConf('typo3', '-4.3.0');
        $unsuitableDependencies = new \SplObjectStorage();
        $unsuitableDependencies->offsetSet($unsuitableDependency);

        $extension1 = new Extension();
        $extension1->extensionKey = 'foo';
        $extension1->version = '1.0.0';
        $extension1->setDependencies($unsuitableDependencies);

        $extension2 = new Extension();
        $extension2->extensionKey = 'bar';
        $extension2->version = '1.0.42';
        $extension2->setDependencies($suitableDependencies);

        $extensions = [$extension1, $extension2];
        $dependency = Dependency::createFromEmConf('foobar', '1.0.0-2.0.0');
        $subject = $this->getAccessibleMock(DependencyUtility::class, null);
        $extensionRepositoryMock = $this->createMock(ExtensionRepository::class);
        $extensionRepositoryMock->expects($this->once())->method('findByVersionRangeAndExtensionKeyOrderedByVersion')->with('foobar', 1000000, 2000000)->willReturn($extensions);
        $subject->injectExtensionRepository($extensionRepositoryMock);
        $extension = $subject->_call('getLatestCompatibleExtensionByDependency', $dependency);

        self::assertInstanceOf(Extension::class, $extension);
        self::assertSame('bar', $extension->extensionKey);
    }
}
