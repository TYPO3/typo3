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

namespace TYPO3\CMS\Install\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Package\PackageSetup;
use TYPO3\CMS\Core\Package\VirtualAppPackage;
use TYPO3\CMS\Install\Service\SetupService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SetupServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install', 'dashboard'];

    #[Test]
    public function setupExtensionsDoesNotSetUpVirtualAppPackage(): void
    {
        $packageSetup = $this->createMock(PackageSetup::class);
        $packageSetup
            ->expects($this->once())
            ->method('setup')
            ->with(self::callback(
                static function (array $packages): bool {
                    self::assertNotEmpty($packages);
                    self::assertArrayNotHasKey(VirtualAppPackage::APP_PACKAGE_KEY, $packages);
                    return true;
                }
            ));

        $container = self::createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(
                fn(string $serviceName): mixed => $serviceName === PackageSetup::class
                    ? $packageSetup
                    : $this->get($serviceName)
            );

        $previousBackendUser = $GLOBALS['BE_USER'] ?? null;
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        try {
            $this->get(SetupService::class)->setupExtensions($container);
        } finally {
            if ($previousBackendUser === null) {
                unset($GLOBALS['BE_USER']);
            } else {
                $GLOBALS['BE_USER'] = $previousBackendUser;
            }
        }
    }

    #[Test]
    public function multipleCreateBackendUserGroupsCreatesGroupsOnce(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandTwice.csv');
    }

    #[Test]
    public function multipleCreateBackendUserGroupsWithForceCreatesGroupsMultipleTimes(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $subject->createBackendUserGroups(true, true, true);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ForcedCreateUserGroupsCommand.csv');
    }

    #[Test]
    public function createEditorOnlyCreatesEditor(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups(true, false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsOnlyEditorCommand.csv');
    }

    #[Test]
    public function createAdvancedEditorOnlyCreatesAdvancedEditor(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsOnlyAdvancedEditorCommand.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithCasualFields(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommand.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithTablePermissions(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandTablePermissions.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithModules(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandModules.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithAllowedContentElements(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandAllowedContentElements.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithNonExcludeFields(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandNonExcludeFields.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesFileMount(): void
    {
        $subject = $this->get(SetupService::class);
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandFileMount.csv');
    }
}
