<?php
namespace TYPO3\CMS\Core\Tests\Unit\Encoder;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Helmut Hummel <helmut.hummel@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test cases for TYPO3\CMS\Core\Encoder\JavaScriptEncoder.
 *
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class JavaScriptEncoderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Encoder\JavaScriptEncoder
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Encoder\JavaScriptEncoder();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * Data provider for encodeEncodesCorrectly.
	 *
	 * @return array
	 */
	public function encodeEncodesCorrectlyDataProvider() {
		return array(
			'Immune characters are returned as is' => array(
				'._,',
				'._,'
			),
			'Alphanumerical characters are returned as is' => array(
				'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
				'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
			),
			'Angel brackets and ampersand are encoded' => array(
				'<>&',
				'\\x3C\\x3E\\x26'
			),
			'Quotes and slashes are encoded' => array(
				'"\'\\/',
				'\\x22\\x27\\x5C\\x2F'
			),
			'Empty string stays empty' => array(
				'',
				''
			),
			'Exclamation mark and space are properly encoded' => array(
				'Hello World!',
				'Hello\\x20World\\x21'
			),
			'Whitespaces are properly encoded' => array(
				TAB . LF . CR . ' ',
				'\\x09\\x0A\\x0D\\x20'
			),
			'Null byte is properly encoded' => array(
				chr(0),
				'\\x00'
			),
			'Umlauts are properly encoded' => array(
				'ÜüÖöÄä',
				'\\xDC\\xFC\\xD6\\xF6\\xC4\\xE4'
			)
		);
	}

	/**
	 * @test
	 * @param string $input
	 * @param string  $expected
	 * @dataProvider encodeEncodesCorrectlyDataProvider
	 */
	public function encodeEncodesCorrectly($input, $expected) {
		$this->assertSame($expected, $this->fixture->encode($input));
	}

}

?>