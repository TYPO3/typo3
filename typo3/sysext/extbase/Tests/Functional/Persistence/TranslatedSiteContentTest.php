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

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

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
 * Test case documenting an Extbase translation handling of tt_content consistent with Site Handling.
 *
 * This test has the same scenarios as in the TypoScript version:
 * @see \TYPO3\CMS\Frontend\Tests\Functional\Rendering\LocalizedSiteContentRenderingTest
 */
final class TranslatedSiteContentTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
    ];
    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Images' => 'fileadmin/user_upload',
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/LiveDefaultPages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/LiveDefaultElements.csv');
        $this->setUpFrontendRootPage(1, [
            'EXT:extbase/Tests/Functional/Fixtures/Extensions/blog_example/Configuration/TypoScript/setup.typoscript',
            'EXT:extbase/Tests/Functional/Persistence/Fixtures/Frontend/ContentJsonRenderer.typoscript',
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
     * Helper function to ease asserting that rest of the data set is not visible
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
     * Helper function to ease asserting that rest of the data set is not visible
     */
    private function getNonVisibleCategoryTitles(array $visibleTitles): array
    {
        $allElements = [
            'Category 1',
            '[Translate to Dansk:] Category 1',
            'Category 3 - not translated',
            'Category 4',
        ];
        return array_diff($allElements, $visibleTitles);
    }

    /**
     * For the default language all combination of language settings should give the same result,
     * regardless of TypoScript settings, if the requested language is "0" then no TypoScript settings apply.
     */
    #[Test]
    public function onlyEnglishContentIsRenderedForDefaultLanguage(): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/en/?id=' . static::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSection('Extbase:list()');
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

        // assert FAL relations
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

        // assert Categories
        $visibleCategories = ['Category 1', 'Category 3 - not translated'];
        self::assertThat($responseSections, (new StructureHasRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('categories')
            ->setTable('sys_category')->setField('title')->setValues(...$visibleCategories));

        self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('categories')
            ->setTable('sys_category')->setField('title')->setValues(...$this->getNonVisibleCategoryTitles($visibleCategories)));
    }

    /**
     * Danish language has pages record and some content elements are translated
     */
    public static function danishDataProvider(): array
    {
        // Expected behaviour:
        // Page is translated to Danish, so changing sys_language_mode does NOT change the results
        // Page title is always [DA]Page, and both sys_language_content and sys_language_uid are always 1
        return [
            [
                'fallbackType' => 'free',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                        'categories' => ['[Translate to Dansk:] Category 1', 'Category 4'],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
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
            ],
            [
                'fallbackType' => 'fallback',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                        'categories' => ['[Translate to Dansk:] Category 1', 'Category 4'],
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
            ],
            [
                'fallbackType' => 'strict',
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                        'categories' => ['[Translate to Dansk:] Category 1', 'Category 4'],
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
            ],
        ];
    }

    #[DataProvider('danishDataProvider')]
    #[Test]
    public function renderingOfDanishLanguage(string $fallbackType, array $visibleRecords): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/', [], $fallbackType),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/da/?id=' . static::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSection('Extbase:list()');
        $visibleHeaders = array_map(static fn(array $element): string => $element['header'], $visibleRecords);

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

            $visibleCategoryTitles = $properties['categories'] ?? [];
            if (!empty($visibleCategoryTitles)) {
                self::assertThat($responseSections, (new StructureHasRecordConstraint())
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('categories')
                    ->setTable('sys_category')->setField('title')->setValues(...$visibleCategoryTitles));
            }
            self::assertThat($responseSections, (new StructureDoesNotHaveRecordConstraint())
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('categories')
                ->setTable('sys_category')->setField('title')->setValues(...$this->getNonVisibleCategoryTitles($visibleCategoryTitles)));
        }
    }

    public static function contentOnNonTranslatedPageDataProvider(): array
    {
        return [
            [
                'fallbackType' => 'free',
                'fallbackChain' => ['EN'],
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
            ],
            [
                'fallbackType' => 'free',
                'fallbackChain' => ['DA', 'EN'],
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
                    308 => [
                        'header' => '[DA] UnHidden Element #4',
                        'image' => [],
                    ],
                ],
            ],
            [
                'fallbackType' => 'free',
                'fallbackChain' => [],
                'visibleRecords' => [],
                'statusCode' => 404,
            ],
            // falling back to default language
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['EN'],
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
            ],
            // danish elements are shown because of the fallback chain 1,0 - first danish, then default language
            // note that '[DA] Without default language' is NOT shown - due to overlays (fetch default language and overlay it with translations)
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DA', 'EN'],
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
            ],
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => [],
                'visibleRecords' => [],
                'statusCode' => 404,
            ],
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DA', 'EN'],
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
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => ['EN'],
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
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => ['DA', 'EN'],
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    // Fallback chain allows Danish records, also without default languages
                    303 => [
                        'header' => '[DA] Without default language',
                        'image' => ['[T3BOARD] Image added to DA element without default language'],
                    ],
                ],
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => [],
                'visibleRecords' => [],
                'statusCode' => 404,
            ],
        ];
    }

    /**
     * Page uid 89 is NOT translated to german
     *
     *
     * @param int $statusCode '200' or '404'
     */
    #[DataProvider('contentOnNonTranslatedPageDataProvider')]
    #[Test]
    public function contentOnNonTranslatedPageGerman(string $fallbackType, array $fallbackChain, array $visibleRecords, int $statusCode = 200): void
    {
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('DE', '/de/', $fallbackChain, $fallbackType),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/de/?id=' . static::VALUE_PageId)
        );

        if ($statusCode === 200) {
            $visibleHeaders = array_column($visibleRecords, 'header');
            $responseStructure = ResponseContent::fromString((string)$response->getBody());
            $responseSections = $responseStructure->getSection('Extbase:list()');
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
        }
        self::assertEquals($statusCode, $response->getStatusCode());
    }

    public static function contentOnPartiallyTranslatedPageDataProvider(): array
    {
        // Expected behaviour:
        // Setting sys_language_mode to different values doesn't influence the result as the requested page is translated to Polish,
        // Page title is always [PL]Page, and both sys_language_content and sys_language_uid are always 3
        return [
            [
                'fallbackType' => 'free',
                'fallbackChain' => [],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'fallbackType' => 'free',
                'fallbackChain' => ['EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'fallbackType' => 'free',
                'fallbackChain' => ['DA', 'EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
            ],
            // Expected behaviour:
            // Not translated element #2 is shown
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
            ],
            // Expected behaviour:
            // Element #3 is shown from DA. Element #3 from PL is not translated, but a fallback to DA is defined
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DA', 'EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', '[Translate to Dansk:] Regular Element #3'],
            ],
            // Expected behaviour:
            // Non translated default language elements are not shown, because of strict mode
            // Also, records in the PL without a default language (l10n_parent=0) are also shown
            [
                'fallbackType' => 'strict',
                'fallbackChain' => [],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => ['EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => ['DA', 'EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', '[PL] Without default language'],
            ],
        ];
    }

    /**
     * Page uid 89 is translated to to Polish, but not all CE are translated
     */
    #[DataProvider('contentOnPartiallyTranslatedPageDataProvider')]
    #[Test]
    public function contentOnPartiallyTranslatedPage(string $fallbackType, array $fallbackChain, array $visibleRecordHeaders): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
                $this->buildLanguageConfiguration('PL', '/pl/', $fallbackChain, $fallbackType),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://website.local/pl/?id=' . static::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSections('Extbase:list()');

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
    }
}
