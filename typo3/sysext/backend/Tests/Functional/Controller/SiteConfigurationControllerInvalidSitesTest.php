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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\SiteConfigurationController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteResult;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteConfigurationControllerInvalidSitesTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Copied instead of linked, as saving writes to the site configuration folder
        foreach (glob(__DIR__ . '/Fixtures/InvalidSites/sites/*/config.yaml') as $file) {
            $targetFolder = $this->instancePath . '/typo3conf/sites/' . basename(dirname($file));
            GeneralUtility::mkdir_deep($targetFolder);
            copy($file, $targetFolder . '/config.yaml');
        }
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InvalidSites/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function overviewListsInvalidSitesWithoutOfferingANewSiteForTheirRootPage(): void
    {
        $response = $this->get(SiteConfigurationController::class)->overviewAction(
            $this->createRequest('site_configuration', '/module/site/configuration')
        );
        $html = (string)$response->getBody();

        self::assertStringContainsString('Failing site configurations', $html);
        self::assertStringContainsString('invalid-language-base', $html);
        self::assertStringContainsString('languages.1.base', $html);
        self::assertStringContainsString('The parsedUri &quot;https://&quot; appears to be malformed', $html);
        self::assertStringContainsString('broken-yaml', $html);
        self::assertStringContainsString('pageUid=3', $html, 'Root page without site offers a new site');
        self::assertStringNotContainsString('pageUid=2', $html, 'Root page of invalid site must not offer a new site');
    }

    #[Test]
    public function editViewOfInvalidSiteShowsErrorAndField(): void
    {
        $response = $this->get(SiteConfigurationController::class)->editAction(
            $this->createRequest('site_configuration.edit', '/module/site/configuration/edit', ['site' => 'invalid-language-base'])
        );
        $html = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('This site configuration contains errors', $html);
        self::assertStringContainsString('Available Languages for this Site › German › Entry Point', $html);
        self::assertStringContainsString('languages.1.base', $html);
        self::assertStringContainsString('https://language.example.com/', $html, 'Form contains the stored configuration');
    }

    public static function settingsActionDataProvider(): iterable
    {
        yield 'valid site' => ['valid', true];
        yield 'invalid site' => ['invalid-language-base', false];
    }

    #[DataProvider('settingsActionDataProvider')]
    #[Test]
    public function editViewOffersSettingsOnlyForResolvedSites(string $identifier, bool $offersSettings): void
    {
        $response = $this->get(SiteConfigurationController::class)->editAction(
            $this->createRequest('site_configuration.edit', '/module/site/configuration/edit', ['site' => $identifier])
        );
        $html = (string)$response->getBody();

        self::assertSame(200, $response->getStatusCode());
        if ($offersSettings) {
            self::assertStringContainsString('/module/site/configuration/editSettings', $html);
        } else {
            self::assertStringNotContainsString('/module/site/configuration/editSettings', $html);
        }
    }

    #[Test]
    public function detailViewOfInvalidSiteRedirectsToEditView(): void
    {
        $response = $this->get(SiteConfigurationController::class)->detailAction(
            $this->createRequest('site_configuration.detail', '/module/site/configuration/detail', ['site' => 'invalid-language-base'])
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertStringContainsString('/module/site/configuration/edit', $response->getHeaderLine('Location'));
        self::assertStringContainsString('site=invalid-language-base', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function savingInvalidSiteUpdatesItsConfiguration(): void
    {
        $this->get(SiteConfigurationController::class)->saveAction(
            $this->createRequest('site_configuration.save', '/module/site/configuration/save')->withParsedBody([
                '_savedok' => 1,
                'rootPageId' => 2,
                'data' => [
                    'site' => [
                        2 => [
                            'identifier' => 'invalid-language-base',
                            'base' => 'https://language.example.com/',
                            'languages' => '0,1',
                        ],
                    ],
                    'site_language' => [
                        1 => [
                            'languageId' => 1,
                            'title' => 'German',
                            'locale' => 'de_DE.UTF-8',
                            'base' => '/de/',
                        ],
                    ],
                ],
            ])
        );

        $siteConfiguration = $this->get(SiteConfiguration::class);
        $sites = $siteConfiguration->getAllExistingSites(false);
        self::assertArrayNotHasKey('invalid-language-base', $siteConfiguration->getInvalidSites());
        self::assertArrayHasKey('invalid-language-base', $sites);
        self::assertSame('https://language.example.com/de/', (string)$sites['invalid-language-base']->getLanguageById(1)->getBase());
    }

    #[Test]
    public function savingInvalidSiteWithNewLanguagePreservesExistingLanguageIds(): void
    {
        $this->get(SiteConfigurationController::class)->saveAction(
            $this->createRequest('site_configuration.save', '/module/site/configuration/save')->withParsedBody([
                '_savedok' => 1,
                'rootPageId' => 2,
                'data' => [
                    'site' => [
                        2 => [
                            'identifier' => 'invalid-language-base',
                            'base' => 'https://language.example.com/',
                            'languages' => '0,1,NEW1',
                        ],
                    ],
                    'site_language' => [
                        1 => [
                            'languageId' => 1,
                            'title' => 'German',
                            'locale' => 'de_DE.UTF-8',
                            'base' => '/de/',
                        ],
                        'NEW1' => [
                            'languageId' => PHP_INT_MAX,
                            'title' => 'French',
                            'locale' => 'fr_FR.UTF-8',
                            'base' => '/fr/',
                        ],
                    ],
                ],
            ])
        );

        $configuration = $this->get(SiteConfiguration::class)->load('invalid-language-base');
        self::assertSame([0, 1, 2], array_column($configuration['languages'], 'languageId'));
    }

    #[Test]
    public function newSiteDoesNotReuseIdentifierOfInvalidSite(): void
    {
        $this->get(SiteConfigurationController::class)->saveAction(
            $this->createRequest('site_configuration.save', '/module/site/configuration/save')->withParsedBody([
                '_savedok' => 1,
                'rootPageId' => 3,
                'data' => [
                    'site' => [
                        'NEW1' => [
                            'identifier' => 'broken-yaml',
                            'base' => 'https://new.example.com/',
                            'languages' => 'NEW2',
                        ],
                    ],
                    'site_language' => [
                        'NEW2' => [
                            'languageId' => 0,
                            'title' => 'English',
                            'locale' => 'en_US.UTF-8',
                            'base' => '/',
                        ],
                    ],
                ],
            ])
        );

        $siteConfiguration = $this->get(SiteConfiguration::class);
        $siteConfiguration->getAllExistingSites(false);
        self::assertArrayHasKey('broken-yaml', $siteConfiguration->getInvalidSites(), 'Invalid site must not be overwritten');
        self::assertFileExists($this->instancePath . '/typo3conf/sites/broken-yaml/config.yaml');
        self::assertStringContainsString('yaml.example.com', (string)file_get_contents($this->instancePath . '/typo3conf/sites/broken-yaml/config.yaml'));
    }

    private function createRequest(string $routeIdentifier, string $path, array $queryParams = []): ServerRequestInterface
    {
        $module = $this->get(ModuleProvider::class)->getModule('site_configuration', $GLOBALS['BE_USER']);
        $route = new Route($path, [
            '_identifier' => $routeIdentifier,
            'module' => $module,
        ]);
        $request = (new ServerRequest('https://example.com/typo3' . $path))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('routing', new RouteResult($route))
            ->withAttribute('module', $module)
            ->withAttribute('moduleData', new ModuleData('site_configuration', []))
            ->withQueryParams($queryParams);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
