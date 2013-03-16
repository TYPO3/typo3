<?php
namespace TYPO3\CMS\Core\Tests\Unit\Html;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Stanislas Rolland <typo3@sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for \TYPO3\CMS\Core\Html\RteHtmlParser
 *
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
class RteHtmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Html\RteHtmlParser
	 */
	private $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Html\RteHtmlParser();
		$this->fixture->procOptions = array(
			'dontConvBRtoParagraph' => '1',
			'preserveDIVSections' => '1',
			'allowTagsOutside' => 'hr, address',
			'disableUnifyLineBreaks' => '0',
			'overruleMode' => 'ts_css'
		);
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Data provider for hrTagCorrectlyTransformedOnWayToDataBase
	 */
	public static function hrTagCorrectlyTransformedOnWayToDataBaseDataProvider() {
		return array(
			'Single hr' => array(
				'<hr />',
				'<hr />',
			),
			'Non-xhtml single hr' => array(
				'<hr/>',
				'<hr />',
			),
			'Double hr' => array(
				'<hr /><hr />',
				'<hr />' . CRLF . '<hr />',
			),
			'Linebreak followed by hr' => array(
				CRLF . '<hr />',
				'<hr />',
			),
			'White space followed by hr' => array(
				' <hr />',
				' ' . CRLF . '<hr />',
			),
			'White space followed linebreak and hr' => array(
				' ' . CRLF . '<hr />',
				' ' . CRLF . '<hr />',
			),
			'br followed by hr' => array(
				'<br /><hr />',
				'<br />' . CRLF . '<hr />',
			),
			'br followed by linebreak and hr' => array(
				'<br />' . CRLF . '<hr />',
				'<br />' . CRLF . '<hr />',
			),
			'Preserved div followed by hr' => array(
				'<div>Some text</div><hr />',
				'<div>Some text</div>' . CRLF . '<hr />',
			),
			'Preserved div followed by linebreak and hr' => array(
				'<div>Some text</div>' . CRLF . '<hr />',
				'<div>Some text</div>' . CRLF . '<hr />',
			),
			'h1 followed by linebreak and hr' => array(
				'<h1>Some text</h1>' . CRLF . '<hr />',
				'<h1>Some text</h1>' . CRLF . '<hr />',
			),
			'Paragraph followed by linebreak and hr' => array(
				'<p>Some text</p>' . CRLF . '<hr />',
				'Some text' . CRLF . '<hr />',
			),
			'Some text followed by hr' => array(
				'Some text<hr />',
				'Some text' . CRLF . '<hr />',
			),
			'Some text followed by linebreak and hr' => array(
				'Some text' . CRLF . '<hr />',
				'Some text' . CRLF . '<hr />',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider hrTagCorrectlyTransformedOnWayToDataBaseDataProvider
	 */
	public function hrTagCorrectlyTransformedOnWayToDataBase($content, $expectedResult) {
		$thisConfig = array('proc.' => $this->fixture->procOptions);
		$this->assertEquals($expectedResult, $this->fixture->RTE_transform($content, array(), 'db', $thisConfig));
	}

	/**
	 * Data provider for hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
	 */
	public static function hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider() {
		return array(
			'Single hr' => array(
				'<hr />',
				'<hr />',
			),
			'Non-xhtml single hr' => array(
				'<hr/>',
				'<hr />',
			),
			'Double hr' => array(
				'<hr /><hr />',
				'<hr />' . CRLF . '<hr />',
			),
			'Linebreak followed by hr' => array(
				CRLF . '<hr />',
				'<hr />',
			),
			'White space followed by hr' => array(
				' <hr />',
				'<p>&nbsp;</p>' . CRLF . '<hr />',
			),
			'White space followed by linebreak and hr' => array(
				' ' . CRLF . '<hr />',
				'<p>&nbsp;</p>' . CRLF . '<hr />',
			),
			'br followed by hr' => array(
				'<br /><hr />',
				'<p><br /></p>' . CRLF . '<hr />',
			),
			'br followed by linebreak and hr' => array(
				'<br />' . CRLF . '<hr />',
				'<p><br /></p>' . CRLF . '<hr />',
			),
			'Preserved div followed by hr' => array(
				'<div>Some text</div>' . '<hr />',
				'<div><p>Some text</p></div>' . CRLF . '<hr />',
			),
			'Preserved div followed by linebreak and hr' => array(
				'<div>Some text</div>' . CRLF . '<hr />',
				'<div><p>Some text</p></div>' . CRLF . '<hr />',
			),
			'h1 followed by linebreak and hr' => array(
				'<h1>Some text</h1>' . CRLF . '<hr />',
				'<h1>Some text</h1>' . CRLF . '<hr />',
			),
			'Paragraph followed by linebreak and hr' => array(
				'<p>Some text</p>' . CRLF . '<hr />',
				'<p>Some text</p>' . CRLF . '<hr />',
			),
			'Some text followed by hr' => array(
				'Some text<hr />',
				'<p>Some text</p>' . CRLF . '<hr />',
			),
			'Some text followed by linebreak and hr' => array(
				'Some text' . CRLF . '<hr />',
				'<p>Some text</p>' . CRLF . '<hr />',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
	 */
	public function hrTagCorrectlyTransformedOnWayToDatabaseAndBackToRte($content, $expectedResult) {
		$thisConfig = array('proc.' => $this->fixture->procOptions);
		$this->assertEquals($expectedResult, $this->fixture->RTE_transform($this->fixture->RTE_transform($content, array(), 'db', $thisConfig), array(), 'rte', $thisConfig));
	}

	/**
	 * Data provider for linkWithAtSignCorrectlyTransformedOnWayToRTE
	 */
	public static function linkWithAtSignCorrectlyTransformedOnWayToRTEProvider() {
		return array(
			'external url with @ sign' => array(
				'<link http://www.example.org/at@sign>link text</link>',
				'<p><a href="http://www.example.org/at@sign" data-htmlarea-external="1">link text</a></p>'
			),
			'email address with @ sign' => array(
				'<link name@example.org - mail "Opens window for sending email">link text</link>',
				'<p><a href="mailto:name@example.org" class="mail" title="Opens window for sending email">link text</a></p>'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider linkWithAtSignCorrectlyTransformedOnWayToRTEProvider
	 */
	public function linkWithAtSignCorrectlyTransformedOnWayToRTE($content, $expectedResult) {
		$thisConfig = array('proc.' => $this->fixture->procOptions);
		$this->assertEquals($expectedResult, $this->fixture->RTE_transform($content, array(), 'rte', $thisConfig));
	}

	/**
	 * Data provider for paragraphCorrectlyTransformedOnWayToDatabase
	 */
	public static function paragraphCorrectlyTransformedOnWayToDatabaseProvider() {
		return array(
			'Empty string' => array(
				'',
				'',
			),
			'Linebreak' => array(
				CRLF,
				'',
			),
			'Double linebreak' => array(
				CRLF . CRLF,
				'',
			),
			'Empty paragraph' => array(
				'<p></p>',
				CRLF,
			),
			'Double empty paragraph' => array(
				'<p></p><p></p>',
				CRLF . CRLF,
			),
			'Spacing paragraph' => array(
				'<p>&nbsp;</p>',
				CRLF,
			),
			'Double spacing paragraph' => array(
				'<p>&nbsp;</p>' . '<p>&nbsp;</p>',
				CRLF . CRLF,
			),
			'Plain text' => array(
				'plain text',
				'plain text',
			),
			'Plain text followed by linebreak' => array(
				'plain text' . CRLF,
				'plain text ',
			),
			'Paragraph' => array(
				'<p>paragraph</p>',
				'paragraph',
			),
			'Paragraph followed by paragraph' => array(
				'<p>paragraph1</p>' . '<p>paragraph2</p>',
				'paragraph1' . CRLF . 'paragraph2',
			),
			'Paragraph followed by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
				'paragraph1' . CRLF . 'paragraph2',
			),
			'Double spacing paragraph' => array(
				'<p>&nbsp;</p><p>&nbsp;</p><p>paragraph1</p>',
				CRLF . CRLF . paragraph1,
			),
			'Paragraph followed by linebreak' => array(
				'<p>paragraph</p>' . CRLF,
				'paragraph',
			),
			'Paragraph followed by spacing paragraph' => array(
				'<p>paragraph</p>' . '<p>&nbsp;</p>',
				'paragraph' . CRLF . CRLF,
			),
			'Paragraph followed by spacing paragraph, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
				'paragraph' . CRLF . CRLF,
			),
			'Paragraph followed by double spacing paragraph' => array(
				'<p>paragraph</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>',
				'paragraph' . CRLF . CRLF . CRLF,
			),
			'Paragraph followed by double spacing paragraph, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
				'paragraph' . CRLF . CRLF . CRLF,
			),
			'Paragraph followed by paragraph' => array(
				'<p>paragraph1</p>' . '<p>paragraph2</p>',
				'paragraph1' . CRLF . 'paragraph2',
			),
			'Paragraph followed by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
				'paragraph1' . CRLF . 'paragraph2',
			),
			'Paragraph followed by spacing paragraph and by paragraph' => array(
				'<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
				'paragraph1' . CRLF . CRLF . 'paragraph2',
			),
			'Paragraph followed by spacing paragraph and by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
				'paragraph1' . CRLF . CRLF . 'paragraph2',
			),
			'Paragraph followed by double spacing paragraph and by paragraph' => array(
				'<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
				'paragraph1' . CRLF . CRLF . CRLF . 'paragraph2',
			),
			'Paragraph followed by double spacing paragraph and by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
				'paragraph1' . CRLF . CRLF . CRLF . 'paragraph2',
			),
			'Paragraph followed by block' => array(
				'<p>paragraph</p>' . '<h1>block</h1>',
				'paragraph' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by block, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<h1>block</h1>',
				'paragraph' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by spacing paragraph and block' => array(
				'<p>paragraph</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
				'paragraph' . CRLF . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by spacing paragraph and block, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
				'paragraph' . CRLF . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by double spacing paragraph and block' => array(
				'<p>paragraph</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
				'paragraph' . CRLF . CRLF . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by double spacing paragraph and block, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
				'paragraph' . CRLF . CRLF . CRLF . '<h1>block</h1>',
			),
			'Block followed by block' => array(
				'<h1>block1</h1>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by empty paragraph and block' => array(
				'<h1>block1</h1>' . '<p></p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by empty paragraph aand block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p></p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by spacing paragraph' => array(
				'<h1>block1</h1>' . '<p>&nbsp;</p>',
				'<h1>block1</h1>' . CRLF . CRLF,
			),
			'Block followed by spacing paragraph, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>',
				'<h1>block1</h1>' . CRLF . CRLF,
			),
			'Block followed by spacing paragraph and block' => array(
				'<h1>block1</h1>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by spacing paragraph and block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by double spacing paragraph and by block' => array(
				'<h1>block1</h1>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by double spacing paragraph and by block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by paragraph and block' => array(
				'<h1>block1</h1>' . '<p>paragraph</p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by paragraph and block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by paragraph, spacing paragraph and block' => array(
				'<h1>block1</h1>' . '<p>paragraph</p>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . CRLF . '<h1>block2</h1>',
			),
			'Block followed by paragraph, spacing paragraph and block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . CRLF . '<h1>block2</h1>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider paragraphCorrectlyTransformedOnWayToDatabaseProvider
	 */
	public function paragraphCorrectlyTransformedOnWayToDatabase($content, $expectedResult) {
		$thisConfig = array('proc.' => $this->fixture->procOptions);
		$this->assertEquals($expectedResult, $this->fixture->RTE_transform($content, array(), 'db', $thisConfig));
	}

	/**
	 * Data provider for lineBreakCorrectlyTransformedOnWayToRte
	 */
	public static function lineBreakCorrectlyTransformedOnWayToRteProvider() {
		return array(
			'Empty string' => array(
				'',
				'',
			),
			'Single linebreak' => array(
				CRLF,
				'<p>&nbsp;</p>',
			),
			'Double linebreak' => array(
				CRLF . CRLF,
				'<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Triple linebreak' => array(
				CRLF . CRLF . CRLF,
				'<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Paragraph' => array(
				'paragraph',
				'<p>paragraph</p>',
			),
			'Paragraph followed by single linebreak' => array(
				'paragraph' . CRLF,
				'<p>paragraph</p>',
			),
			'Paragraph followed by double linebreak' => array(
				'paragraph' . CRLF . CRLF,
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Paragraph followed by triple linebreak' => array(
				'paragraph' . CRLF . CRLF . CRLF,
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Paragraph followed by paragraph' => array(
				'paragraph1' . CRLF . 'paragraph2',
				'<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by double linebreak and paragraph' => array(
				'paragraph1' . CRLF . CRLF . 'paragraph2',
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by triple linebreak and paragraph' => array(
				'paragraph1' . CRLF . CRLF . CRLF . 'paragraph2',
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by block' => array(
				'paragraph' . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by linebreak and block' => array(
				'paragraph' . CRLF . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by double linebreak and block' => array(
				'paragraph' . CRLF . CRLF . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by triple linebreak and block' => array(
				'paragraph' . CRLF . CRLF . CRLF . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
			),
			'Block followed by block' => array(
				'<h1>block1</h1>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by single linebreak and block' => array(
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by double linebreak and block' => array(
				'<h1>block1</h1>' . CRLF . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by triple linebreak and block' => array(
				'<h1>block1</h1>' . CRLF . CRLF . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by paragraph and block' => array(
				'<h1>block1</h1>' . CRLF . 'paragraph' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>paragraph</p>' . CRLF . '<h1>block2</h1>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider lineBreakCorrectlyTransformedOnWayToRTEProvider
	 */
	public function lineBreakCorrectlyTransformedOnWayToRTE($content, $expectedResult) {
		$thisConfig = array('proc.' => $this->fixture->procOptions);
		$this->assertEquals($expectedResult, $this->fixture->RTE_transform($content, array(), 'rte', $thisConfig));
	}

	/**
	 * Data provider for paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRte
	 */
	public static function paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider() {
		return array(
			'Empty string' => array(
				'',
				'',
			),
			'Empty paragraph' => array(
				'<p></p>',
				'<p>&nbsp;</p>',
			),
			'Double empty paragraph' => array(
				'<p></p><p></p>',
				'<p>&nbsp;</p>'. CRLF . '<p>&nbsp;</p>',
			),
			'Triple empty paragraph' => array(
				'<p></p><p></p><p></p>',
				'<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Plain text' => array(
				'plain text',
				'<p>plain text</p>',
			),
			'Plain text followed by linebreak' => array(
				'plain text' . CRLF,
				'<p>plain text </p>',
			),
			'Plain text followed by paragraph' => array(
				'plain text' . '<p>paragraph</p>',
				'<p>plain text</p>' . CRLF . '<p>paragraph</p>',
			),
			'Spacing paragraph' => array(
				'<p>&nbsp;</p>',
				'<p>&nbsp;</p>',
			),
			'Double spacing paragraph' => array(
				'<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
				'<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Paragraph' => array(
				'<p>paragraph</p>',
				'<p>paragraph</p>',
			),
			'Paragraph followed by linebreak' => array(
				'<p>paragraph</p>' . CRLF,
				'<p>paragraph</p>',
			),
			'Paragraph followed by spacing paragraph' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Paragraph followed by double spacing paragraph' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>',
			),
			'Paragraph followed by paragraph' => array(
				'<p>paragraph1</p>' . '<p>paragraph2</p>',
				'<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
				'<p>paragraph1</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by spacing paragraph and by paragraph' => array(
				'<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by spacing paragraph and by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by double spacing paragraph and by paragraph' => array(
				'<p>paragraph1</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<p>paragraph2</p>',
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by double spacing paragraph and by paragraph, linebreak-separated' => array(
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
				'<p>paragraph1</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>paragraph2</p>',
			),
			'Paragraph followed by block' => array(
				'<p>paragraph</p>' . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by block, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by spacing paragraph and by block' => array(
				'<p>paragraph</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by spacing paragraph and by block, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by double spacing paragraph and by block' => array(
				'<p>paragraph</p>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
			),
			'Paragraph followed by double spacing paragraph and by block, linebreak-separated' => array(
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
				'<p>paragraph</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block</h1>',
			),
			'Block followed by block' => array(
				'<h1>block1</h1>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by empty paragraph and by block' => array(
				'<h1>block1</h1>' . '<p></p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by empty paragraph and by block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p></p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by spacing paragraph and by block' => array(
				'<h1>block1</h1>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by spacing paragraph and by block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by double spacing paragraph and by block' => array(
				'<h1>block1</h1>' . '<p>&nbsp;</p>' . '<p>&nbsp;</p>' . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
			'Block followed by double spacing paragraph and by block, linebreak-separated' => array(
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
				'<h1>block1</h1>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<p>&nbsp;</p>' . CRLF . '<h1>block2</h1>',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRteProvider
	 */
	public function paragraphCorrectlyTransformedOnWayToDatabaseAndBackToRte($content, $expectedResult) {
		$thisConfig = array('proc.' => $this->fixture->procOptions);
		$this->assertEquals($expectedResult, $this->fixture->RTE_transform($this->fixture->RTE_transform($content, array(), 'db', $thisConfig), array(), 'rte', $thisConfig));
	}
}
?>