<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

/***********************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
 *      2012 Oliver Hader <oliver.hader@typo3.org>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 **********************************************************************/

/**
 * Testcase for \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class TypoScriptParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
	 */
	protected $fixture;

	/**
	 * Sets up the test cases.
	 */
	protected function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser();
	}

	/**
	 * Tears down the test cases.
	 */
	protected function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @param string $typoScript
	 * @param array $expected
	 * @dataProvider typoScriptIsParsedToArrayDataProvider
	 * @test
	 */
	public function typoScriptIsParsedToArray($typoScript, array $expected) {
		$this->fixture->parse($typoScript);
		$this->assertEquals($expected, $this->fixture->setup);
	}

	/**
	 * @param string $typoScript
	 * @param array $expected
	 * @dataProvider typoScriptIsParsedToArrayDataProvider
	 * @test
	 */
	public function typoScriptIsStrictlyParsedToArray($typoScript, array $expected) {
		$this->fixture->strict = TRUE;
		$this->fixture->parse($typoScript);
		$this->assertEquals($expected, $this->fixture->setup);
	}

	/**
	 * @return array
	 */
	public function typoScriptIsParsedToArrayDataProvider() {
		return array(
			'simple assignment' => array(
				'key = value',
				array(
					'key' => 'value',
				)
			),
			'nested assignment' => array(
				'lib.key = value',
				array(
					'lib.' => array(
						'key' => 'value',
					),
				),
			),
			'nested structured assignment' => array(
				'lib {' . LF .
					'key = value' . LF .
				'}',
				array(
					'lib.' => array(
						'key' => 'value',
					),
				),
			),
			'multiline assignment' => array(
				'key (' . LF .
					'first' . LF .
					'second' . LF .
				')',
				array(
					'key' => 'first' . LF . 'second',
				),
			),
			'copying values' => array(
				'lib.default = value' . LF .
				'lib.copy < lib.default',
				array(
					'lib.' => array(
						'default' => 'value',
						'copy' => 'value',
					),
				),
			),
			'one-line hash comment' => array(
				'first = 1' . LF .
				'# ignore = me' . LF .
				'second = 2',
				array(
					'first' => '1',
					'second' => '2',
				),
			),
			'one-line slash comment' => array(
				'first = 1' . LF .
				'// ignore = me' . LF .
				'second = 2',
				array(
					'first' => '1',
					'second' => '2',
				),
			),
			'multi-line slash comment' => array(
				'first = 1' . LF .
				'/*' . LF .
					'ignore = me' . LF .
				'*/' . LF .
				'second = 2',
				array(
					'first' => '1',
					'second' => '2',
				),
			),
		);
	}

	/**
	 * @param string $typoScript
	 * @param array $expected
	 * @dataProvider typoScriptWithQuotedKeysIsParsedToArrayDataProvider
	 * @test
	 */
	public function typoScriptWithQuotedKeysIsParsedToArray($typoScript, array $expected) {
		$this->fixture->parse($typoScript);
		$this->assertEquals($expected, $this->fixture->setup);
	}

	/**
	 * @param string $typoScript
	 * @param array $expected
	 * @dataProvider typoScriptWithQuotedKeysIsParsedToArrayDataProvider
	 * @test
	 */
	public function typoScriptWithQuotedKeysIsStrictlyParsedToArray($typoScript, array $expected) {
		$this->fixture->strict = TRUE;
		$this->fixture->parse($typoScript);
		$this->assertEquals($expected, $this->fixture->setup);
	}

	/**
	 * @return array
	 */
	public function typoScriptWithQuotedKeysIsParsedToArrayDataProvider() {
		return array(
			'quoted key with regular characters' => array(
				'"key" = value',
				array(
					'key' => 'value',
				),
			),
			'nested quoted key with regular characters' => array(
				'lib."key" = value',
				array(
					'lib.' => array(
						'key' => 'value',
					),
				),
			),
			'nested structured quoted key with regular characters' => array(
				'lib {' . LF .
					'"key" = value' . LF .
				'}',
				array(
					'lib.' => array(
						'key' => 'value',
					),
				),
			),
			'quoted key containing dash' => array(
				'"the-key" = value',
				array(
					'the-key' => 'value',
				),
			),
			'nested quoted key containing dash' => array(
				'lib."the-key" = value',
				array(
					'lib.' => array(
						'the-key' => 'value',
					),
				),
			),
			'nested structured quoted key containing dash' => array(
				'lib {' . LF .
					'"the-key" = value' . LF .
				'}',
				array(
					'lib.' => array(
						'the-key' => 'value',
					),
				),
			),
			'quoted key containing dot' => array(
				'"the.key" = value',
				array(
					'the.key' => 'value',
				),
			),
			'nested quoted key containing dot' => array(
				'lib."the.key" = value',
				array(
					'lib.' => array(
						'the.key' => 'value',
					),
				),
			),
			'nested structured quoted key containing dot' => array(
				'lib {' . LF .
					'"the.key" = value' . LF .
				'}',
				array(
					'lib.' => array(
						'the.key' => 'value',
					),
				),
			),
			'quoted key containing equal operator' => array(
				'"the=key" = value',
				array(
					'the=key' => 'value',
				),
			),
			'nested quoted key containing equal operator' => array(
				'lib."the=key" = value',
				array(
					'lib.' => array(
						'the=key' => 'value',
					),
				),
			),
			'nested structured quoted key containing equal operator' => array(
				'lib {' . LF .
					'"the=key" = value' . LF .
				'}',
				array(
					'lib.' => array(
						'the=key' => 'value',
					),
				),
			),
		);
	}

}
?>