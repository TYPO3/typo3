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
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
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
