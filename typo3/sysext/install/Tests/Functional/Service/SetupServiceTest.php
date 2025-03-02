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
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Install\Service\SetupService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SetupServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['install', 'dashboard'];

    #[Test]
    public function multipleCreateBackendUserGroupsCreatesGroupsOnce(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandTwice.csv');
    }

    #[Test]
    public function multipleCreateBackendUserGroupsWithForceCreatesGroupsMultipleTimes(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $subject->createBackendUserGroups(true, true, true);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ForcedCreateUserGroupsCommand.csv');
    }

    #[Test]
    public function createEditorOnlyCreatesEditor(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups(true, false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsOnlyEditorCommand.csv');
    }

    #[Test]
    public function createAdvancedEditorOnlyCreatesAdvancedEditor(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups(false);
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsOnlyAdvancedEditorCommand.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithCasualFields(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommand.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithTablePermissions(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandTablePermissions.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithModules(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandModules.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithAllowedContentElements(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandAllowedContentElements.csv');
    }

    #[Test]
    public function createBackendUserGroupsCreatesGroupsWithNonExcludeFields(): void
    {
        $subject = new SetupService(
            $this->get(ConfigurationManager::class),
            $this->get(SiteWriter::class),
            $this->get(YamlFileLoader::class),
            new FailsafePackageManager(new DependencyOrderingService())
        );
        $subject->createBackendUserGroups();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/CreateUserGroupsCommandNonExcludeFields.csv');
    }
}
