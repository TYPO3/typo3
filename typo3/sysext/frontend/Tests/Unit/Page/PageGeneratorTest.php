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
 *
 * @author Markus Klein <klein.t3@mfc-linz.at>
 */
class PageGeneratorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
	protected function setUp() {
		$this->pageGeneratorFixture = new PageGeneratorFixture();
		$this->contentObjectRenderer = new ContentObjectRenderer();
	}

	/**
	 * @return array
	 */
	public function generateMetaTagHtmlGeneratesCorrectTagsDataProvider() {
		return array(
			'simple meta' => array(
				array(
					'author' => 'Markus Klein',
				),
				FALSE,
				array(
					'<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS">',
					'<meta name="author" content="Markus Klein">',
				)
			),
			'simple meta xhtml' => array(
				array(
					'author' => 'Markus Klein',
				),
				TRUE,
				array(
					'<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS" />',
					'<meta name="author" content="Markus Klein" />',
				)
			),
			'meta with nested stdWrap' => array(
				array(
					'author' => 'Markus ',
				    'author.' => array('stdWrap.' => array('wrap' => '|Klein'))
				),
				FALSE,
				array(
					'<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS">',
					'<meta name="author" content="Markus Klein">',
				)
			),
			'httpEquivalent meta' => array(
				array(
					'X-UA-Compatible' => 'IE=edge,chrome=1',
				    'X-UA-Compatible.' => array('httpEquivalent' => 1)
				),
				FALSE,
			    array(
				    '<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS">',
					'<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">'
			    )
			),
		    'httpEquivalent meta xhtml' => array(
				array(
					'X-UA-Compatible' => 'IE=edge,chrome=1',
					'X-UA-Compatible.' => array('httpEquivalent' => 1)
				),
				TRUE,
				array(
					'<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS" />',
					'<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />'
				)
		    ),
		    'refresh meta' => array(
				array(
					'refresh' => '10',
				),
				FALSE,
				array(
					'<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS">',
					'<meta http-equiv="refresh" content="10">',
				)
			),
		    'meta with dot' => array(
			    array(
				    'DC.author' => 'Markus Klein',
			    ),
			    FALSE,
			    array(
				    '<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS">',
				    '<meta name="DC.author" content="Markus Klein">',
			    )
		    ),
		    'meta with colon' => array(
			    array(
				    'OG:title' => 'Magic Tests',
			    ),
			    FALSE,
			    array(
				    '<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS">',
				    '<meta name="OG:title" content="Magic Tests">',
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
	public function generateMetaTagHtmlGeneratesCorrectTags(array $typoScript, $xhtml, array $expectedTags) {
		$result = $this->pageGeneratorFixture->callGenerateMetaTagHtml($typoScript, $xhtml, $this->contentObjectRenderer);
		$this->assertSame($expectedTags, $result);
	}
}
