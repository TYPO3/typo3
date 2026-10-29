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
use TYPO3\CMS\Backend\View\SetupModuleViewMode;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteConfigurationControllerTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SiteConfigurationControllerPages.csv');
    }

    #[Test]
    public function saveKeepsExistingSiteIfLanguageZeroIsRemoved(): void
    {
        $this->writeSiteWithLanguages([0, 1]);

        $this->get(SiteConfigurationController::class)->saveAction($this->createSaveRequest(1, 'main', [
            1 => ['languageId' => 1, 'title' => 'German', 'base' => '/de/', 'locale' => 'de_DE.UTF-8'],
        ]));

        self::assertSame([0, 1], $this->getStoredLanguageIds('main'));
        self::assertSame(['Site configuration not saved'], $this->getFlashMessageTitles());
    }

    #[Test]
    public function saveStoresExistingSiteWithLanguageZero(): void
    {
        $this->writeSiteWithLanguages([0, 1]);

        $this->get(SiteConfigurationController::class)->saveAction($this->createSaveRequest(1, 'main', [
            0 => ['languageId' => 0, 'title' => 'English', 'base' => '/', 'locale' => 'en_US.UTF-8'],
        ]));

        self::assertSame([0], $this->getStoredLanguageIds('main'));
        self::assertSame([], $this->getFlashMessageTitles());
    }

    #[Test]
    public function saveRefusedWithRenamedIdentifierShowsFormOfStoredSiteAgain(): void
    {
        $this->writeSiteWithLanguages([0, 1]);

        $response = $this->get(SiteConfigurationController::class)->saveAction($this->createSaveRequest(1, 'renamed', [
            1 => ['languageId' => 1, 'title' => 'German', 'base' => '/de/', 'locale' => 'de_DE.UTF-8'],
        ]));

        self::assertSame(['main'], array_keys($this->get(SiteConfiguration::class)->resolveAllExistingSitesRaw()));
        self::assertStringContainsString('site=main', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function saveCreatesSiteWithSingleLanguageAsLanguageZero(): void
    {
        $this->get(SiteConfigurationController::class)->saveAction($this->createSaveRequest(1, 'new-site', [
            'NEW1' => ['languageId' => 1, 'title' => 'German', 'base' => '/', 'locale' => 'de_DE.UTF-8'],
        ]));

        self::assertSame([0], $this->getStoredLanguageIds('new-site'));
        self::assertSame([], $this->getFlashMessageTitles());
    }

    #[Test]
    public function saveDoesNotCreateSiteWithoutLanguageZeroAndShowsCreationFormAgain(): void
    {
        $response = $this->get(SiteConfigurationController::class)->saveAction($this->createSaveRequest(1, 'new-site', [
            'NEW1' => ['languageId' => 1, 'title' => 'German', 'base' => '/de/', 'locale' => 'de_DE.UTF-8'],
            'NEW2' => ['languageId' => 2, 'title' => 'French', 'base' => '/fr/', 'locale' => 'fr_FR.UTF-8'],
        ]));

        self::assertArrayNotHasKey('new-site', $this->get(SiteConfiguration::class)->resolveAllExistingSitesRaw());
        self::assertSame(['Site configuration not saved'], $this->getFlashMessageTitles());
        $location = $response->getHeaderLine('Location');
        self::assertStringContainsString('pageUid=1', $location);
        self::assertStringNotContainsString('site=', $location);
    }

    #[Test]
    public function detailShowsErrorIfLanguageZeroIsNotConfigured(): void
    {
        $this->writeSiteWithLanguages([1, 2]);

        $response = $this->get(SiteConfigurationController::class)->detailAction($this->createModuleRequest('detail', ['site' => 'main']));

        self::assertStringContainsString('Default language missing', (string)$response->getBody());
    }

    #[Test]
    public function detailShowsNoErrorIfLanguageZeroIsConfigured(): void
    {
        $this->writeSiteWithLanguages([1, 0]);

        $response = $this->get(SiteConfigurationController::class)->detailAction($this->createModuleRequest('detail', ['site' => 'main']));

        self::assertStringNotContainsString('Default language missing', (string)$response->getBody());
    }

    public static function overviewTilesDataProvider(): array
    {
        return [
            'language 0 not configured first' => [[1, 0], 'Language 0', 'Language 1'],
            'language 0 not configured' => [[1, 2], 'Language 1', 'Language 2'],
        ];
    }

    /**
     * @param list<int> $languageIds
     */
    #[DataProvider('overviewTilesDataProvider')]
    #[Test]
    public function overviewTilesShowDefaultLanguage(array $languageIds, string $expectedLanguageTitle, string $otherLanguageTitle): void
    {
        $this->writeSiteWithLanguages($languageIds);
        $request = $this->createModuleRequest('', []);
        $request = $request->withAttribute(
            'moduleData',
            ModuleData::createFromModule($request->getAttribute('module'), ['viewMode' => SetupModuleViewMode::TILES->value])
        );

        $body = (string)$this->get(SiteConfigurationController::class)->overviewAction($request)->getBody();

        self::assertStringContainsString($expectedLanguageTitle, $body);
        self::assertStringNotContainsString($otherLanguageTitle, $body);
    }

    public static function overviewDataProvider(): array
    {
        return [
            'list without language 0' => [SetupModuleViewMode::LIST, [1, 2], true],
            'list with language 0' => [SetupModuleViewMode::LIST, [1, 0], false],
            'tiles without language 0' => [SetupModuleViewMode::TILES, [1, 2], true],
            'tiles with language 0' => [SetupModuleViewMode::TILES, [1, 0], false],
        ];
    }

    /**
     * @param list<int> $languageIds
     */
    #[DataProvider('overviewDataProvider')]
    #[Test]
    public function overviewShowsErrorOnlyIfLanguageZeroIsNotConfigured(SetupModuleViewMode $viewMode, array $languageIds, bool $expectError): void
    {
        $this->writeSiteWithLanguages($languageIds);
        $request = $this->createModuleRequest('', []);
        $request = $request->withAttribute(
            'moduleData',
            ModuleData::createFromModule($request->getAttribute('module'), ['viewMode' => $viewMode->value])
        );

        $body = (string)$this->get(SiteConfigurationController::class)->overviewAction($request)->getBody();

        self::assertStringContainsString('https://example.com/', $body);
        self::assertSame($expectError, str_contains($body, 'Default language missing'));
    }

    /**
     * @param list<int> $languageIds
     */
    private function writeSiteWithLanguages(array $languageIds): void
    {
        $languages = [];
        foreach ($languageIds as $languageId) {
            $languages[] = [
                'languageId' => $languageId,
                'title' => 'Language ' . $languageId,
                'base' => '/' . $languageId . '/',
                'locale' => 'en_US.UTF-8',
                'flag' => 'us',
            ];
        }
        $this->get(SiteWriter::class)->write('main', [
            'rootPageId' => 1,
            'base' => 'https://example.com/',
            'languages' => $languages,
        ]);
    }

    /**
     * @return list<int>
     */
    private function getStoredLanguageIds(string $siteIdentifier): array
    {
        $configuration = $this->get(SiteConfiguration::class)->load($siteIdentifier);
        return array_map(static fn(array $language): int => (int)$language['languageId'], $configuration['languages']);
    }

    /**
     * @return list<string>
     */
    private function getFlashMessageTitles(): array
    {
        $messages = $this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages();
        return array_map(static fn(FlashMessage $message): string => $message->getTitle(), $messages);
    }

    /**
     * @param array<int|string, array<string, int|string>> $siteLanguages
     */
    private function createSaveRequest(int $rootPageId, string $siteIdentifier, array $siteLanguages): ServerRequestInterface
    {
        return $this->createModuleRequest('save', [])
            ->withMethod('POST')
            ->withParsedBody([
                '_savedok' => '1',
                'rootPageId' => (string)$rootPageId,
                'data' => [
                    'site' => [
                        $rootPageId => [
                            'identifier' => $siteIdentifier,
                            'base' => 'https://example.com/',
                            'languages' => implode(',', array_keys($siteLanguages)),
                        ],
                    ],
                    'site_language' => $siteLanguages,
                ],
            ]);
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function createModuleRequest(string $action, array $queryParams): ServerRequestInterface
    {
        $module = $this->get(ModuleProvider::class)->getModule('site_configuration', $GLOBALS['BE_USER']);
        $path = '/module/site/configuration' . ($action !== '' ? '/' . $action : '');
        $route = new Route($path, [
            '_identifier' => 'site_configuration' . ($action !== '' ? '.' . $action : ''),
            'module' => $module,
        ]);
        $request = new ServerRequest('https://example.com/typo3' . $path)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', $route)
            ->withAttribute('routing', new RouteResult($route))
            ->withAttribute('module', $module)
            ->withQueryParams($queryParams);

        return $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }
}
