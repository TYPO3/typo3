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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Controller\RecordListController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for RecordListController multi-language selection functionality
 */
final class RecordListControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $scenarioFile = __DIR__ . '/../Fixtures/CommonScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($this->backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
            },
            function () {
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
            }
        );

        $this->writeSiteConfiguration(
            'test-site',
            $this->buildSiteConfiguration(1000, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
                $this->buildLanguageConfiguration('ES', '/es/', ['EN']),
            ]
        );
    }

    #[Test]
    #[IgnoreDeprecations] // Calls recordlist multiple times and therefore triggers deprecation notice for manual shortcut button
    public function multipleLanguagesCanBeSelected(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        // Request with multiple languages
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0, 1, 2]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify multiple languages are stored
        $storedLanguages = $moduleData->get('languages');
        self::assertIsArray($storedLanguages);
        self::assertCount(3, $storedLanguages);
        self::assertContains(0, $storedLanguages);
        self::assertContains(1, $storedLanguages);
        self::assertContains(2, $storedLanguages);
    }

    #[Test]
    public function defaultLanguageIsAlwaysIncludedInRecordList(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        // Try to select only translation languages (1, 2) without default
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [1, 2]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify default language (0) was automatically added
        $storedLanguages = $moduleData->get('languages');
        self::assertContains(0, $storedLanguages);
        self::assertContains(1, $storedLanguages);
        self::assertContains(2, $storedLanguages);
    }

    #[Test]
    public function emptyLanguageSelectionDefaultsToDefaultLanguage(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        // Request with empty languages array
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => []]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify defaults to default language
        $storedLanguages = $moduleData->get('languages');
        self::assertEquals([0], $storedLanguages);
    }

    #[Test]
    public function invalidLanguageIdsAreFilteredOut(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        // Request with mix of valid and invalid language IDs
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0, 1, 999, 'invalid']]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify only valid languages are stored
        $storedLanguages = $moduleData->get('languages');
        self::assertContains(0, $storedLanguages);
        self::assertContains(1, $storedLanguages);
        self::assertNotContains(999, $storedLanguages);
        self::assertNotContains('invalid', $storedLanguages);
    }

    #[Test]
    #[IgnoreDeprecations] // Calls recordlist multiple times and therefore triggers deprecation notice for manual shortcut button
    public function languageSelectionPersistsAcrossRequests(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        // First request: select languages [0, 1]
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0, 1]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        self::assertEquals([0, 1], $moduleData->get('languages'));

        // Second request: no language parameter, should use persisted value
        $request = $this->createRequest(1400, $site, $moduleData);
        $controller->mainAction($request);

        self::assertEquals([0, 1], $moduleData->get('languages'));
    }

    #[Test]
    public function requestParameterOverridesModuleDataLanguages(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', ['languages' => [0, 1]], []);

        // Request explicitly changes languages to [0, 2]
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0, 2]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify request parameter takes precedence
        $storedLanguages = $moduleData->get('languages');
        self::assertContains(0, $storedLanguages);
        self::assertContains(2, $storedLanguages);
        self::assertNotContains(1, $storedLanguages);
    }

    #[Test]
    public function backwardCompatibilityWithOldSingleLanguageParameter(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');

        // Old format: single 'language' value stored as integer
        $moduleData = new ModuleData('web_list', ['language' => 1], []);

        $request = $this->createRequest(1100, $site, $moduleData);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify old parameter is converted to new array format
        $storedLanguages = $moduleData->get('languages');
        self::assertIsArray($storedLanguages);
        self::assertContains(0, $storedLanguages);
        self::assertContains(1, $storedLanguages);
    }

    #[Test]
    public function languageParametersAreCastToIntegers(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => ['0', '1', '2']]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        $storedLanguages = $moduleData->get('languages');
        foreach ($storedLanguages as $langId) {
            self::assertIsInt($langId);
        }
    }

    #[Test]
    public function duplicateLanguageIdsAreRemoved(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0, 1, 1, 2, 2, 0]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        $storedLanguages = $moduleData->get('languages');
        self::assertCount(3, $storedLanguages);
        self::assertEquals([0, 1, 2], array_values(array_unique($storedLanguages)));
    }

    #[Test]
    public function singleLanguageSelectionWorks(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        $storedLanguages = $moduleData->get('languages');
        self::assertEquals([0], $storedLanguages);
    }

    #[Test]
    public function allExistingLanguagesCanBeSelected(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('web_list', [], []);

        // Page 1100 has L=0, L=1, L=2 in live workspace (L=3 only in workspace)
        // Requesting [0,1,2,3] should filter to [0,1,2]
        $request = $this->createRequest(1100, $site, $moduleData, ['languages' => [0, 1, 2, 3]]);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        $storedLanguages = $moduleData->get('languages');
        self::assertCount(3, $storedLanguages);
        self::assertContains(0, $storedLanguages);
        self::assertContains(1, $storedLanguages);
        self::assertContains(2, $storedLanguages);
        self::assertNotContains(3, $storedLanguages); // L=3 doesn't exist on this page
    }

    private function createRequest(int $pageId, object $site, ModuleData $moduleData, array $additionalParams = []): ServerRequest
    {
        $queryParams = array_merge(['id' => $pageId], $additionalParams);

        $request = (new ServerRequest('https://example.com/typo3/module/web/list'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/typo3/module/web/list', ['_identifier' => 'web_list']))
            ->withAttribute('site', $site)
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams($queryParams);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        // Create PageContext via factory (simulating middleware)
        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, $pageId, $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        return $request;
    }
}
