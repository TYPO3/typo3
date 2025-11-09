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

namespace TYPO3\CMS\Backend\Tests\Functional\Context;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Domain\Model\Language\LanguageStatus;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Service\PageLanguageInformationService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for PageContext and PageContextFactory
 */
final class PageContextTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Authentication/Fixtures/be_users.csv');
        $this->backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        // Import pages with different translation configurations
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_with_translations.csv');
    }

    /**
     * Data provider for language filtering when navigating between pages
     *
     * @return array<string, array{pageId: int, requestedLanguages: int[], expectedLanguages: int[]}>
     */
    public static function languageFilteringScenarios(): array
    {
        return [
            'filters_to_existing_translations_only' => [
                'pageId' => 3, // Page without translations (only L=0 exists)
                'requestedLanguages' => [0, 1, 2],
                'expectedLanguages' => [0], // Only default exists on this page
            ],
            'filters_out_non_existing_translations' => [
                'pageId' => 1, // Page with German translation (L=0, L=1 exist)
                'requestedLanguages' => [0, 1, 2, 99],
                'expectedLanguages' => [0, 1], // Only 0 and 1 exist on this page
            ],
            'fallback_to_default_when_no_valid_languages' => [
                'pageId' => 3,
                'requestedLanguages' => [99], // Language doesn't exist
                'expectedLanguages' => [0], // Falls back to default
            ],
        ];
    }

    #[Test]
    #[DataProvider('languageFilteringScenarios')]
    public function filtersLanguagesToExistingTranslations(
        int $pageId,
        array $requestedLanguages,
        array $expectedLanguages
    ): void {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $moduleData = new ModuleData('web_layout', []);
        $moduleData->set('languages', $requestedLanguages);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('moduleData', $moduleData)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            $pageId,
            $this->backendUser
        );

        self::assertSame($expectedLanguages, $pageContext->selectedLanguageIds);
        self::assertSame($expectedLanguages, $moduleData->get('languages'));
    }

    #[Test]
    public function handlesEmptyLanguageSelection(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
            ],
        ]);

        $moduleData = new ModuleData('test_module', []);
        $moduleData->set('languages', []);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('moduleData', $moduleData)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            3,
            $this->backendUser
        );

        self::assertSame([0], $pageContext->selectedLanguageIds);
    }

    #[Test]
    public function createsContextForPageZero(): void
    {
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', new NullSite())
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            0,
            $this->backendUser
        );

        self::assertSame(0, $pageContext->pageId);
        self::assertSame(['uid' => 0, 'pid' => 0, 'title' => 'Root'], $pageContext->pageRecord);
        self::assertInstanceOf(NullSite::class, $pageContext->site);
    }

    #[Test]
    public function respectsDisableLanguagesTSconfigForNullSite(): void
    {
        $this->backendUser->setAndSaveSessionData('core.db.pagesTSconfig', [
            0 => [
                'mod.' => [
                    'SHARED.' => [
                        'disableLanguages' => '1,2',
                    ],
                ],
            ],
        ]);

        $service = $this->get(PageLanguageInformationService::class);
        $languageInfo = $service->getLanguageInformationForPage(0, new NullSite(), $this->backendUser);

        self::assertTrue(isset($languageInfo->availableLanguages[0]));
        self::assertFalse(isset($languageInfo->availableLanguages[1]), 'Language 1 should be excluded by TSconfig');
        self::assertFalse(isset($languageInfo->availableLanguages[2]), 'Language 2 should be excluded by TSconfig');
    }

    #[Test]
    public function classifiesLanguagesCorrectlyForNullSite(): void
    {
        $service = $this->get(PageLanguageInformationService::class);
        $languageInfo = $service->getLanguageInformationForPage(0, new NullSite(), $this->backendUser);

        self::assertSame(LanguageStatus::Existing, $languageInfo->getLanguageStatus(0));
        foreach ($languageInfo->availableLanguages as $languageId => $siteLanguage) {
            self::assertSame($languageId, $siteLanguage->getLanguageId());
        }
    }

    #[Test]
    public function createWithLanguagesWorksForPageZero(): void
    {
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', new NullSite())
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createWithLanguages(
            $request,
            0,
            [0, 1],
            $this->backendUser
        );

        self::assertSame(0, $pageContext->pageId);
        self::assertSame([0, 1], $pageContext->selectedLanguageIds);
        self::assertInstanceOf(NullSite::class, $pageContext->site);
    }

    #[Test]
    public function retrievesDisableLanguagesTSconfigCorrectlyForPageZero(): void
    {
        $pageTs = BackendUtility::getPagesTSconfig(0);
        $disableLanguages = $pageTs['mod.']['SHARED.']['disableLanguages'] ?? '';
        self::assertSame('', $disableLanguages);
    }

    #[Test]
    public function pageZeroValidatesAgainstSiteLanguagesNotExistingTranslations(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [0, 1, 2]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            0, // Page 0 has no translations, but child records might have
            $this->backendUser
        );

        // Page 0 should allow all site-configured languages (for displaying child records)
        // NOT just languages with translations (page 0 itself has no translations)
        self::assertSame([0, 1, 2], $pageContext->selectedLanguageIds);
    }

    #[Test]
    public function preservesLanguagePreferenceAcrossPageNavigation(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $pageContextFactory = $this->get(PageContextFactory::class);

        // Step 1: User explicitly selects L=1 on page 1 (which has L=1 translation)
        $requestWithLanguage = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [1]]); // Explicit selection via request

        $pageContext1 = $pageContextFactory->createFromRequest(
            $requestWithLanguage,
            1,
            $this->backendUser
        );

        // Verify: L=1 is selected and stored as preference
        self::assertSame([1], $pageContext1->selectedLanguageIds);

        // Step 2: Navigate to page 3 (NO L=1 translation) without explicit language parameter
        $requestWithoutLanguage = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContext3 = $pageContextFactory->createFromRequest(
            $requestWithoutLanguage,
            3, // Page 3 has NO translations
            $this->backendUser
        );

        // Verify: Falls back to L=0 (page doesn't have L=1)
        // But preference is NOT overwritten (no explicit request parameter)
        self::assertSame([0], $pageContext3->selectedLanguageIds);

        // Step 3: Navigate back to page 1 (has L=1 translation)
        $pageContext1Again = $pageContextFactory->createFromRequest(
            $requestWithoutLanguage,
            1, // Back to page 1
            $this->backendUser
        );

        // Verify: L=1 is restored from preserved preference
        self::assertSame([1], $pageContext1Again->selectedLanguageIds);
    }

    #[Test]
    public function doesNotPreservePreferenceWhenExplicitlyChangedViaRequest(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $pageContextFactory = $this->get(PageContextFactory::class);

        // Step 1: Select L=1
        $request1 = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [1]]);

        $pageContextFactory->createFromRequest($request1, 4, $this->backendUser);

        // Step 2: Explicitly change to L=2 via request
        $request2 = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [2]]); // Explicit change

        $pageContext2 = $pageContextFactory->createFromRequest($request2, 4, $this->backendUser);

        // Verify: New preference is stored
        self::assertSame([2], $pageContext2->selectedLanguageIds);

        // Step 3: Navigate without explicit parameter
        $request3 = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContext3 = $pageContextFactory->createFromRequest($request3, 4, $this->backendUser);

        // Verify: L=2 is used (new preference), not L=1 (old preference)
        self::assertSame([2], $pageContext3->selectedLanguageIds);
    }

    #[Test]
    public function getPrimaryLanguageIdReturnsFirstSelectedLanguage(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [1, 2]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            4, // Page 4 has translations for L=1 and L=2
            $this->backendUser
        );

        self::assertSame(1, $pageContext->getPrimaryLanguageId());
    }

    #[Test]
    public function hasMultipleLanguagesSelectedReturnsTrueForMultipleLanguages(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [0, 1]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1, // Page 1 has L=0 and L=1
            $this->backendUser
        );

        self::assertTrue($pageContext->hasMultipleLanguagesSelected());
    }

    #[Test]
    public function hasMultipleLanguagesSelectedReturnsFalseForSingleLanguage(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [0]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1,
            $this->backendUser
        );

        self::assertFalse($pageContext->hasMultipleLanguagesSelected());
    }

    #[Test]
    public function isLanguageSelectedReturnsTrueForSelectedLanguage(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [0, 1, 2]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            4, // Page 4 has all translations
            $this->backendUser
        );

        self::assertTrue($pageContext->isLanguageSelected(1));
    }

    #[Test]
    public function isLanguageSelectedReturnsFalseForNonSelectedLanguage(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
                ['languageId' => 2, 'locale' => 'fr-FR', 'base' => '/fr', 'title' => 'French'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [0, 1]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1, // Page 1 has L=0 and L=1
            $this->backendUser
        );

        self::assertFalse($pageContext->isLanguageSelected(2));
    }

    #[Test]
    public function getPageTitleReturnsDefaultTitle(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1,
            $this->backendUser
        );

        self::assertSame('Page with German translation', $pageContext->getPageTitle());
    }

    #[Test]
    public function getPageTitleReturnsTranslatedTitle(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [1]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1, // Page 1 has German translation
            $this->backendUser
        );

        self::assertSame('German translation of page 1', $pageContext->getPageTitle(1));
    }

    #[Test]
    public function isDefaultLanguageSelectedReturnsTrueWhenDefaultIsSelected(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [0, 1]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1,
            $this->backendUser
        );

        self::assertTrue($pageContext->isDefaultLanguageSelected());
    }

    #[Test]
    public function isDefaultLanguageSelectedReturnsFalseWhenDefaultIsNotSelected(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
                ['languageId' => 1, 'locale' => 'de-DE', 'base' => '/de', 'title' => 'German'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()))
            ->withQueryParams(['languages' => [1]]);

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest(
            $request,
            1,
            $this->backendUser
        );

        self::assertFalse($pageContext->isDefaultLanguageSelected());
    }

    #[Test]
    public function contextProvidesRootLine(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 1, $this->backendUser);

        self::assertNotEmpty($pageContext->rootLine);
        // Rootline contains pages from page up to root (page 1 is last element)
        $pageIds = array_column($pageContext->rootLine, 'uid');
        self::assertContains(1, $pageIds);
        self::assertContains(0, $pageIds); // Root page should be included
    }

    #[Test]
    public function contextProvidesPageTsConfig(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 1, $this->backendUser);

        // Verify dots are removed from keys
        self::assertArrayNotHasKey('mod.', $pageContext->pageTsConfig);
        if (!empty($pageContext->pageTsConfig)) {
            self::assertArrayHasKey('mod', $pageContext->pageTsConfig);
        }
    }

    #[Test]
    public function contextProvidesModuleTsConfig(): void
    {
        // Set up page with TSconfig
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $connection->update(
            'pages',
            ['TSconfig' => "mod.web_layout {\n  defaultLanguageBinding = 1\n}\nmod.SHARED {\n  colPos_list = 0,1\n}"],
            ['uid' => 1]
        );

        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 1, $this->backendUser);

        $webLayoutConfig = $pageContext->getModuleTsConfig('web_layout');
        $sharedConfig = $pageContext->getModuleTsConfig('SHARED');

        self::assertSame('1', $webLayoutConfig['defaultLanguageBinding'] ?? null);
        self::assertSame('0,1', $sharedConfig['colPos_list'] ?? null);
    }

    #[Test]
    public function contextProvidesEmptyArrayForNonExistentModule(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 1, $this->backendUser);

        $nonExistentConfig = $pageContext->getModuleTsConfig('non_existent_module');

        self::assertEmpty($nonExistentConfig);
    }

    #[Test]
    public function returnsNullableContextForNoAccessPage(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        // Create backend user with no page permissions (user 2 has limited permissions)
        $limitedUser = $this->setUpBackendUser(2);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 1, $limitedUser);

        // Should return context with null values instead of throwing exception
        self::assertFalse($pageContext->isAccessible());
        self::assertNull($pageContext->pageId);
        self::assertNull($pageContext->pageRecord);
        self::assertSame([], $pageContext->rootLine);
        self::assertSame([0], $pageContext->selectedLanguageIds);
        // No permissions when no access
        self::assertSame(0, $pageContext->pagePermissions->__toInt());
    }

    #[Test]
    public function contextProvidesPagePermissions(): void
    {
        $site = new Site('test-site', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'locale' => 'en-US', 'base' => '/', 'title' => 'English'],
            ],
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $site)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 1, $this->backendUser);

        self::assertTrue($pageContext->pagePermissions->showPagePermissionIsGranted());
        self::assertTrue($pageContext->pagePermissions->editPagePermissionIsGranted());
    }

    #[Test]
    public function contextProvidesPagePermissionsForPageZero(): void
    {
        $editorUser = $this->setUpBackendUser(2);
        $request = (new ServerRequest('https://example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', new NullSite())
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));

        $pageContextFactory = $this->get(PageContextFactory::class);
        $pageContext = $pageContextFactory->createFromRequest($request, 0, $editorUser);

        // Non-admin user has no permissions on page 0 (minimal page record)
        self::assertFalse($pageContext->pagePermissions->showPagePermissionIsGranted());
        self::assertFalse($pageContext->pagePermissions->editPagePermissionIsGranted());
    }
}
