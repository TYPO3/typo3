<?php
namespace TYPO3\CMS\Core\Tests\Unit\Html;

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

use TYPO3\CMS\Core\Html\HtmlParser;

/**
 * Testcase for \TYPO3\CMS\Core\Html\HtmlParser
 */
class HtmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Html\HtmlParser
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = new HtmlParser();
	}

	/**
	 * Data provider for getSubpart
	 *
	 * @return array
	 */
	public function getSubpartDataProvider() {
		return array(
			'No start marker' => array(
				'<body>text</body>',
				'###SUBPART###',
				''
			),
			'No stop marker' => array(
				'<body>
<!-- ###SUBPART### Start -->
text
</body>',
				'###SUBPART###',
				''
			),
			'Start and stop marker in HTML comment' => array(
				'<body>
<!-- ###SUBPART### Start -->
text
<!-- ###SUBPART### End -->
</body>',
				'###SUBPART###',
				'
text
'
			),
			'Stop marker in HTML comment' => array(
				'<body>
###SUBPART###
text
<!-- ###SUBPART### End -->
</body>',
				'###SUBPART###',
				'
text
'
			),
			'Start marker in HTML comment' => array(
				'<body>
<!-- ###SUBPART### Start -->
text
###SUBPART###
</body>',
				'###SUBPART###',
				'
text
'
			),
			'Start and stop marker direct' => array(
				'<body>
###SUBPART###
text
###SUBPART###
</body>',
				'###SUBPART###',
				'
text
'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getSubpartDataProvider
	 */
	public function getSubpart($content, $marker, $expected) {
		$this->assertSame($expected, HtmlParser::getSubpart($content, $marker));
	}

	/**
	 * Data provider for substituteSubpart
	 *
	 * @return array
	 */
	public function substituteSubpartDataProvider() {
		return array(
			'No start marker' => array(
				'<body>text</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				FALSE,
				'<body>text</body>'
			),
			'No stop marker' => array(
				'<body>
<!-- ###SUBPART### Start -->
text
</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				FALSE,
				'<body>
<!-- ###SUBPART### Start -->
text
</body>',
			),
			'Start and stop marker in HTML comment' => array(
				'<body>
<!-- ###SUBPART### Start -->
text
<!-- ###SUBPART### End -->
</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				FALSE,
				'<body>
hello
</body>'
			),
			'Recursive subpart' => array(
				'<body>
<!-- ###SUBPART### Start -->text1<!-- ###SUBPART### End -->
<!-- ###SUBPART### Start -->text2<!-- ###SUBPART### End -->
</body>',
				'###SUBPART###',
				'hello',
				TRUE,
				FALSE,
				'<body>
hello
hello
</body>'
			),
			'Keep HTML marker' => array(
				'<body>
<!-- ###SUBPART### Start -->text<!-- ###SUBPART### End -->
</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				TRUE,
				'<body>
<!-- ###SUBPART### Start -->hello<!-- ###SUBPART### End -->
</body>'
			),
			'Keep HTML begin marker' => array(
				'<body>
<!-- ###SUBPART### Start -->text###SUBPART###
</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				TRUE,
				'<body>
<!-- ###SUBPART### Start -->hello###SUBPART###
</body>'
			),
			'Keep HTML end marker' => array(
				'<body>
###SUBPART###text<!-- ###SUBPART### End -->
</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				TRUE,
				'<body>
###SUBPART###hello<!-- ###SUBPART### End -->
</body>'
			),
			'Keep plain marker' => array(
				'<body>
###SUBPART###text###SUBPART###
</body>',
				'###SUBPART###',
				'hello',
				FALSE,
				TRUE,
				'<body>
###SUBPART###hello###SUBPART###
</body>'
			),
			'Wrap around' => array(
				'<body>
###SUBPART###text###SUBPART###
</body>',
				'###SUBPART###',
				array('before-', '-after'),
				FALSE,
				TRUE,
				'<body>
###SUBPART###before-text-after###SUBPART###
</body>'
			),
		);
	}

	/**
	 * @test
	 * @dataProvider substituteSubpartDataProvider
	 */
	public function substituteSubpart($content, $marker, $subpartContent, $recursive, $keepMarker, $expected) {
		$this->assertSame($expected, HtmlParser::substituteSubpart($content, $marker, $subpartContent, $recursive, $keepMarker));
	}

	/**
	 * Data provider for substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArray
	 */
	public function substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArrayDataProvider() {
		$template = '###SINGLEMARKER1###
<!-- ###FOO### begin -->
<!-- ###BAR### begin -->
###SINGLEMARKER2###
<!-- ###BAR### end -->
<!-- ###FOOTER### begin -->
###SINGLEMARKER3###
<!-- ###FOOTER### end -->
<!-- ###FOO### end -->';

		$expected ='Value 1


Value 2.1

Value 2.2


Value 3.1

Value 3.2

';

		return array(
			'Single marker' => array(
				'###SINGLEMARKER###',
				array(
					'###SINGLEMARKER###' => 'Value 1'
				),
				'',
				FALSE,
				FALSE,
				'Value 1'
			),
			'Subpart marker' => array(
				$template,
				array(
					'###SINGLEMARKER1###' => 'Value 1',
					'###FOO###' => array(
						array(
							'###BAR###' => array(
								array(
									'###SINGLEMARKER2###' => 'Value 2.1'
								),
								array(
									'###SINGLEMARKER2###' => 'Value 2.2'
								)
							),
							'###FOOTER###' => array(
								array(
									'###SINGLEMARKER3###' => 'Value 3.1'
								),
								array(
									'###SINGLEMARKER3###' => 'Value 3.2'
								)
							)
						)
					)
				),
				'',
				FALSE,
				FALSE,
				$expected
			),
			'Subpart marker with wrap' => array(
				$template,
				array(
					'SINGLEMARKER1' => 'Value 1',
					'FOO' => array(
						array(
							'BAR' => array(
								array(
									'SINGLEMARKER2' => 'Value 2.1'
								),
								array(
									'SINGLEMARKER2' => 'Value 2.2'
								)
							),
							'FOOTER' => array(
								array(
									'SINGLEMARKER3' => 'Value 3.1'
								),
								array(
									'SINGLEMARKER3' => 'Value 3.2'
								)
							)
						)
					)
				),
				'###|###',
				FALSE,
				FALSE,
				$expected
			),
			'Subpart marker with lower marker array keys' => array(
				$template,
				array(
					'###singlemarker1###' => 'Value 1',
					'###foo###' => array(
						array(
							'###bar###' => array(
								array(
									'###singlemarker2###' => 'Value 2.1'
								),
								array(
									'###singlemarker2###' => 'Value 2.2'
								)
							),
							'###footer###' => array(
								array(
									'###singlemarker3###' => 'Value 3.1'
								),
								array(
									'###singlemarker3###' => 'Value 3.2'
								)
							)
						)
					)
				),
				'',
				TRUE,
				FALSE,
				$expected
			),
			'Subpart marker with unused markers' => array(
				$template,
				array(
					'###FOO###' => array(
						array(
							'###BAR###' => array(
								array(
									'###SINGLEMARKER2###' => 'Value 2.1'
								)
							),
							'###FOOTER###' => array(
								array(
									'###SINGLEMARKER3###' => 'Value 3.1'
								)
							)
						)
					)
				),
				'',
				FALSE,
				TRUE,
				'


Value 2.1


Value 3.1

'
			),
			'Subpart marker with empty subpart' => array(
				$template,
				array(
					'###SINGLEMARKER1###' => 'Value 1',
					'###FOO###' => array(
						array(
							'###BAR###' => array(
								array(
									'###SINGLEMARKER2###' => 'Value 2.1'
								),
								array(
									'###SINGLEMARKER2###' => 'Value 2.2'
								)
							),
							'###FOOTER###' => array()
						)
					)
				),
				'',
				FALSE,
				FALSE,
				'Value 1


Value 2.1

Value 2.2


'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArrayDataProvider
	 */
	public function substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArray($template, $markersAndSubparts, $wrap, $uppercase, $deleteUnused, $expected) {
		$this->assertSame($expected, HtmlParser::substituteMarkerAndSubpartArrayRecursive($template, $markersAndSubparts, $wrap, $uppercase, $deleteUnused));
	}

	/**
	 * @return array
	 */
	public function cDataWillRemainUnmodifiedDataProvider() {
		return array(
			'single-line CDATA' => array(
				'/*<![CDATA[*/ <hello world> /*]]>*/',
				'/*<![CDATA[*/ <hello world> /*]]>*/',
			),
			'multi-line CDATA #1' => array(
				'/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
				'/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
			),
			'multi-line CDATA #2' => array(
				'/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
				'/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
			),
			'multi-line CDATA #3' => array(
				'/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
				'/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
			),
		);
	}

	/**
	 * @test
	 * @param string $source
	 * @param string $expected
	 * @dataProvider cDataWillRemainUnmodifiedDataProvider
	 */
	public function xHtmlCleaningDoesNotModifyCDATA($source, $expected) {
		$result = $this->subject->XHTML_clean($source);
		$this->assertSame($expected, $result);
	}

	/**
	 * Data provider for spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured
	 */
	public static function spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider() {
		return array(
			'Span tag with no attrib' => array(
				'<span>text</span>',
				'text'
			),
			'Span tag with allowed id attrib' => array(
				'<span id="id">text</span>',
				'<span id="id">text</span>'
			),
			'Span tag with disallowed style attrib' => array(
				'<span style="line-height: 12px;">text</span>',
				'text'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider
	 */
	public function tagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured($content, $expectedResult) {
		$tsConfig = array(
			'allowTags' => 'span',
			'tags.' => array(
				'span.' => array(
					'allowedAttribs' => 'id',
					'rmTagIfNoAttrib' => 1
				)
			)
		);
		$this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
	}

	/**
	 * @test
	 */
	public function rmTagIfNoAttribIsConfiguredDoesNotChangeNestingType() {
		$tsConfig = array(
			'allowTags' => 'div,span',
			'rmTagIfNoAttrib' => 'span',
			'globalNesting' => 'div,span'
		);
		$content = '<span></span><span id="test"><div></span></div>';
		$expectedResult = '<span id="test"></span>';
		$this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
	}

	/**
	 * Data provider for localNestingCorrectlyRemovesInvalidTags
	 */
	public static function localNestingCorrectlyRemovesInvalidTagsDataProvider() {
		return array(
			'Valid nesting is untouched' => array(
				'<B><I></B></I>',
				'<B><I></B></I>'
			),
			'Valid nesting with content is untouched' => array(
				'testa<B>test1<I>test2</B>test3</I>testb',
				'testa<B>test1<I>test2</B>test3</I>testb'
			),
			'Superflous tags are removed' => array(
				'</B><B><I></B></I></B>',
				'<B><I></B></I>'
			),
			'Superflous tags with content are removed' => array(
				'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
				'test1test2<B>test3<I>test4</B>test5</I>test6test7'
			),
			'Another valid nesting test' => array(
				'<span><div></span></div>',
				'<span><div></span></div>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider localNestingCorrectlyRemovesInvalidTagsDataProvider
	 * @param string $content
	 * @param string $expectedResult
	 */
	public function localNestingCorrectlyRemovesInvalidTags($content, $expectedResult) {
		$tsConfig = array(
			'allowTags' => 'div,span,b,i',
			'localNesting' => 'div,span,b,i',
		);
		$this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
	}

	/**
	 * Data provider for globalNestingCorrectlyRemovesInvalidTags
	 */
	public static function globalNestingCorrectlyRemovesInvalidTagsDataProvider() {
		return array(
			'Valid nesting is untouched' => array(
				'<B><I></I></B>',
				'<B><I></I></B>'
			),
			'Valid nesting with content is untouched' => array(
				'testa<B>test1<I>test2</I>test3</B>testb',
				'testa<B>test1<I>test2</I>test3</B>testb'
			),
			'Invalid nesting is cleaned' => array(
				'</B><B><I></B></I></B>',
				'<B></B>'
			),
			'Invalid nesting with content is cleaned' => array(
				'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
				'test1test2<B>test3test4</B>test5test6test7'
			),
			'Another invalid nesting test' => array(
				'<span><div></span></div>',
				'<span></span>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider globalNestingCorrectlyRemovesInvalidTagsDataProvider
	 * @param string $content
	 * @param string $expectedResult
	 */
	public function globalNestingCorrectlyRemovesInvalidTags($content, $expectedResult) {
		$tsConfig = array(
			'allowTags' => 'span,div,b,i',
			'globalNesting' => 'span,div,b,i',
		);
		$this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
	}

	/**
	 * @return array
	 */
	public function emptyTagsDataProvider() {
		return array(
			array(0 , NULL, FALSE, '<h1></h1>', '<h1></h1>'),
			array(1 , NULL, FALSE, '<h1></h1>', ''),
			array(1 , NULL, FALSE, '<h1>hallo</h1>', '<h1>hallo</h1>'),
			array(1 , NULL, FALSE, '<h1 class="something"></h1>', ''),
			array(1 , NULL, FALSE, '<h1 class="something"></h1><h2></h2>', ''),
			array(1 , 'h2', FALSE, '<h1 class="something"></h1><h2></h2>', '<h1 class="something"></h1>'),
			array(1 , 'h2, h1', FALSE, '<h1 class="something"></h1><h2></h2>', ''),
			array(1 , NULL, FALSE, '<div><p></p></div>', ''),
			array(1 , NULL, FALSE, '<div><p>&nbsp;</p></div>', '<div><p>&nbsp;</p></div>'),
			array(1 , NULL, TRUE, '<div><p>&nbsp;&nbsp;</p></div>', ''),
			array(1 , NULL, TRUE, '<div>&nbsp;&nbsp;<p></p></div>', ''),
			array(1 , NULL, FALSE, '<div>Some content<p></p></div>', '<div>Some content</div>'),
			array(1 , NULL, TRUE, '<div>Some content<p></p></div>', '<div>Some content</div>'),
			array(1 , NULL, FALSE, '<div>Some content</div>', '<div>Some content</div>'),
			array(1 , NULL, TRUE, '<div>Some content</div>', '<div>Some content</div>'),
			array(1 , NULL, FALSE, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'),
			array(1 , NULL, TRUE, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'),
		);
	}

	/**
	 * @test
	 * @dataProvider emptyTagsDataProvider
	 * @param bool $stripOn TRUE if stripping should be activated.
	 * @param string $tagList Comma seperated list of tags that should be stripped.
	 * @param bool $treatNonBreakingSpaceAsEmpty If TRUE &nbsp; will be considered empty.
	 * @param string $content The HTML code that should be modified.
	 * @param string $expectedResult The expected HTML code result.
	 */
	public function stripEmptyTags($stripOn, $tagList, $treatNonBreakingSpaceAsEmpty, $content, $expectedResult) {
		$tsConfig = array(
			'keepNonMatchedTags' => 1,
			'stripEmptyTags' => $stripOn,
			'stripEmptyTags.' => array(
				'tags' => $tagList,
				'treatNonBreakingSpaceAsEmpty' => $treatNonBreakingSpaceAsEmpty
			),
		);

		$result = $this->parseConfigAndCleanHtml($tsConfig, $content);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * Calls HTMLparserConfig() and passes the generated config to the HTMLcleaner() method on the current subject.
	 *
	 * @param array $tsConfig The TypoScript that should be used to generate the HTML parser config.
	 * @param string $content The content that should be parsed by the HTMLcleaner.
	 * @return string The parsed content.
	 */
	protected function parseConfigAndCleanHtml(array $tsConfig, $content) {
		$config = $this->subject->HTMLparserConfig($tsConfig);
		return $this->subject->HTMLcleaner($content, $config[0], $config[1], $config[2], $config[3]);
	}
}
