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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkAnalyzerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'info',
        'seo',
        'linkvalidator',
    ];

    /**
     * Set up for set up the backend user, initialize the language object
     * and creating the Export instance
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    public static function findAllBrokenLinksDataProvider(): array
    {
        return [
            'Test with one broken external link (not existing domain)'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_external.csv',
                ],
            'Test with one broken external link in pages:canonical_link'
                => [
                    __DIR__ . '/Fixtures/input_page_with_broken_link_external_in_canonical_link.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_with_broken_link_external_in_canonical_link.csv',
                ],
            'Test with one broken page link (not existing page)'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_page.csv',
                ],
            'Test with one broken file link (not existing file)'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_file.csv',
                ],
            'Test with several broken external, page and file links'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_links_several.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_links_several.csv',
                ],
            'Test with several pages with broken external, page and file links'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_links_several_pages.csv',
                    [1, 2],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_links_several_pages.csv',
                ],
        ];
    }

    #[DataProvider('findAllBrokenLinksDataProvider')]
    #[Test]
    public function getLinkStatisticsFindAllBrokenLinks(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'pages' => ['media', 'url', 'canonical_link'],
                'tt_content' => ['bodytext', 'header_link', 'records'],
            ],
            'linktypes' => 'db,file,external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public static function findFindOnlyFileBrokenLinksDataProvider(): array
    {
        return [
            // Tests with one broken link
            'Test with one broken external link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_none.csv',
                ],
            'Test with one broken page link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_none.csv',
                ],
            'Test with one broken file link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_file.csv',
                ],
        ];
    }

    #[DataProvider('findFindOnlyFileBrokenLinksDataProvider')]
    #[Test]
    public function getLinkStatisticsFindOnlyFileBrokenLinks(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'pages' => ['media', 'url'],
                'tt_content' => ['bodytext', 'header_link', 'records'],
            ],
            'linktypes' => 'file',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public static function findFindOnlyPageBrokenLinksDataProvider(): array
    {
        return [
            // Tests with one broken link
            'Test with one broken external link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_none.csv',
                ],
            'Test with one broken page link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_page.csv',
                ],
            'Test with one broken file link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_none.csv',
                ],
        ];
    }

    #[DataProvider('findFindOnlyPageBrokenLinksDataProvider')]
    #[Test]
    public function getLinkStatisticsFindOnlyPageBrokenLinks(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'pages' => ['media', 'url'],
                'tt_content' => ['bodytext', 'header_link', 'records'],
            ],
            'linktypes' => 'db',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public static function findFindOnlyExternalBrokenLinksDataProvider(): array
    {
        return [
            // Tests with one broken link
            'Test with one broken external link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_external.csv',
                ],
            'Test with one broken page link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_none.csv',
                ],
            'Test with one broken file link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.csv',
                    [1],
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_none.csv',
                ],
        ];
    }

    #[DataProvider('findFindOnlyExternalBrokenLinksDataProvider')]
    #[Test]
    public function getLinkStatisticsFindOnlyExternalBrokenLinksInBodytext(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'tt_content' => ['bodytext'],
            ],
            'linktypes' => 'external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public static function getLinkStatisticsFindOnlyExternalBrokenLinksInBodytextWithHugeListOfPageIdsDataProvider(): array
    {
        $lagePageUidList = range(1, 200000, 1);
        return [
            // Tests with one broken link
            'Test with one broken external link'
                => [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.csv',
                    $lagePageUidList,
                    __DIR__ . '/Fixtures/expected_output_content_with_broken_link_external.csv',
                ],
        ];
    }

    #[DataProvider('getLinkStatisticsFindOnlyExternalBrokenLinksInBodytextWithHugeListOfPageIdsDataProvider')]
    #[Test]
    public function getLinkStatisticsFindOnlyExternalBrokenLinksInBodytextWithHugeListOfPageIds(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'tt_content' => ['bodytext'],
            ],
            'linktypes' => 'external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public static function analyzeRecordReturnsCorrectCountDataProvider(): ?\Generator
    {
        // Regression test for https://forge.typo3.org/issues/95878
        yield 'Check that link parsing only returns 1 result for links with URL as anchor text' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">http://localhost/iAmInvalid</a>',
            ],
            'typolink_tag,email[subst],url',
            'expectedCount' => [
                'external' => 1,
            ],
        ];

        yield 'Parse external link' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">links</a>',
            ],
            'typolink_tag,email[subst],url',
            'expectedCount' => [
                'external' => 1,
            ],
        ];

        yield 'Parse external links' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">links</a><a href="http://localhost/iAmInvalid?abc=d">second link</a>',
            ],
            'typolink_tag,email[subst],url',
            'expectedCount' => [
                'external' => 2,
            ],
        ];

        yield 'Parse external link and page link' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">links</a><a href="t3://page?uid=1">second link</a>',
            ],
            'typolink_tag,email[subst],url',
            'expectedCount' => [
                'external' => 1,
                'db' => 1,
            ],
        ];

        // Regression test: a link using its URL as anchor text must still be de-duplicated
        // ("typolink_tag" and "url" both match it) while a *different*, unrelated URL in
        // plain text elsewhere in the same field must still be found by the "url" parser.
        yield 'Deduplicate link with URL as anchor text but still find an unrelated plain URL' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">http://localhost/iAmInvalid</a> and also http://localhost/anotherOne',
            ],
            'typolink_tag,email[subst],url',
            'expectedCount' => [
                'external' => 2,
            ],
        ];
    }

    /**
     * Check the analyzeRecord returns correct number of links
     */
    #[DataProvider('analyzeRecordReturnsCorrectCountDataProvider')]
    #[Test]
    public function analyzeRecordReturnsCorrectCount(
        string $table,
        string $field,
        array $record,
        string $softrefString,
        array $expectedCount
    ): void {
        $tsConfig = [
            'searchFields' => [
                'tt_content' => ['bodytext'],
            ],
            'linktypes' => 'external',
            'checkhidden' => '0',
        ];
        $searchFields = $tsConfig['searchFields'];
        $pidList = [1];

        // intialize TCA
        $GLOBALS['TCA'][$table]['columns'][$field]['config'] = [
            'softref' => $softrefString,
        ];

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $results = [];
        $linkAnalyzer->analyzeRecord($results, 'tt_content', ['bodytext'], $record);
        $count = [];
        foreach ($results as $type => $links) {
            $count[$type] = 0;
            foreach ($links as $link) {
                $count[$type]++;
            }
        }
        self::assertEquals($expectedCount, $count, 'analyzeRecord should return 1 result');
    }

    public static function analyzeRecordReturnsCorrectResultDataProvider(): ?\Generator
    {
        // Regression test for https://forge.typo3.org/issues/95878
        yield 'Check link parsing with external link with URL as anchor text' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">http://localhost/iAmInvalid</a>',
            ],
            'typolink_tag,email[subst],url',
            [
                [
                    'linkType' => 'external',
                    'linkTarget' => 'http://localhost/iAmInvalid',
                    'linkText' => 'http://localhost/iAmInvalid',
                ],
            ],
        ];

        yield 'Check link parsing with external link' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">link title</a>',
            ],
            'typolink_tag,email[subst],url',
            [
                [
                    'linkType' => 'external',
                    'linkTarget' => 'http://localhost/iAmInvalid',
                    'linkText' => 'link title',
                ],
            ],
        ];

        yield 'Check link parsing with page link' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="t3://page?uid=123">page link</a>',
            ],
            'typolink_tag,email[subst],url',
            [
                [
                    'linkType' => 'db',
                    'linkTarget' => '123',
                    'linkText' => 'page link',
                ],
            ],
        ];

        yield 'Check link parsing with external and page link' => [
            'tt_content',
            'bodytext',
            [
                'uid' => 1,
                'bodytext' => '<a href="http://localhost/iAmInvalid">link title</a><a href="t3://page?uid=123">page link</a>',
            ],
            'typolink_tag,email[subst],url',
            [
                [
                    'linkType' => 'external',
                    'linkTarget' => 'http://localhost/iAmInvalid',
                    'linkText' => 'link title',
                ],
                [
                    'linkType' => 'db',
                    'linkTarget' => '123',
                    'linkText' => 'page link',
                ],
            ],
        ];
    }

    /**
     * Regression test for https://forge.typo3.org/issues/95878
     * Check that link parsing only returns 1 result for
     * links with URL as anchor text, e.g.
     * <a href="http://localhost/iAmInvalid">http://localhost/iAmInvalid</a>
     */
    #[DataProvider('analyzeRecordReturnsCorrectResultDataProvider')]
    #[Test]
    public function analyzeRecordReturnsCorrectResult(
        string $table,
        string $field,
        array $record,
        string $softrefString,
        array $expectedResults
    ): void {
        $tsConfig = [
            'searchFields' => [
                'tt_content' => ['bodytext'],
            ],
            'linktypes' => 'external',
            'checkhidden' => '0',
        ];
        $searchFields = $tsConfig['searchFields'];
        $pidList = [1];

        // intialize TCA
        $GLOBALS['TCA'][$table]['columns'][$field]['config'] = [
            'softref' => $softrefString,
        ];

        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $results = [];
        $linkAnalyzer->analyzeRecord($results, 'tt_content', ['bodytext'], $record);
        $results = reset($results);
        $count = 0;
        foreach ($results as $result) {
            self::assertEquals(
                $result['substr']['type'],
                $expectedResults[$count]['linkType'],
                'analyzeRecord should return correct link type'
            );
            self::assertEquals(
                $result['substr']['tokenValue'],
                $expectedResults[$count]['linkTarget'],
                'analyzeRecord should return correct tokenValue'
            );
            self::assertEquals(
                $result['link_title'],
                $expectedResults[$count]['linkText'],
                'analyzeRecord should return correct link_title'
            );
            $count++;
        }
    }

}
