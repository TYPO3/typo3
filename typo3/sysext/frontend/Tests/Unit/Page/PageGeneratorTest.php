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
class PageGeneratorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
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
        return array(
            'simple meta' => array(
                array(
                    'author' => 'Markus Klein',
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="author" content="Markus Klein">',
                )
            ),
            'simple meta xhtml' => array(
                array(
                    'author' => 'Markus Klein',
                ),
                true,
                array(
                    '<meta name="generator" content="TYPO3 CMS" />',
                    '<meta name="author" content="Markus Klein" />',
                )
            ),
            'meta with nested stdWrap' => array(
                array(
                    'author' => 'Markus ',
                    'author.' => array('stdWrap.' => array('wrap' => '|Klein'))
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="author" content="Markus Klein">',
                )
            ),
            'httpEquivalent meta' => array(
                array(
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => array('httpEquivalent' => 1)
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'
                )
            ),
            'httpEquivalent meta xhtml' => array(
                array(
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => array('httpEquivalent' => 1)
                ),
                true,
                array(
                    '<meta name="generator" content="TYPO3 CMS" />',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />'
                )
            ),
            'httpEquivalent meta xhtml new notation' => array(
                array(
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => array('attribute' => 'http-equiv')
                ),
                true,
                array(
                    '<meta name="generator" content="TYPO3 CMS" />',
                    '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />'
                )
            ),
            'refresh meta' => array(
                array(
                    'refresh' => '10',
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv="refresh" content="10">',
                )
            ),
            'refresh meta new notation' => array(
                array(
                    'refresh' => '10',
                    'refresh.' => array('attribute' => 'http-equiv')
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv="refresh" content="10">',
                )
            ),
            'refresh meta new notation wins form old' => array(
                array(
                    'refresh' => '10',
                    'refresh.' => array('attribute' => 'http-equiv-new')
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta http-equiv-new="refresh" content="10">',
                )
            ),
            'meta with dot' => array(
                array(
                    'DC.author' => 'Markus Klein',
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="DC.author" content="Markus Klein">',
                )
            ),
            'meta with colon' => array(
                array(
                    'OG:title' => 'Magic Tests',
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="OG:title" content="Magic Tests">',
                )
            ),
            'different attribute name' => array(
                array(
                    'og:site_title' => 'My TYPO3 site',
                    'og:site_title.' => array('attribute' => 'property'),
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta property="og:site_title" content="My TYPO3 site">',
                )
            ),
            'multi value attribute name' => array(
                array(
                    'og:locale:alternate.' => array(
                        'attribute' => 'property',
                        'value' => array(
                            10 => 'nl_NL',
                            20 => 'de_DE',
                        )
                    ),
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta property="og:locale:alternate" content="nl_NL">',
                    '<meta property="og:locale:alternate" content="de_DE">',
                )
            ),
            'multi value attribute name (empty values are skipped)' => array(
                array(
                    'og:locale:alternate.' => array(
                        'attribute' => 'property',
                        'value' => array(
                            10 => 'nl_NL',
                            20 => '',
                            30 => 'de_DE',
                        )
                    ),
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta property="og:locale:alternate" content="nl_NL">',
                    '<meta property="og:locale:alternate" content="de_DE">',
                )
            ),
            'meta with empty string value' => array(
                array(
                    'custom:key' => '',
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                )
            ),
            'meta with 0 value' => array(
                array(
                    'custom:key' => '0',
                ),
                false,
                array(
                    '<meta name="generator" content="TYPO3 CMS">',
                    '<meta name="custom:key" content="0">',
                )
            ),
        );
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

    /**
     * @return array
     */
    public function initializeSearchWordDataInTsfeBuildsCorrectRegexDataProvider()
    {
        return array(
            'one simple search word' => array(
                array('test'),
                false,
                'test',
            ),
            'one simple search word with standalone words' => array(
                array('test'),
                true,
                '[[:space:]]test[[:space:]]',
            ),
            'two simple search words' => array(
                array('test', 'test2'),
                false,
                'test|test2',
            ),
            'two simple search words with standalone words' => array(
                array('test', 'test2'),
                true,
                '[[:space:]]test[[:space:]]|[[:space:]]test2[[:space:]]',
            ),
            'word with regex chars' => array(
                array('A \\ word with / a bunch of [] regex () chars .*'),
                false,
                'A \\\\ word with \\/ a bunch of \\[\\] regex \\(\\) chars \\.\\*',
            ),
        );
    }

    /**
     * @test
     * @dataProvider initializeSearchWordDataInTsfeBuildsCorrectRegexDataProvider
     *
     * @param array $searchWordGetParameters The values that should be loaded in the sword_list GET parameter.
     * @param bool $enableStandaloneSearchWords If TRUE the sword_standAlone option will be enabled.
     * @param string $expectedRegex The expected regex after processing the search words.
     */
    public function initializeSearchWordDataInTsfeBuildsCorrectRegex(array $searchWordGetParameters, $enableStandaloneSearchWords, $expectedRegex)
    {
        $_GET['sword_list'] = $searchWordGetParameters;

        $GLOBALS['TSFE'] = new \stdClass();
        if ($enableStandaloneSearchWords) {
            $GLOBALS['TSFE']->config = array('config' => array('sword_standAlone' => 1));
        }

        $this->pageGeneratorFixture->callInitializeSearchWordDataInTsfe();
        $this->assertEquals($GLOBALS['TSFE']->sWordRegEx, $expectedRegex);
    }
}
