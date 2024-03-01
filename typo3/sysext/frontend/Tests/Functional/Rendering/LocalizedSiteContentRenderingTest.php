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

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\HasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case checking if localized tt_content is rendered correctly with different language settings
 * with site configuration.
 *
 * Previously the language was given by TypoScript settings which were overridden via GP parameters for language
 *
 * config.sys_language_uid = [0,1,2,3,4...]
 * config.sys_language_mode = [strict, content_fallback;2,3, ignore, '']
 * config.sys_language_overlay = [0, 1, hideNonTranslated]
 *
 * The previous setting config.sys_language_mode the behaviour of the page translation was referred to, and what
 * should happen if a page translation does not exist.
 *
 * the setting config.sys_language_overlay was responsible if records of a target language should be fetched
 * directly ("free mode" or no-overlays), or if the default language (L=0) should be taken and then overlaid.
 * In addition the "hideNonTranslated" was a special form of overlays: Take the default language, but if the translation
 * does not exist, do not render the default language.
 *
 * This is what changed with Site Handling:
 * - General approach is now defined on a site language, in configuration, not evaluated during runtime
 * - Page fallback concept (also for menu generation) is now valid for page and content.
 * - Various options which only made sense on specific page configurations have been removed for consistency reasons.
 *
 * Pages & Menus:
 * - When a Page Translation needs to be fetched, it is checked if the page translation exists, otherwise the "fallbackChain"
 * jumps in and checks if the other languages are available or aren't available.
 * - If no fallbackChain is given, then the page is not shown / rendered / accessible.
 * - pages.l18n_cfg is now considered properly with multiple fallback languages for menus and page resolving and URL linking.
 *
 * Content Fetching:
 *
 * - A new "free" mode only fetches the records that are set in a specific language.
 *   Due to the concept of the database structure, no fallback logic applies currently when selecting records, however
 *   fallbackChains are still valid for identifying the Page Translation.
 * - The modes "fallback" and "strict" have similarities: They utilize the so-called "overlay" logic: Fetch records in the default
 *   language (= 0) and then overlay with the available language. This ensures that ordering and other connections
 *   are kept the same way as on the default language.
 * - "fallback" shows content in the language of the page that was selected, does the overlays but keeps the default
 *   language records when no translation is available (= "mixed overlays").
 * - "strict" shows only content of the page that was selected via overlays (fetch default language and do overlays)
 *    but does not render the ones that have no translation in the specific language.
 *
 * General notes regarding content fetching:
 * - Records marked as "All Languages" (sys_language_uid = -1) are always fetched (this wasn't always the case before!).
 * - Records without a language parent (l10n_parent) are rendered at any time.
 *
 * Relevant parts for site handling:
 *
 * SiteLanguage
 * -> languageId
 *    the language that is requested, usually determined by the base property. If this setting is "0"
 *    no other options are taken into account.
 * -> fallbackType
 *    - strict:
 *        * for pages: if the page translation does not exist, check fallbackChain
 *        * for record fetching: take Default Language records which have a valid translation for this language + records without default translation
 *    - fallback:
 *        * for pages: if the page translation does not exist, check fallbackChain
 *        * for record fetching: take Default Language records and overlay the language, but keep default language records + records without default translation
 *    - free:
 *        * for pages: if the page translation does not exist, check fallbackChain
 *        * for record fetching: Only fetch records of the current language and "All languages" no overlays are done.
 *
 * LanguageAspect
 * -> doOverlays()
 *    whether the overlay logic should be applied
 * -> getLanguageId()
 *    the language that was originally requested
 * -> getContentId()
 *    if the page translation for e.g. language=5 is not available, but the fallback is "4,3,2", then the content of this language is used instead.
 *    applies to all concepts of fallback types.
 * -> getFallbackChain()
 *    if the page is not available in a specific language, apply other language Ids in the given order until the page translation can be found.
 */
final class LocalizedSiteContentRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Images' => 'fileadmin/user_upload',
    ];

    private const VALUE_PageId = 89;
    private const TABLE_Content = 'tt_content';
    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
        'PL' => ['id' => 3, 'title' => 'Polski', 'locale' => 'pl_PL.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file_storage.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultElements.csv');
        $this->setUpFrontendRootPage(1, [
            'EXT:core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
        ]);
    }

    /**
     * Helper function to ease asserting that rest of the data set is not visible
     */
    private function getNonVisibleHeaders(array $visibleHeaders): array
    {
        $allElements = [
            'Regular Element #1',
            'Regular Element #2',
            'Regular Element #3',
            'Hidden Element #4',
            '[Translate to Dansk:] Regular Element #1',
            '[Translate to Dansk:] Regular Element #3',
            '[DA] Without default language',
            '[DA] UnHidden Element #4',
            '[DE] Without default language',
            '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
            '[Translate to Polski:] Regular Element #1',
            '[PL] Without default language',
            '[PL] Hidden Regular Element #2',
        ];
        return array_diff($allElements, $visibleHeaders);
    }

    /**
     * Helper function to ease asserting that rest of the files are not present
     */
    private function getNonVisibleFileTitles(array $visibleTitles): array
    {
        $allElements = [
            'T3BOARD',
            'Kasper',
            '[Kasper] Image translated to Dansk',
            '[T3BOARD] Image added in Dansk (without parent)',
            '[T3BOARD] Image added to DA element without default language',
            '[T3BOARD] image translated to DE from DA',
            'Kasper2',
        ];
        return array_diff($allElements, $visibleTitles);
    }

    /**
     * For the default language all combination of language settings should give the same result,
     * regardless of Language fallback settings, if the default language is requested then no language settings apply.
     */
    #[Test]
    public function onlyEnglishContentIsRenderedForDefaultLanguage(): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=' . self::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());

        $responseSections = $responseStructure->getSections();
        $visibleHeaders = ['Regular Element #1', 'Regular Element #2', 'Regular Element #3'];
        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$visibleHeaders)
        );
        self::assertThat(
            $responseSections,
            (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        //assert FAL relations
        $visibleFiles = ['T3BOARD'];
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFiles));

        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFiles)));

        $visibleFiles = ['Kasper2'];
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':298')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFiles));

        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':298')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFiles)));

        // Assert language settings and page record title
        self::assertEquals('Default language Page', $responseStructure->getScopePath('page/title'));
        self::assertEquals(0, $responseStructure->getScopePath('languageInfo/id'), 'languageId does not match');
        self::assertEquals(0, $responseStructure->getScopePath('languageInfo/contentId'), 'contentId does not match');
        self::assertEquals('strict', $responseStructure->getScopePath('languageInfo/fallbackType'), 'fallbackType does not match');
        self::assertEquals('pageNotFound', $responseStructure->getScopePath('languageInfo/fallbackChain'), 'fallbackChain does not match');
        self::assertEquals('includeFloating', $responseStructure->getScopePath('languageInfo/overlayType'), 'language overlayType does not match');
    }

    /**
     * Dutch language has page translation record and some content elements are translated
     */
    public static function dutchDataProvider(): array
    {
        return [
            [
                // Only records with language=1 are shown
                'languageConfiguration' => [
                    'fallbackType' => 'free',
                ],
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DA] Without default language',
                        'image' => ['[T3BOARD] Image added to DA element without default language'],
                    ],
                    308 => [
                        'header' => '[DA] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
                'fallbackType' => 'free',
                'fallbackChain' => 'pageNotFound',
                'overlayMode' => 'off',
            ],
            // Expected behaviour:
            // Not translated element #2 is shown because "fallback" is enabled, which defaults to L=0 elements
            [
                'languageConfiguration' => [
                    'fallbackType' => 'fallback',
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'fallbackType' => 'fallback',
                'fallbackChain' => 'pageNotFound',
                'overlayMode' => 'mixed',
            ],
            // Expected behaviour:
            // Non translated default language elements are not shown, but the results include the records without default language as well
            [
                'languageConfiguration' => [
                    'fallbackType' => 'strict',
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    303 => [
                        'header' => '[DA] Without default language',
                        'image' => ['[T3BOARD] Image added to DA element without default language'],
                    ],
                ],
                'fallbackType' => 'strict',
                'fallbackChain' => 'pageNotFound',
                'overlayMode' => 'includeFloating',
            ],
        ];
    }

    /**
     * Page is translated to Dutch, so changing fallbackChain does not matter currently.
     * Page title is always [DA]Page, the content language is always "1"
     */
    #[DataProvider('dutchDataProvider')]
    #[Test]
    public function renderingOfDutchLanguage(array $languageConfiguration, array $visibleRecords, string $fallbackType, string $fallbackChain, string $overlayMode): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/', $languageConfiguration['fallbackChain'] ?? [], $languageConfiguration['fallbackType']),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/da/?id=' . self::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSections();
        $visibleHeaders = array_map(static function ($element) {
            return $element['header'];
        }, $visibleRecords);

        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$visibleHeaders)
        );
        self::assertThat(
            $responseSections,
            (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        foreach ($visibleRecords as $ttContentUid => $properties) {
            $visibleFileTitles = $properties['image'];
            if (!empty($visibleFileTitles)) {
                self::assertThat($responseSections, (new StructureHasRecordConstraint())
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFileTitles));
            }
            self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFileTitles)));
        }

        self::assertEquals('[DA]Page', $responseStructure->getScopePath('page/title'));
        self::assertEquals(1, $responseStructure->getScopePath('languageInfo/id'), 'languageId does not match');
        self::assertEquals(1, $responseStructure->getScopePath('languageInfo/contentId'), 'contentId does not match');
        self::assertEquals($fallbackType, $responseStructure->getScopePath('languageInfo/fallbackType'), 'fallbackType does not match');
        self::assertEquals($fallbackChain, $responseStructure->getScopePath('languageInfo/fallbackChain'), 'fallbackChain does not match');
        self::assertEquals($overlayMode, $responseStructure->getScopePath('languageInfo/overlayType'), 'language overlayType does not match');
    }

    public static function contentOnNonTranslatedPageDataProvider(): array
    {
        //Expected behaviour:
        //the page is NOT translated so setting sys_language_mode to different values changes the results
        //- setting sys_language_mode to empty value makes TYPO3 return default language records
        //- setting it to strict throws 404, independently from other settings
        //Setting config.sys_language_overlay = 0
        return [
            [
                'languageConfiguration' => [
                    'fallbackType' => 'free',
                    'fallbackChain' => ['EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'languageId' => 2,
                'contentLanguageId' => 0,
                'fallbackType' => 'free',
                'fallbackChain' => '0,pageNotFound',
                'overlayMode' => 'off',
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'free',
                    'fallbackChain' => ['EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'languageId' => 2,
                'contentLanguageId' => 0,
                'fallbackType' => 'free',
                'fallbackChain' => '0,pageNotFound',
                'overlayMode' => 'off',
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'free',
                    'fallbackChain' => ['DA', 'EN'],
                ],
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    303 => [
                        'header' => '[DA] Without default language',
                        'image' => ['[T3BOARD] Image added to DA element without default language'],
                    ],
                    308 => [
                        'header' => '[DA] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
                'pageTitle' => '[DA]Page',
                'languageId' => 2,
                'contentLanguageId' => 1,
                'fallbackType' => 'free',
                'fallbackChain' => '1,0,pageNotFound',
                'overlayMode' => 'off',
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'free',
                ],
                'visibleRecords' => [],
                'pageTitle' => '',
                'languageId' => 2,
                'contentLanguageId' => 2,
                'fallbackType' => 'free',
                'fallbackChain' => 'pageNotFound',
                'overlayMode' => 'off',
                'statusCode' => 404,
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'fallback',
                    'fallbackChain' => ['EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'languageId' => 2,
                'contentLanguageId' => 0,
                'fallbackType' => 'fallback',
                'fallbackChain' => '0,pageNotFound',
                'overlayMode' => 'mixed',
            ],
            //falling back to default language
            [
                'languageConfiguration' => [
                    'fallbackType' => 'fallback',
                    'fallbackChain' => ['EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'languageId' => 2,
                'contentLanguageId' => 0,
                'fallbackType' => 'fallback',
                'fallbackChain' => '0,pageNotFound',
                'overlayMode' => 'mixed',
            ],
            //Dutch elements are shown because of the content fallback 1,0 - first Dutch, then default language
            //note that '[DA] Without default language' is NOT shown - due to overlays (fetch default language and overlay it with translations)
            [
                'languageConfiguration' => [
                    'fallbackType' => 'fallback',
                    'fallbackChain' => ['DA', 'EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                ],
                'pageTitle' => '[DA]Page',
                'languageId' => 2,
                'contentLanguageId' => 1,
                'fallbackType' => 'fallback',
                'fallbackChain' => '1,0,pageNotFound',
                'overlayMode' => 'mixed',
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'fallback',
                    'fallbackChain' => [],
                ],
                'visibleRecords' => [],
                'pageTitle' => '',
                'languageId' => 2,
                'contentLanguageId' => 0,
                'fallbackType' => 'fallback',
                'fallbackChain' => 'pageNotFound',
                'overlayMode' => 'mixed',
                'statusCode' => 404,
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'strict',
                    'fallbackChain' => ['EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => 'Regular Element #1',
                        'image' => ['T3BOARD'],
                    ],
                    298 => [
                        'header' => 'Regular Element #2',
                        'image' => ['Kasper2'],
                    ],
                    299 => [
                        'header' => 'Regular Element #3',
                        'image' => ['Kasper'],
                    ],
                ],
                'pageTitle' => 'Default language Page',
                'languageId' => 2,
                'contentLanguageId' => 0,
                'fallbackType' => 'strict',
                'fallbackChain' => '0,pageNotFound',
                'overlayMode' => 'includeFloating',
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'strict',
                    'fallbackChain' => ['DA', 'EN'],
                ],
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    303 => [
                        'header' => '[DA] Without default language',
                        'image' => ['[T3BOARD] Image added to DA element without default language'],
                    ],
                ],
                'pageTitle' => '[DA]Page',
                'languageId' => 2,
                'contentLanguageId' => 1,
                'fallbackType' => 'strict',
                'fallbackChain' => '1,0,pageNotFound',
                'overlayMode' => 'includeFloating',
            ],
            [
                'languageConfiguration' => [
                    'fallbackType' => 'strict',
                    'fallbackChain' => [],
                ],
                'visibleRecords' => [],
                'pageTitle' => '',
                'languageId' => 2,
                'contentLanguageId' => 1,
                'fallbackType' => 'strict',
                'fallbackChain' => 'pageNotFound',
                'overlayMode' => 'includeFloating',
                'statusCode' => 404,
            ],
        ];
    }

    /**
     * Page uid 89 is NOT translated to german
     *
     *
     * @param int $statusCode 200 or 404
     */
    #[DataProvider('contentOnNonTranslatedPageDataProvider')]
    #[Test]
    public function contentOnNonTranslatedPageGerman(array $languageConfiguration, array $visibleRecords, string $pageTitle, int $languageId, int $contentLanguageId, string $fallbackType, string $fallbackChain, string $overlayMode, int $statusCode = 200): void
    {
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/', $languageConfiguration['fallbackChain'] ?? [], $languageConfiguration['fallbackType']),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/de/?id=' . self::VALUE_PageId)
        );

        if ($statusCode === 200) {
            $visibleHeaders = array_column($visibleRecords, 'header');
            $responseStructure = ResponseContent::fromString((string)$response->getBody());
            $responseSections = $responseStructure->getSections();

            self::assertThat(
                $responseSections,
                (new HasRecordConstraint())
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$visibleHeaders)
            );
            self::assertThat(
                $responseSections,
                (new DoesNotHaveRecordConstraint())
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
            );

            foreach ($visibleRecords as $ttContentUid => $properties) {
                $visibleFileTitles = $properties['image'];
                if (!empty($visibleFileTitles)) {
                    self::assertThat($responseSections, (new StructureHasRecordConstraint())
                        ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                        ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFileTitles));
                }
                self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFileTitles)));
            }

            self::assertEquals($pageTitle, $responseStructure->getScopePath('page/title'));
            self::assertEquals($languageId, $responseStructure->getScopePath('languageInfo/id'), 'languageId does not match');
            self::assertEquals($contentLanguageId, $responseStructure->getScopePath('languageInfo/contentId'), 'contentId does not match');
            self::assertEquals($fallbackType, $responseStructure->getScopePath('languageInfo/fallbackType'), 'fallbackType does not match');
            self::assertEquals($fallbackChain, $responseStructure->getScopePath('languageInfo/fallbackChain'), 'fallbackChain does not match');
            self::assertEquals($overlayMode, $responseStructure->getScopePath('languageInfo/overlayType'), 'language overlayType does not match');
        }
        self::assertEquals($statusCode, $response->getStatusCode());
    }

    public static function contentOnPartiallyTranslatedPageDataProvider(): \Generator
    {
        //Expected behaviour:
        //Setting sys_language_mode to different values doesn't influence the result as the requested page is translated to Polish,
        //Page title is always [PL]Page, and both languageId/contentId are always 3
        yield 'free' => [
            'languageConfiguration' => [
                'fallbackType' => 'free',
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            'fallbackType' => 'free',
            'fallbackChain' => 'pageNotFound',
            'overlayMode' => 'off',
        ];
        yield 'free with fallback' => [
            'languageConfiguration' => [
                'fallbackType' => 'free',
                'fallbackChain' => ['EN'],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            'fallbackType' => 'free',
            'fallbackChain' => '0,pageNotFound',
            'overlayMode' => 'off',
        ];
        yield 'free with multiple fallbacks' => [
            'languageConfiguration' => [
                'fallbackType' => 'free',
                'fallbackChain' => ['DA', 'EN'],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            'fallbackType' => 'free',
            'fallbackChain' => '1,0,pageNotFound',
            'overlayMode' => 'off',
        ];
        // Expected behaviour:
        // Not translated element #2 is shown because sys_language_overlay = 1 (with sys_language_overlay = hideNonTranslated, it would be hidden)
        yield 'fallback to EN' => [
            'languageConfiguration' => [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['EN'],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
            'fallbackType' => 'fallback',
            'fallbackChain' => '0,pageNotFound',
            'overlayMode' => 'mixed',
        ];
        // Expected behaviour:
        // Element #3 is not translated in PL, but it is translated in DA. The DA version is shown as it has a fallback chain defined.
        yield 'fallback with multiple languages' => [
            'languageConfiguration' => [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DA', 'EN'],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', '[Translate to Dansk:] Regular Element #3'],
            'fallbackType' => 'fallback',
            'fallbackChain' => '1,0,pageNotFound',
            'overlayMode' => 'mixed',
        ];
        yield 'fallback but without languages' => [
            'languageConfiguration' => [
                'fallbackType' => 'fallback',
                'fallbackChain' => [],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
            'fallbackType' => 'fallback',
            'fallbackChain' => 'pageNotFound',
            'overlayMode' => 'mixed',
        ];
        // Expected behaviour:
        // Non translated default language elements are not shown, because of "hideNonTranslated" a.k.a. "strict"
        yield 'strict with fallback to default' => [
            'languageConfiguration' => [
                'fallbackType' => 'strict',
                'fallbackChain' => ['EN'],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            'fallbackType' => 'strict',
            'fallbackChain' => '0,pageNotFound',
            'overlayMode' => 'includeFloating',
        ];
        yield 'strict with multiple fallbacks' => [
            'languageConfiguration' => [
                'fallbackType' => 'strict',
                'fallbackChain' => ['DA', 'EN'],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[Translate to Dansk:] Regular Element #3', '[PL] Without default language'],
            'fallbackType' => 'strict',
            'fallbackChain' => '1,0,pageNotFound',
            'overlayMode' => 'includeFloating',
        ];
        yield 'strict without fallback' => [
            'languageConfiguration' => [
                'fallbackType' => 'strict',
                'fallbackChain' => [],
            ],
            'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            'fallbackType' => 'strict',
            'fallbackChain' => 'pageNotFound',
            'overlayMode' => 'includeFloating',
        ];
    }

    /**
     * Page uid 89 is translated to Polish, but not all CE are translated
     */
    #[DataProvider('contentOnPartiallyTranslatedPageDataProvider')]
    #[Test]
    public function contentOnPartiallyTranslatedPage(array $languageConfiguration, array $visibleRecordHeaders, string $fallbackType, string $fallbackChain, string $overlayMode): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('PL', '/pl/', $languageConfiguration['fallbackChain'] ?? [], $languageConfiguration['fallbackType']),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/pl/?id=' . self::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSections();

        self::assertEquals(200, $response->getStatusCode());

        self::assertThat(
            $responseSections,
            (new HasRecordConstraint())
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$visibleRecordHeaders)
        );
        self::assertThat(
            $responseSections,
            (new DoesNotHaveRecordConstraint())
            ->setTable(self::TABLE_Content)
            ->setField('header')
            ->setValues(...$this->getNonVisibleHeaders($visibleRecordHeaders))
        );

        self::assertEquals('[PL]Page', $responseStructure->getScopePath('page/title'));
        self::assertEquals(3, $responseStructure->getScopePath('languageInfo/id'), 'languageId does not match');
        self::assertEquals(3, $responseStructure->getScopePath('languageInfo/contentId'), 'contentId does not match');
        self::assertEquals($fallbackType, $responseStructure->getScopePath('languageInfo/fallbackType'), 'fallbackType does not match');
        self::assertEquals($fallbackChain, $responseStructure->getScopePath('languageInfo/fallbackChain'), 'fallbackChain does not match');
        self::assertEquals($overlayMode, $responseStructure->getScopePath('languageInfo/overlayType'), 'language overlayType does not match');
    }
}
