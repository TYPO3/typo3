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

namespace TYPO3\CMS\Recycler\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Recycler\Controller\RecyclerModuleController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecyclerModuleControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
        'DE' => ['id' => 2, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
        'ES' => ['id' => 3, 'title' => 'Spanish', 'locale' => 'es_ES.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['recycler'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/language_records.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->writeSiteConfiguration(
            'test-site',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr/', ['EN']),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN']),
                $this->buildLanguageConfiguration('ES', '/es/', ['EN']),
            ]
        );
    }

    #[Test]
    public function languageSelectorOffersAllLanguagesAndEverySiteLanguage(): void
    {
        $content = $this->renderModule('');

        self::assertMatchesRegularExpression('/<a[^>]*href="[^"]*language=&[^"]*"[^>]*>(?:(?!<\/a>).)*All languages<\/a>/s', $content);
        self::assertMatchesRegularExpression('/<a[^>]*href="[^"]*language=0&[^"]*"[^>]*>(?:(?!<\/a>).)*English<\/a>/s', $content);
        self::assertMatchesRegularExpression('/<a[^>]*href="[^"]*language=1&[^"]*"[^>]*>(?:(?!<\/a>).)*French<\/a>/s', $content);
        self::assertMatchesRegularExpression('/<a[^>]*href="[^"]*language=2&[^"]*"[^>]*>(?:(?!<\/a>).)*German<\/a>/s', $content);
        self::assertMatchesRegularExpression('/<a[^>]*href="[^"]*language=3&[^"]*"[^>]*>(?:(?!<\/a>).)*Spanish<\/a>/s', $content);
        self::assertStringNotContainsString('typo3-backend-localization-button', $content);
    }

    #[Test]
    public function noLanguageSelectionShowsAllDeletedRecords(): void
    {
        $content = $this->renderModule('');

        self::assertMatchesRegularExpression('/<button[^>]*>.*All languages.*<\/button>/s', $content);
        self::assertStringContainsString('Deleted content EN', $content);
        self::assertStringContainsString('Deleted content FR', $content);
        self::assertStringContainsString('Deleted content DE', $content);
        self::assertStringContainsString('Deleted content all languages', $content);
        self::assertStringContainsString('Deleted template without language', $content);
    }

    #[Test]
    public function languageSelectionRestrictsDeletedRecordsToSelectedLanguage(): void
    {
        $content = $this->renderModule('2');

        self::assertMatchesRegularExpression('/<button[^>]*>.*German.*<\/button>/s', $content);
        self::assertStringNotContainsString('Deleted content EN', $content);
        self::assertStringNotContainsString('Deleted content FR', $content);
        self::assertStringContainsString('Deleted content DE', $content);
        self::assertStringContainsString('Deleted content all languages', $content);
        self::assertStringContainsString('Deleted template without language', $content);
    }

    #[Test]
    public function languageSelectionOfDefaultLanguageExcludesTranslations(): void
    {
        $content = $this->renderModule('0');

        self::assertStringContainsString('Deleted content EN', $content);
        self::assertStringNotContainsString('Deleted content FR', $content);
        self::assertStringNotContainsString('Deleted content DE', $content);
        self::assertStringContainsString('Deleted content all languages', $content);
        self::assertStringContainsString('Deleted template without language', $content);
    }

    private function renderModule(string $language): string
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('test-site');
        $moduleData = new ModuleData('recycler', ['language' => $language], [
            'depthSelection' => 0,
            'tableSelection' => '',
            'language' => '',
        ]);
        $request = new ServerRequest('https://example.com/typo3/module/web/recycler')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/module/web/recycler', ['_identifier' => 'recycler', 'packageName' => 'typo3/cms-recycler']))
            ->withAttribute('site', $site)
            ->withAttribute('moduleData', $moduleData)
            ->withQueryParams(['id' => 1]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        return (string)$this->get(RecyclerModuleController::class)->handleRequest($request)->getBody();
    }
}
