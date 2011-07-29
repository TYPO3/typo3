<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stanislas Rolland <typo3@sjbr.ca>
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
 * Testcase for class t3lib_parsehtml_proc
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
class t3lib_parsehtml_procTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_parsehtml_proc
	 */
	private $fixture = NULL;

	public function setUp() {
		$this->fixture = new t3lib_parsehtml_proc();
		$this->fixture->procOptions = array(
			'dontConvBRtoParagraph' => '1',
			'preserveDIVSections' => '1',
			'allowTagsOutside' => 'hr, address',
		);
	}

	public function tearDown() {
		unset($this->fixture);
	}
	/**
	 * Data provider for TS_transform_db
	 */
	public static function hrTagCorrectlyTransformedOnWayToDataBaseDataProvider() {
		return array(
			'single hr' => array(
				'<hr />',
				'<hr />',
			),
			'non-xhtml single hr' => array(
				'<hr/>',
				'<hr />',
			),
			'double hr' => array(
				'<hr /><hr />',
				'<hr />' . LF . '<hr />',
			),
			'linefeed followed by hr' => array(
				LF . '<hr />',
				'<hr />',
			),
			'white space followed by hr' => array(
				' <hr />',
				' ' . LF . '<hr />',
			),
			'white space followed linefeed and hr' => array(
				' ' . LF . '<hr />',
				' ' . LF . '<hr />',
			),
			'br followed by hr' => array(
				'<br /><hr />',
				'<br />' . LF . '<hr />',
			),
			'br followed by linefeed and hr' => array(
				'<br />' . LF . '<hr />',
				'<br />' . LF . '<hr />',
			),
			'preserved div followed by hr' => array(
				'<div>Some text</div><hr />',
				'<div>Some text</div>' . LF . '<hr />',
			),
			'preserved div followed by linefeed and hr' => array(
				'<div>Some text</div>' . LF . '<hr />',
				'<div>Some text</div>' . LF . '<hr />',
			),
			'h1 followed by linefeed and hr' => array(
				'<h1>Some text</h1>' . LF . '<hr />',
				'<h1>Some text</h1>' . LF . '<hr />',
			),
			'paragraph followed by linefeed and hr' => array(
				'<p>Some text</p>' . LF . '<hr />',
				'Some text' . LF . '<hr />',
			),
			'some text followed by hr' => array(
				'Some text<hr />',
				'Some text' . LF . '<hr />',
			),
			'some text followed by linefeed and hr' => array(
				'Some text' . LF . '<hr />',
				'Some text' . LF . '<hr />',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider hrTagCorrectlyTransformedOnWayToDataBaseDataProvider
	 */
	public function hrTagCorrectlyTransformedOnWayToDataBase($content, $expectedResult) {
		//die(htmlspecialchars($expectedResult) . '-' . htmlspecialchars($this->fixture->TS_transform_db($content)));
			// Assume the transformation is ts_css
		$this->assertEquals($expectedResult, $this->fixture->TS_transform_db($content, TRUE));
	}
}
?>