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

namespace TYPO3\CMS\Install\Tests\Functional\Updates\RowUpdater;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseRowsUpdateWizard;
use TYPO3\CMS\Install\Updates\RowUpdater\SysRedirectRootPageMoveMigration;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SysRedirectRootPageMoveMigrationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [];

    protected array $coreExtensionsToLoad = ['redirects'];

    /**
     * @var MockObject|DatabaseRowsUpdateWizard|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    protected ActionService $actionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actionService = GeneralUtility::makeInstance(ActionService::class);
        // Register only WorkspaceNewPlaceholderRemovalMigration in the row updater wizard
        $this->subject = $this->getAccessibleMock(DatabaseRowsUpdateWizard::class, ['dummy']);
        $this->subject->_set('rowUpdater', [SysRedirectRootPageMoveMigration::class]);
    }

    protected function tearDown(): void
    {
        // Cleanup written site configuration, to have no impact for test expecting no-site config.
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites');
        parent::tearDown();
    }

    /**
     * @test
     */
    public function siteRootPageChildrenRecordsAreMovedToSiteRootPage(): void
    {
        // Data set inspired by workspaces IRRE/CSV/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationSiteRootChildrenImport.csv');
        $this->writeSiteConfiguration('site-one', $this->buildSiteConfiguration(1, '/site-one/'));
        $this->writeSiteConfiguration('site-two', $this->buildSiteConfiguration(3, '/site-two/'));
        $this->subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationSiteRootChildrenResult.csv');
    }

    /**
     * @test
     */
    public function siteRootPageChildrenRecordsWithDisabledRedirectsAreMovedToSiteRootPage(): void
    {
        // Data set inspired by workspaces IRRE/CSV/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationSiteRootChildrenWithDisabledRedirectImport.csv');
        $this->writeSiteConfiguration('site-one', $this->buildSiteConfiguration(1, '/site-one/'));
        $this->writeSiteConfiguration('site-two', $this->buildSiteConfiguration(3, '/site-two/'));
        $this->subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationSiteRootChildrenWithDisabledRedirectResult.csv');
    }

    /**
     * @test
     */
    public function siteRootPageChildrenRecordsWithDeletedRedirectsAreMovedToSiteRootPage(): void
    {
        // Data set inspired by workspaces IRRE/CSV/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationSiteRootChildrenWithDeletedRedirectImport.csv');
        $this->writeSiteConfiguration('site-one', $this->buildSiteConfiguration(1, '/site-one/'));
        $this->writeSiteConfiguration('site-two', $this->buildSiteConfiguration(3, '/site-two/'));
        $this->subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationSiteRootChildrenWithDeletedRedirectResult.csv');
    }

    /**
     * @test
     */
    public function subPageRecordsAreMovedToPidZeroIfNoSiteConfig(): void
    {
        // Data set inspired by workspaces IRRE/CSV/Modify/DataSet/copyPage.csv
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationNoSiteConfigImport.csv');
        $this->subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/SysRedirectsRootPageMoveMigrationNoSiteConfigResult.csv');
    }
}
