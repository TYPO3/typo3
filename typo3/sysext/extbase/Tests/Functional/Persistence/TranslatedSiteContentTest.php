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

use ExtbaseTeam\BlogExample\Domain\Repository\TtContentRepository;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\ResponseContent;

/**
 * Test case documenting an Extbase translation handling of tt_content consistent with Site Handling.
 *
 * This test has the same scenarios as in the TypoScript version:
 * @see \TYPO3\CMS\Frontend\Tests\Functional\Rendering\LocalizedSiteContentRenderingTest
 */
class TranslatedSiteContentTest extends AbstractDataHandlerActionTestCase
{
    use SiteBasedTestTrait;

    protected const VALUE_PageId = 89;
    protected const TABLE_Content = 'tt_content';
    protected const TABLE_Pages = 'pages';

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/frontend/Tests/Functional/Rendering/DataSet/';

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * @var TtContentRepository
     */
    protected $contentRepository;

    /**
     * If this value is NULL, log entries are not considered.
     * If it's an integer value, the number of log entries is asserted.
     *
     * @var int|null
     */
    protected $expectedErrorLogEntries;

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Images' => 'fileadmin/user_upload'
    ];

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DK' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'dk_DA.UTF8'],
        'DE' => ['id' => 2, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
        'PL' => ['id' => 3, 'title' => 'Polski', 'locale' => 'pl_PL.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->contentRepository = $this->objectManager->get(TtContentRepository::class);
        $this->setUpFrontendRootPage(1, [
            'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example/Configuration/TypoScript/setup.typoscript',
            'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/Frontend/ContentJsonRenderer.typoscript'
        ]);
    }

    protected function tearDown(): void
    {
        unset($this->objectManager, $this->contentRepository);
        parent::tearDown();
    }

    /**
     * For the default language all combination of language settings should give the same result,
     * regardless of TypoScript settings, if the requested language is "0" then no TypoScript settings apply.
     *
     * @test
     */
    public function onlyEnglishContentIsRenderedForDefaultLanguage(): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/')
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest('https://website.local/en/?id=' . static::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSection('Extbase:list()');
        $visibleHeaders = ['Regular Element #1', 'Regular Element #2', 'Regular Element #3'];
        self::assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$visibleHeaders)
        );
        self::assertThat(
            $responseSections,
            $this->getRequestSectionDoesNotHaveRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        // assert FAL relations
        $visibleFiles = ['T3BOARD'];
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFiles));

        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFiles)));

        $visibleFiles = ['Kasper2'];
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':298')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFiles));

        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':298')->setRecordField('image')
            ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFiles)));

        // assert Categories
        $visibleCategories = ['Category 1', 'Category 3 - not translated'];
        self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('categories')
            ->setTable('sys_category')->setField('title')->setValues(...$visibleCategories));

        self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
            ->setRecordIdentifier(self::TABLE_Content . ':297')->setRecordField('categories')
            ->setTable('sys_category')->setField('title')->setValues(...$this->getNonVisibleCategoryTitles($visibleCategories)));
    }

    /**
     * Dutch language has pages record and some content elements are translated
     *
     * @return array
     */
    public function dutchDataProvider(): array
    {
        // Expected behaviour:
        // Page is translated to Dutch, so changing sys_language_mode does NOT change the results
        // Page title is always [DK]Page, and both sys_language_content and sys_language_uid are always 1
        return [
            [
                'fallbackType' => 'free',
                'visibleRecords' => [
                    300 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
                    ],
                    301 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                        'categories' => ['[Translate to Dansk:] Category 1', 'Category 4'],
                    ],
                    303 => [
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language']
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
                        'image' => []
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
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dutchDataProvider
     *
     * @param string $fallbackType
     * @param array $visibleRecords
     */
    public function renderingOfDutchLanguage(string $fallbackType, array $visibleRecords): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DK', '/dk/', [], $fallbackType)
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest('https://website.local/dk/?id=' . static::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSection('Extbase:list()');
        $visibleHeaders = array_map(function ($element) {
            return $element['header'];
        }, $visibleRecords);

        self::assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$visibleHeaders)
        );
        self::assertThat(
            $responseSections,
            $this->getRequestSectionDoesNotHaveRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );

        foreach ($visibleRecords as $ttContentUid => $properties) {
            $visibleFileTitles = $properties['image'];
            if (!empty($visibleFileTitles)) {
                self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFileTitles));
            }
            self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFileTitles)));

            $visibleCategoryTitles = $properties['categories'] ?? [];
            if (!empty($visibleCategoryTitles)) {
                self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('categories')
                    ->setTable('sys_category')->setField('title')->setValues(...$visibleCategoryTitles));
            }
            self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('categories')
                ->setTable('sys_category')->setField('title')->setValues(...$this->getNonVisibleCategoryTitles($visibleCategoryTitles)));
        }
    }

    public function contentOnNonTranslatedPageDataProvider(): array
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
                'fallbackChain' => ['DK', 'EN'],
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
                        'header' => '[DK] Without default language',
                        'image' => ['[T3BOARD] Image added to DK element without default language'],
                    ],
                    308 => [
                        'header' => '[DK] UnHidden Element #4',
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
            // Dutch elements are shown because of the fallback chain 1,0 - first Dutch, then default language
            // note that '[DK] Without default language' is NOT shown - due to overlays (fetch default language and overlay it with translations)
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DK', 'EN'],
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
                'statusCode' => 404
            ],
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DK', 'EN'],
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
                'fallbackChain' => ['DK', 'EN'],
                'visibleRecords' => [
                    297 => [
                        'header' => '[Translate to Dansk:] Regular Element #1',
                        'image' => [],
                    ],
                    299 => [
                        'header' => '[Translate to Dansk:] Regular Element #3',
                        'image' => ['[Kasper] Image translated to Dansk', '[T3BOARD] Image added in Dansk (without parent)'],
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
     * @test
     * @dataProvider contentOnNonTranslatedPageDataProvider
     *
     * @param string $fallbackType
     * @param array $fallbackChain
     * @param array $visibleRecords
     * @param int $statusCode '200' or '404'
     */
    public function contentOnNonTranslatedPageGerman(string $fallbackType, array $fallbackChain, array $visibleRecords, int $statusCode = 200): void
    {
        $this->writeSiteConfiguration(
            'main',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DK', '/dk/'),
                $this->buildLanguageConfiguration('DE', '/de/', $fallbackChain, $fallbackType)
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest('https://website.local/de/?id=' . static::VALUE_PageId)
        );

        if ($statusCode === 200) {
            $visibleHeaders = array_column($visibleRecords, 'header');
            $responseStructure = ResponseContent::fromString((string)$response->getBody());
            $responseSections = $responseStructure->getSection('Extbase:list()');
            self::assertThat(
                $responseSections,
                $this->getRequestSectionHasRecordConstraint()
                    ->setTable(self::TABLE_Content)
                    ->setField('header')
                    ->setValues(...$visibleHeaders)
            );
            self::assertThat(
                $responseSections,
                $this->getRequestSectionDoesNotHaveRecordConstraint()
                    ->setTable(self::TABLE_Content)
                    ->setField('header')
                    ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
            );

            foreach ($visibleRecords as $ttContentUid => $properties) {
                $visibleFileTitles = $properties['image'];
                if (!empty($visibleFileTitles)) {
                    self::assertThat($responseSections, $this->getRequestSectionStructureHasRecordConstraint()
                        ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                        ->setTable('sys_file_reference')->setField('title')->setValues(...$visibleFileTitles));
                }
                self::assertThat($responseSections, $this->getRequestSectionStructureDoesNotHaveRecordConstraint()
                    ->setRecordIdentifier(self::TABLE_Content . ':' . $ttContentUid)->setRecordField('image')
                    ->setTable('sys_file_reference')->setField('title')->setValues(...$this->getNonVisibleFileTitles($visibleFileTitles)));
            }
        }
        self::assertEquals($statusCode, $response->getStatusCode());
    }

    public function contentOnPartiallyTranslatedPageDataProvider(): array
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
                'fallbackChain' => ['DK', 'EN'],
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
            // Element #3 is not translated in PL and it is translated in DK. It's not shown as fallback chain is not related to single CE level
            // but on page level - and this page is translated to Polish, so no fallback is happening
            [
                'fallbackType' => 'fallback',
                'fallbackChain' => ['DK', 'EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1', 'Regular Element #2', 'Regular Element #3'],
            ],
            // Expected behaviour:
            // Non translated default language elements are not shown, because of strict mode
            [
                'fallbackType' => 'strict',
                'fallbackChain' => [],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => ['EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
            ],
            [
                'fallbackType' => 'strict',
                'fallbackChain' => ['DK', 'EN'],
                'visibleRecordHeaders' => ['[Translate to Polski:] Regular Element #1'],
            ],
        ];
    }

    /**
     * Page uid 89 is translated to to Polish, but not all CE are translated
     *
     * @test
     * @dataProvider contentOnPartiallyTranslatedPageDataProvider
     *
     * @param string $fallbackType
     * @param array $fallbackChain
     * @param array $visibleHeaders
     */
    public function contentOnPartiallyTranslatedPage(string $fallbackType, array $fallbackChain, array $visibleHeaders): void
    {
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DK', '/dk/'),
                $this->buildLanguageConfiguration('PL', '/pl/', $fallbackChain, $fallbackType)
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );
        $response = $this->executeFrontendRequest(
            new InternalRequest('https://website.local/pl/?id=' . static::VALUE_PageId)
        );
        $responseStructure = ResponseContent::fromString((string)$response->getBody());
        $responseSections = $responseStructure->getSections('Extbase:list()');

        self::assertEquals(200, $response->getStatusCode());

        self::assertThat(
            $responseSections,
            $this->getRequestSectionHasRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$visibleHeaders)
        );
        self::assertThat(
            $responseSections,
            $this->getRequestSectionDoesNotHaveRecordConstraint()
                ->setTable(self::TABLE_Content)
                ->setField('header')
                ->setValues(...$this->getNonVisibleHeaders($visibleHeaders))
        );
    }

    /**
     * Helper function to ease asserting that rest of the data set is not visible
     *
     * @param array $visibleHeaders
     * @return array
     */
    protected function getNonVisibleHeaders(array $visibleHeaders): array
    {
        $allElements = [
            'Regular Element #1',
            'Regular Element #2',
            'Regular Element #3',
            'Hidden Element #4',
            '[Translate to Dansk:] Regular Element #1',
            '[Translate to Dansk:] Regular Element #3',
            '[DK] Without default language',
            '[DK] UnHidden Element #4',
            '[DE] Without default language',
            '[Translate to Deutsch:] [Translate to Dansk:] Regular Element #1',
            '[Translate to Polski:] Regular Element #1',
            '[PL] Without default language',
            '[PL] Hidden Regular Element #2'
        ];
        return array_diff($allElements, $visibleHeaders);
    }

    /**
     * Helper function to ease asserting that rest of the data set is not visible
     *
     * @param array $visibleTitles
     * @return array
     */
    protected function getNonVisibleFileTitles(array $visibleTitles): array
    {
        $allElements = [
            'T3BOARD',
            'Kasper',
            '[Kasper] Image translated to Dansk',
            '[T3BOARD] Image added in Dansk (without parent)',
            '[T3BOARD] Image added to DK element without default language',
            '[T3BOARD] image translated to DE from DK',
            'Kasper2'
        ];
        return array_diff($allElements, $visibleTitles);
    }

    /**
     * Helper function to ease asserting that rest of the data set is not visible
     *
     * @param array $visibleTitles
     * @return array
     */
    protected function getNonVisibleCategoryTitles(array $visibleTitles): array
    {
        $allElements = [
            'Category 1',
            '[Translate to Dansk:] Category 1',
            'Category 3 - not translated',
            'Category 4',
        ];
        return array_diff($allElements, $visibleTitles);
    }
}
