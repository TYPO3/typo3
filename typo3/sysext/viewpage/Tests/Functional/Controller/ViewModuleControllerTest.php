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

namespace TYPO3\CMS\Viewpage\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\User\SharedUserPreferences;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Viewpage\Controller\ViewModuleController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ViewModuleControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['viewpage'];

    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages_languages.csv');

        $this->writeSiteConfiguration(
            'test-site',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
            ]
        );
    }

    #[Test]
    public function controllerUsesLanguageFromPageContext(): void
    {
        $sharedPreferences = $this->get(SharedUserPreferences::class);
        $sharedPreferences->setPageLanguages($this->backendUser, 1, [1]); // French

        $request = $this->createRequest(1);

        $controller = $this->get(ViewModuleController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();
        self::assertStringContainsString('French Root Page', $content);
    }

    #[Test]
    public function controllerFallsBackToDefaultLanguageWhenSelectedNotAvailable(): void
    {
        $sharedPreferences = $this->get(SharedUserPreferences::class);
        $sharedPreferences->setPageLanguages($this->backendUser, 1, [99]);

        $request = $this->createRequest(1);

        $controller = $this->get(ViewModuleController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();
        self::assertStringContainsString('Default Root Page', $content);
    }

    #[Test]
    public function controllerFallsBackToDefaultLanguageWhenMultipleAreSelected(): void
    {
        $sharedPreferences = $this->get(SharedUserPreferences::class);
        $sharedPreferences->setPageLanguages($this->backendUser, 1, [1, 2]);

        $request = $this->createRequest(1);

        $controller = $this->get(ViewModuleController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();
        self::assertStringContainsString('Default Root Page', $content);
    }

    #[Test]
    public function controllerUsesLanguagesArrayParameterInUrls(): void
    {
        $request = $this->createRequest(1);

        $controller = $this->get(ViewModuleController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();

        // Language selector links should use 'languages' array parameter
        self::assertMatchesRegularExpression('/languages%5B0%5D=\d/', $content);
    }

    #[Test]
    public function controllerShowsEmptyPageForInvalidPageId(): void
    {
        // Page 5 does not exist
        $request = $this->createRequest(5);

        $controller = $this->get(ViewModuleController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();

        self::assertStringContainsString('Please select a page with a valid page type', $content);
    }

    #[Test]
    public function controllerShowsEmptyPageForInvalidDoktype(): void
    {
        // Page 4 is a sysfolder (doktype 254)
        $request = $this->createRequest(4);

        $controller = $this->get(ViewModuleController::class);
        $response = $controller->handleRequest($request);

        $content = (string)$response->getBody();

        self::assertStringContainsString('Please select a page with a valid page type', $content);
    }

    #[Test]
    public function controllerRequiresPageContext(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1763630591);

        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $module = $this->get(ModuleProvider::class)->getModule('page_preview');
        $moduleData = new ModuleData('page_preview', ['language' => 0]);
        $route = new Route('/module/page-preview', [
            'packageName' => 'typo3/cms-viewpage',
            '_identifier' => 'page_preview',
        ]);

        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('route', $route)
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams(['id' => 1]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $controller = $this->get(ViewModuleController::class);
        $controller->handleRequest($request);
    }

    private function createRequest(int $pageId, array $additionalParams = []): ServerRequest
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $module = $this->get(ModuleProvider::class)->getModule('page_preview');
        $moduleData = new ModuleData('page_preview', ['language' => 0]);
        $route = new Route('/module/page-preview', [
            'packageName' => 'typo3/cms-viewpage',
            '_identifier' => 'page_preview',
        ]);

        $queryParams = array_merge(['id' => $pageId], $additionalParams);

        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('route', $route)
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams($queryParams);

        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, $pageId, $this->backendUser);
        $request = $request->withAttribute('pageContext', $pageContext);

        return $request;
    }
}
