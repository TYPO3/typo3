<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Testcase for class t3lib_htmlmail
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_htmlmailTest extends tx_phpunit_testcase {
	/**
	 * Data provider for regexCreatedWithTagRegexSplitsString
	 */
	public static function regexCreatedWithTagRegexSplitsStringDataProvider() {
		return array(
			'single split without whitespace' => array(
				'p',
				'<p>foo</p>',
				array('<p>foo</p>')
			),
			'single split' => array(
				'p',
				'<p class="1">foo</p>',
				array('', 'class="1">foo</p>')
			),
			'multiple splits' => array(
				'p',
				'<p class="1">foo</p><p class="2">bar</p>',
				array('','class="1">foo</p>','class="2">bar</p>')
			),
			'multiple splits with multiple tags' => array(
				array('div', 'p'),
				'<b><div class="div"><p class="p1">foo</p><p class="p2">bar</p></div></b>',
				array('<b>', 'class="div">', 'class="p1">foo</p>', 'class="p2">bar</p></div></b>'),
			),
			'case insenitive split' => array(
				array('p', 'div'),
				'<b><DiV class="div"><P class="p1">foo</P></div></b>',
				array('<b>', 'class="div">', 'class="p1">foo</P></div></b>'),
			),
		);
	}

	/**
	 * @test
	 * @dataProvider regexCreatedWithTagRegexSplitsStringDataProvider
	 */
	public function regexCreatedWithTagRegexSplitsString($tag, $testString, $expectedResult) {
		$regex = t3lib_htmlmail::tag_regex($tag);
		$this->assertSame($expectedResult, preg_split($regex, $testString));
	}
}
?>