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
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\PageViewMode;
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
 * Tests for the view mode handling of the page layout module.
 *
 * Page 1100 has translations, page 1200 has none. Both belong to the same
 * multi language site, so the stored view mode is shared between them.
 */
final class PageLayoutControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const PAGE_WITH_TRANSLATIONS = 1100;
    private const PAGE_WITHOUT_TRANSLATIONS = 1200;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $this->backendUser = $this->setUpBackendUser(1);
                $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
                $factory = DataHandlerFactory::fromYamlFile(__DIR__ . '/../Fixtures/CommonScenario.yaml');
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
            ]
        );
    }

    #[Test]
    public function layoutViewIsRenderedForPageWithoutTranslations(): void
    {
        $body = $this->renderPageLayout(self::PAGE_WITHOUT_TRANSLATIONS, PageViewMode::LanguageComparisonView);

        self::assertStringNotContainsString('t3-page-column-lang-name', $body);
        self::assertStringContainsString('<colgroup>', $body);
    }

    #[Test]
    public function editPageButtonIsShownForPageWithoutTranslations(): void
    {
        $body = $this->renderPageLayout(self::PAGE_WITHOUT_TRANSLATIONS, PageViewMode::LanguageComparisonView);

        self::assertStringContainsString('actions-page-open', $body);
    }

    #[Test]
    public function layoutViewIsRenderedIfComparisonModeIsBlindedByTsConfig(): void
    {
        $this->setPageTsConfig(self::PAGE_WITH_TRANSLATIONS, 'mod.web_layout.menu.functions.2 = 0');

        $body = $this->renderPageLayout(self::PAGE_WITH_TRANSLATIONS, PageViewMode::LanguageComparisonView);

        self::assertStringNotContainsString('t3-page-column-lang-name', $body);
        self::assertStringContainsString('<colgroup>', $body);
    }

    #[Test]
    public function comparisonViewIsRenderedIfLayoutModeIsBlindedByTsConfig(): void
    {
        $this->setPageTsConfig(self::PAGE_WITH_TRANSLATIONS, 'mod.web_layout.menu.functions.1 = 0');

        $body = $this->renderPageLayout(self::PAGE_WITH_TRANSLATIONS, PageViewMode::LanguageComparisonView);

        self::assertStringContainsString('t3-page-column-lang-name', $body);
        self::assertStringNotContainsString('actions-page-open', $body);
    }

    #[Test]
    public function layoutViewIsRenderedIfTsConfigBlindsEveryMode(): void
    {
        // Layout mode blinded on a page that does not offer comparison mode either. Blinding
        // must not leave the module without a mode, so layout mode is drawn nevertheless.
        $this->setPageTsConfig(self::PAGE_WITHOUT_TRANSLATIONS, 'mod.web_layout.menu.functions.1 = 0');

        $body = $this->renderPageLayout(self::PAGE_WITHOUT_TRANSLATIONS, PageViewMode::LanguageComparisonView);

        self::assertStringNotContainsString('t3-page-column-lang-name', $body);
        self::assertStringContainsString('<colgroup>', $body);
    }

    #[Test]
    public function comparisonViewIsRenderedForPageWithTranslations(): void
    {
        $body = $this->renderPageLayout(self::PAGE_WITH_TRANSLATIONS, PageViewMode::LanguageComparisonView);

        self::assertStringContainsString('t3-page-column-lang-name', $body);
        self::assertStringNotContainsString('actions-page-open', $body);
    }

    #[Test]
    public function storedViewModeIsKeptWhenPageWithoutTranslationsIsRendered(): void
    {
        $moduleData = new ModuleData('web_layout', ['viewMode' => PageViewMode::LanguageComparisonView->value], []);

        $this->get(PageLayoutController::class)
            ->mainAction($this->createRequest(self::PAGE_WITHOUT_TRANSLATIONS, $moduleData));

        self::assertSame(PageViewMode::LanguageComparisonView->value, (int)$moduleData->get('viewMode'));
        self::assertSame(
            PageViewMode::LanguageComparisonView->value,
            (int)($this->backendUser->getModuleData('web_layout')['viewMode'] ?? 0)
        );
    }

    private function renderPageLayout(int $pageId, PageViewMode $storedViewMode): string
    {
        $moduleData = new ModuleData('web_layout', ['viewMode' => $storedViewMode->value], []);
        $response = $this->get(PageLayoutController::class)->mainAction($this->createRequest($pageId, $moduleData));

        return (string)$response->getBody();
    }

    private function createRequest(int $pageId, ModuleData $moduleData): ServerRequest
    {
        $request = (new ServerRequest('https://example.com/typo3/module/web/layout'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/typo3/module/web/layout', ['_identifier' => 'web_layout']))
            ->withAttribute('site', $this->get(SiteFinder::class)->getSiteByIdentifier('test-site'))
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams(['id' => $pageId]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return $request->withAttribute(
            'pageContext',
            $this->get(PageContextFactory::class)->createFromRequest($request, $pageId, $this->backendUser)
        );
    }

    private function setPageTsConfig(int $pageId, string $tsConfig): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->update('pages', ['TSconfig' => $tsConfig], ['uid' => $pageId]);
    }
}
