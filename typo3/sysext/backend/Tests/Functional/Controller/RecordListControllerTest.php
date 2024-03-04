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

use PHPUnit\Framework\Attributes\Test;
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

final class RecordListControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'FR-CA' => ['id' => 2, 'title' => 'French (CA)', 'locale' => 'fr_CA.UTF8'],
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
            'acme',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', '/fr-ca/', ['FR', 'EN']),
                $this->buildLanguageConfiguration('ES', '/es/', ['EN']),
            ]
        );
    }

    #[Test]
    public function languageSelectionIsPersistedAcrossPageNavigations(): void
    {
        // Initial request with language=1 (French) on page 1100 (has French translation)
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');
        $moduleData = new ModuleData('web_list', ['language' => 1], ['language' => -1]);

        $request = $this->createRequest(1100, $site, $moduleData);
        $controller = $this->get(RecordListController::class);
        $response = $controller->mainAction($request);

        // Verify language 1 is set in the moduleData after processing
        self::assertSame(1, $moduleData->get('language'), 'Language selection should be set in moduleData');

        // Navigate to another page with French translation (page 1400)
        // Use the same moduleData to simulate persistence
        $request = $this->createRequest(1400, $site, $moduleData);
        $response = $controller->mainAction($request);

        // Verify language 1 is still selected
        self::assertSame(1, $moduleData->get('language'), 'Language selection should persist across page changes');
    }

    #[Test]
    public function languageFallsBackToAllLanguagesWhenTranslationNotAvailable(): void
    {
        // Select French (language 1)
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');
        $moduleData = new ModuleData('web_list', ['language' => 1], ['language' => -1]);

        // Navigate to page 1100 which has French translation
        $request = $this->createRequest(1100, $site, $moduleData);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify French is set
        self::assertSame(1, $moduleData->get('language'));
        $storedLanguage = $moduleData->get('language'); // Store the user's preference

        // Create new moduleData with stored preference for page 1200 which has NO French translation
        $moduleData2 = new ModuleData('web_list', ['language' => $storedLanguage], ['language' => -1]);
        $request = $this->createRequest(1200, $site, $moduleData2);

        // Access protected method via reflection to test the language menu configuration
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getLanguageMenuConfiguration');
        $method->setAccessible(true);

        // Set up controller state
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($controller, 1200);

        $moduleDataProperty = $reflection->getProperty('moduleData');
        $moduleDataProperty->setAccessible(true);
        $moduleDataProperty->setValue($controller, $moduleData2);

        $availableLanguages = $site->getAvailableLanguages($this->backendUser, false, 1200);
        $menuConfig = $method->invoke($controller, $availableLanguages);

        // Language should temporarily fall back to 0 (default language) for this page since no translations exist
        self::assertSame(0, $moduleData2->get('language'), 'Should fall back to default language when no translations exist on page');
    }

    #[Test]
    public function languageRestoresWhenNavigatingBackToPageWithTranslation(): void
    {
        // Select French (language 1) and navigate to page with French translation
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');
        $moduleData = new ModuleData('web_list', ['language' => 1], ['language' => -1]);

        $request = $this->createRequest(1100, $site, $moduleData);
        $controller = $this->get(RecordListController::class);
        $controller->mainAction($request);

        // Verify French is set
        self::assertSame(1, $moduleData->get('language'));
        $storedLanguage = $moduleData->get('language');

        // Navigate to page without French translation (page 1200)
        $moduleData2 = new ModuleData('web_list', ['language' => $storedLanguage], ['language' => -1]);
        $request = $this->createRequest(1200, $site, $moduleData2);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getLanguageMenuConfiguration');
        $method->setAccessible(true);

        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($controller, 1200);

        $moduleDataProperty = $reflection->getProperty('moduleData');
        $moduleDataProperty->setAccessible(true);
        $moduleDataProperty->setValue($controller, $moduleData2);

        $availableLanguages = $site->getAvailableLanguages($this->backendUser, false, 1200);
        $menuConfig = $method->invoke($controller, $availableLanguages);

        // Temporarily falls back to 0 on page without translations
        self::assertSame(0, $moduleData2->get('language'));

        // Navigate back to page WITH French translation (page 1400) using the stored preference
        $moduleData3 = new ModuleData('web_list', ['language' => $storedLanguage], ['language' => -1]);
        $idProperty->setValue($controller, 1400);
        $moduleDataProperty->setValue($controller, $moduleData3);

        $availableLanguages = $site->getAvailableLanguages($this->backendUser, false, 1400);
        $menuConfig = $method->invoke($controller, $availableLanguages);

        // Should restore to French (1) since page 1400 has French translation
        self::assertSame(1, $moduleData3->get('language'), 'Should restore to French when translation is available again');
    }

    #[Test]
    public function defaultLanguageIsUsedWhenNoTranslationsExist(): void
    {
        // Select French and navigate to page with no translations at all
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');
        $moduleData = new ModuleData('web_list', ['language' => 1], ['language' => -1]);

        $controller = $this->get(RecordListController::class);

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getLanguageMenuConfiguration');
        $method->setAccessible(true);

        // Page 1200 has no translations
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($controller, 1200);

        $moduleDataProperty = $reflection->getProperty('moduleData');
        $moduleDataProperty->setAccessible(true);
        $moduleDataProperty->setValue($controller, $moduleData);

        $availableLanguages = $site->getAvailableLanguages($this->backendUser, false, 1200);
        $menuConfig = $method->invoke($controller, $availableLanguages);

        // Should fall back to default language (0) when no translations exist
        self::assertSame(0, $moduleData->get('language'), 'Should fall back to default language when no translations exist');
    }

    #[Test]
    public function allLanguagesOptionIsOnlyAvailableWhenTranslationsExist(): void
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');
        $moduleData = new ModuleData('web_list', ['language' => -1], ['language' => -1]);

        $controller = $this->get(RecordListController::class);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getLanguageMenuConfiguration');
        $method->setAccessible(true);

        $moduleDataProperty = $reflection->getProperty('moduleData');
        $moduleDataProperty->setAccessible(true);
        $moduleDataProperty->setValue($controller, $moduleData);

        // Page 1100 has translations
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($controller, 1100);

        $availableLanguages = $site->getAvailableLanguages($this->backendUser, false, 1100);
        $menuConfig = $method->invoke($controller, $availableLanguages);

        self::assertArrayHasKey(-1, $menuConfig['language'], '"All languages" option should be available when translations exist');

        // Page 1200 has NO translations
        $moduleData = new ModuleData('web_list', ['language' => -1], ['language' => -1]);
        $moduleDataProperty->setValue($controller, $moduleData);
        $idProperty->setValue($controller, 1200);

        $availableLanguages = $site->getAvailableLanguages($this->backendUser, false, 1200);
        $menuConfig = $method->invoke($controller, $availableLanguages);

        self::assertArrayNotHasKey(-1, $menuConfig['language'], '"All languages" option should NOT be available when no translations exist');
    }

    private function createRequest(int $pageId, object $site, ModuleData $moduleData): ServerRequest
    {
        $request = (new ServerRequest('https://acme.com/typo3/module/web/list'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/typo3/module/web/list', ['_identifier' => 'web_list']))
            ->withAttribute('site', $site)
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams(['id' => $pageId]);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
