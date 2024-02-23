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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionRepositoryTest extends UnitTestCase
{
    #[Test]
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
        $subject = $this->getAccessibleMock(ExtensionRepository::class, null, [], '', false);
        self::assertEquals(['foo' => $foo2, 'bar' => $bar1], $subject->_call('filterYoungestVersionOfExtensionList', $input, true));
    }

    #[Test]
    public function filterYoungestVersionOfExtensionListFiltersAListToLatestVersionWithOnlyCompatibleExtensions(): void
    {
        $suitableDependency = Dependency::createFromEmConf('typo3', '3.6.1');
        $suitableDependencies = new \SplObjectStorage();
        $suitableDependencies->attach($suitableDependency);

        $unsuitableDependency = Dependency::createFromEmConf('typo3', '-4.3.0');

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
        $subject = $this->getAccessibleMock(ExtensionRepository::class, null, [], '', false);
        self::assertEquals(['foo' => $foo1, 'bar' => $bar2], $subject->_call('filterYoungestVersionOfExtensionList', $input, false));
    }

    #[Test]
    public function getExtensionsSuitableForTypo3VersionReturnsOnlySuitableOnes(): void
    {
        $suitableDependency = Dependency::createFromEmConf('typo3', '10.4.0-99.99.99');
        $suitableDependencies = new \SplObjectStorage();
        $suitableDependencies->attach($suitableDependency);
        $suitableExtension = new Extension();
        $suitableExtension->setExtensionKey('suitable');
        $suitableExtension->setVersion('1.0.0');
        $suitableExtension->setDependencies($suitableDependencies);

        $unsuitableDependency = Dependency::createFromEmConf('typo3', '9.5.0-10.4.99');
        $unsuitableDependencies = new \SplObjectStorage();
        $unsuitableDependencies->attach($unsuitableDependency);
        $unsuitableExtension = new Extension();
        $unsuitableExtension->setExtensionKey('unsuitable');
        $unsuitableExtension->setVersion('1.0.0');
        $unsuitableExtension->setDependencies($unsuitableDependencies);

        $input = [$suitableExtension, $unsuitableExtension];
        $subject = $this->getAccessibleMock(ExtensionRepository::class, null, [], '', false);

        self::assertSame($this->count($subject->_call('getExtensionsSuitableForTypo3Version', $input)), 1);
    }
}
