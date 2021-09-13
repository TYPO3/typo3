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

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class LinkAnalyzerTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = [
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
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
    }

    public function findAllBrokenLinksDataProvider(): array
    {
        return [
            'Test with one broken external link (not existing domain)' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_external.csv'
                ],
            'Test with one broken external link in pages:canonical_link' =>
                [
                    __DIR__ . '/Fixtures/input_page_with_broken_link_external_in_canonical_link.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_with_broken_link_external_in_canonical_link.csv'
                ],
            'Test with one broken page link (not existing page)' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_page.csv'
                ],
            'Test with one broken file link (not existing file)' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_file.csv'
                ],
            'Test with several broken external, page and file links' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_links_several.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_links_several.csv'
                ],
            'Test with several pages with broken external, page and file links' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_links_several_pages.xml',
                    [1, 2],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_links_several_pages.csv'
                ],
        ];
    }

    /**
     * @test
     * @dataProvider findAllBrokenLinksDataProvider
     */
    public function getLinkStatisticsFindAllBrokenLinks(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'pages' => ['media', 'url', 'canonical_link'],
                'tt_content' => ['bodytext', 'header_link', 'records']
            ],
            'linktypes' => 'db,file,external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public function findFindOnlyFileBrokenLinksDataProvider(): array
    {
        return [
            // Tests with one broken link
            'Test with one broken external link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_none.csv'
                ],
            'Test with one broken page link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_none.csv'
                ],
            'Test with one broken file link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_file.csv'
                ],
        ];
    }

    /**
     * @test
     * @dataProvider findFindOnlyFileBrokenLinksDataProvider
     */
    public function getLinkStatisticsFindOnlyFileBrokenLinks(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'pages' => ['media', 'url'],
                'tt_content' => ['bodytext', 'header_link', 'records']
            ],
            'linktypes' => 'file',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public function findFindOnlyPageBrokenLinksDataProvider(): array
    {
        return [
            // Tests with one broken link
            'Test with one broken external link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_none.csv'
                ],
            'Test with one broken page link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_page.csv'
                ],
            'Test with one broken file link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_none.csv'
                ],
        ];
    }

    /**
     * @test
     * @dataProvider findFindOnlyPageBrokenLinksDataProvider
     */
    public function getLinkStatisticsFindOnlyPageBrokenLinks(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'pages' => ['media', 'url'],
                'tt_content' => ['bodytext', 'header_link', 'records']
            ],
            'linktypes' => 'db',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }

    public function findFindOnlyExternalBrokenLinksDataProvider(): array
    {
        return [
            // Tests with one broken link
            'Test with one broken external link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_external.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_external.csv'
                ],
            'Test with one broken page link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_page.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_none.csv'
                ],
            'Test with one broken file link' =>
                [
                    __DIR__ . '/Fixtures/input_content_with_broken_link_file.xml',
                    [1],
                    'EXT:linkvalidator/Tests/Functional/Fixtures/expected_output_content_with_broken_link_none.csv'
                ],
        ];
    }

    /**
     * @test
     * @dataProvider findFindOnlyExternalBrokenLinksDataProvider
     */
    public function getLinkStatisticsFindOnlyExternalBrokenLinksInBodytext(string $inputFile, array $pidList, string $expectedOutputFile): void
    {
        $tsConfig = [
            'searchFields' => [
                'tt_content' => ['bodytext']
            ],
            'linktypes' => 'external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields'];

        $this->importDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $this->assertCSVDataSet($expectedOutputFile);
    }
}
