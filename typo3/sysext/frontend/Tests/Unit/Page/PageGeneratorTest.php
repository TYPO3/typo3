<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

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

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Tests\Unit\Page\Fixtures\PageGeneratorFixture;

/**
 * Test case
 */
class PageGeneratorTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var PageGeneratorFixture
     */
    protected $pageGeneratorFixture;

    /**
     * @var ContentObjectRenderer
     */
    protected $contentObjectRenderer;

    /**
     * Set up the helper objects
     */
    protected function setUp()
    {
        $this->pageGeneratorFixture = new PageGeneratorFixture();
        $this->contentObjectRenderer = new ContentObjectRenderer();
    }

    /**
     * @return array
     */
    public function generateMetaTagHtmlGeneratesCorrectTagsDataProvider()
    {
        return [
            'simple meta' => [
                [
                    'author' => 'Markus Klein',
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="author" content="Markus Klein">',
                ]
            ],
            'simple meta xhtml' => [
                [
                    'author' => 'Markus Klein',
                ],
                true,
                [
                    '<meta name="generator" content="TYPO3 CMS" />',
                    '<meta name="author" content="Markus Klein" />',
                ]
            ],
            'meta with nested stdWrap' => [
                [
                    'author' => 'Markus ',
                    'author.' => ['stdWrap.' => ['wrap' => '|Klein']]
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="author" content="Markus Klein">',
                ]
            ],
            'httpEquivalent meta' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['httpEquivalent' => 1]
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'
                ]
            ],
            'httpEquivalent meta xhtml' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['httpEquivalent' => 1]
                ],
                true,
                [
                    '<meta name="generator" content="TYPO3 CMS" />',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />'
                ]
            ],
            'httpEquivalent meta xhtml new notation' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['attribute' => 'http-equiv']
                ],
                true,
                [
                    '<meta name="generator" content="TYPO3 CMS" />',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />'
                ]
            ],
            'refresh meta' => [
                [
                    'refresh' => '10',
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv="refresh" content="10">',
                ]
            ],
            'refresh meta new notation' => [
                [
                    'refresh' => '10',
                    'refresh.' => ['attribute' => 'http-equiv']
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv="refresh" content="10">',
                ]
            ],
            'refresh meta new notation wins form old' => [
                [
                    'refresh' => '10',
                    'refresh.' => ['attribute' => 'http-equiv-new']
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv-new="refresh" content="10">',
                ]
            ],
            'meta with dot' => [
                [
                    'DC.author' => 'Markus Klein',
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="DC.author" content="Markus Klein">',
                ]
            ],
            'meta with colon' => [
                [
                    'OG:title' => 'Magic Tests',
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="OG:title" content="Magic Tests">',
                ]
            ],
            'different attribute name' => [
                [
                    'og:site_title' => 'My TYPO3 site',
                    'og:site_title.' => ['attribute' => 'property'],
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta property="og:site_title" content="My TYPO3 site">',
                ]
            ],
            'multi value attribute name' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => 'de_DE',
                        ]
                    ],
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta property="og:locale:alternate" content="nl_NL">',
                    '<meta property="og:locale:alternate" content="de_DE">',
                ]
            ],
            'multi value attribute name (empty values are skipped)' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => '',
                            30 => 'de_DE',
                        ]
                    ],
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta property="og:locale:alternate" content="nl_NL">',
                    '<meta property="og:locale:alternate" content="de_DE">',
                ]
            ],
            'meta with empty string value' => [
                [
                    'custom:key' => '',
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                ]
            ],
            'meta with 0 value' => [
                [
                    'custom:key' => '0',
                ],
                false,
                [
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="custom:key" content="0">',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider generateMetaTagHtmlGeneratesCorrectTagsDataProvider
     *
     * @param array $typoScript
     * @param bool $xhtml
     * @param array $expectedTags
     */
    public function generateMetaTagHtmlGeneratesCorrectTags(array $typoScript, $xhtml, array $expectedTags)
    {
        $result = $this->pageGeneratorFixture->callGenerateMetaTagHtml($typoScript, $xhtml, $this->contentObjectRenderer);
        $this->assertSame($expectedTags, $result);
    }
}
