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

namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class PagesAndTtContentTest extends AbstractImportExportTestCase
{
    private const string FIXTURE_WITHOUT_SITE_CONFIG = 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent.xml';
    private const string FIXTURE_WITH_SITE_CONFIG = 'EXT:impexp/Tests/Functional/Fixtures/XmlImports/pages-and-ttcontent-with-site-config.xml';

    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/impexp/Tests/Functional/Fixtures/Folders/fileadmin/user_upload' => 'fileadmin/user_upload',
    ];

    private function createSiteConfiguration(string $identifier, int $rootPageId, string $base = 'https://example.com/'): void
    {
        $this->get(SiteWriter::class)->write($identifier, [
            'rootPageId' => $rootPageId,
            'base' => $base,
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => '/', 'flag' => 'global'],
            ],
        ]);
    }

    #[Test]
    public function importPagesAndRelatedTtContent(): void
    {
        $subject = $this->get(Import::class);
        $subject->setPid(0);

        $subject->loadFile(self::FIXTURE_WITHOUT_SITE_CONFIG);
        $subject->importData();

        $this->testFilesToDelete[] = Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg';

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/DatabaseAssertions/importPagesAndRelatedTtContent.csv');

        self::assertFileEquals(__DIR__ . '/../Fixtures/Folders/fileadmin/user_upload/typo3_image3.jpg', Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image3.jpg');
        self::assertFileEquals(__DIR__ . '/../Fixtures/Extensions/template_extension/Resources/Public/Templates/Empty.html', Environment::getPublicPath() . '/typo3conf/ext/template_extension/Resources/Public/Templates/Empty.html');
    }

    #[Test]
    public function importCreatesSiteConfigurationFromFixture(): void
    {
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile(self::FIXTURE_WITH_SITE_CONFIG);
        $import->importData();

        $siteFinder = $this->get(SiteFinder::class);
        $allSites = $siteFinder->getAllSites(false);
        self::assertNotEmpty($allSites, 'At least one site configuration should exist after import.');

        $importedSite = reset($allSites);
        $siteLanguages = $importedSite->getLanguages();
        self::assertArrayHasKey(0, $siteLanguages);
        self::assertSame('English', $siteLanguages[0]->getTitle());
    }

    #[Test]
    public function importRemapsSiteConfigurationRootPageIdToNewlyImportedUid(): void
    {
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile(self::FIXTURE_WITH_SITE_CONFIG);
        $import->importData();

        $siteFinder = $this->get(SiteFinder::class);
        $allSites = $siteFinder->getAllSites(false);
        self::assertNotEmpty($allSites);
        $importedSite = reset($allSites);
        self::assertGreaterThan(0, $importedSite->getRootPageId());
    }

    #[Test]
    public function importSkipsSiteConfigurationWhenRootPageAlreadyHasSite(): void
    {
        // Create a site for rootPageId=1. Since the database is empty, the first imported
        // page will get UID=1 and the import map will map 1→1, so this site will match.
        $this->createSiteConfiguration('existing-site', 1);

        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile(self::FIXTURE_WITH_SITE_CONFIG);
        $import->importData();

        // Only the pre-existing site should exist — import was skipped because the
        // imported root page already has a site configuration.
        $siteFinder = $this->get(SiteFinder::class);
        $allSites = $siteFinder->getAllSites(false);
        self::assertCount(1, $allSites);
        self::assertArrayHasKey('existing-site', $allSites);
    }

    #[Test]
    public function importUsesAlternativeSiteIdentifierWhenIdentifierAlreadyExists(): void
    {
        // Create a site with identifier 'test-site' for page 99 (different root page)
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/DatabaseImports/pages.csv');
        $this->createSiteConfiguration('test-site', 99, 'https://existing.example.com/');

        // Import fixture — pages get new UIDs, and the site config uses identifier 'test-site'
        // which is already taken, so the import should pick 'test-site-1'.
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile(self::FIXTURE_WITH_SITE_CONFIG);
        $import->importData();

        $siteFinder = $this->get(SiteFinder::class);
        $allSites = $siteFinder->getAllSites(false);
        self::assertArrayHasKey('test-site', $allSites, 'Original test-site should still exist.');
        self::assertArrayHasKey('test-site-1', $allSites, 'Import should have created test-site-1 as fallback identifier.');
    }

    #[Test]
    public function importDoesNotCreateSiteConfigurationWhenDisabled(): void
    {
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile(self::FIXTURE_WITH_SITE_CONFIG);
        $import->disableSiteConfigurationImport();
        $import->importData();

        $siteFinder = $this->get(SiteFinder::class);
        $allSites = $siteFinder->getAllSites(false);
        self::assertEmpty($allSites, 'No site configuration should exist when import is disabled.');
    }

    #[Test]
    public function getSiteConfigurationsReturnsConfigurationsWithRootPageTitle(): void
    {
        $import = $this->get(Import::class);
        $import->setPid(0);
        $import->loadFile(self::FIXTURE_WITH_SITE_CONFIG);

        $siteConfigurations = $import->getSiteConfigurations();
        self::assertArrayHasKey('test-site', $siteConfigurations);
        self::assertSame('Root', $siteConfigurations['test-site']['_rootPageTitle']);
    }
}
