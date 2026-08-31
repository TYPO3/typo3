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

namespace TYPO3\CMS\Core\Tests\Unit\Package;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Package\PackageSetup;
use TYPO3\CMS\Core\Package\VirtualAppPackage;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PackageSetupTest extends UnitTestCase
{
    #[Test]
    public function setupPublishesResourcesOfVirtualAppPackage(): void
    {
        $sqlReader = self::createStub(SqlReader::class);
        $sqlReader->method('getTablesDefinitionString')->willReturn('');
        $sqlReader->method('getCreateTableStatementArray')->willReturn([]);

        $schemaMigrator = self::createStub(SchemaMigrator::class);
        $schemaMigrator->method('getUpdateSuggestions')->willReturn([[]]);

        $virtualAppPackage = self::createStub(PackageInterface::class);

        $packageManager = $this->createMock(PackageManager::class);
        $packageManager
            ->expects($this->once())
            ->method('getPackage')
            ->with(VirtualAppPackage::APP_PACKAGE_KEY)
            ->willReturn($virtualAppPackage);

        $resourcePublisher = $this->createMock(SystemResourcePublisherInterface::class);
        $resourcePublisher
            ->expects($this->once())
            ->method('publishResources')
            ->with(self::identicalTo($virtualAppPackage));

        $subject = new PackageSetup(
            $sqlReader,
            $schemaMigrator,
            self::createStub(ExtensionConfiguration::class),
            self::createStub(EventDispatcherInterface::class),
            $resourcePublisher,
            $packageManager,
        );

        $subject->setup([]);
    }
}
