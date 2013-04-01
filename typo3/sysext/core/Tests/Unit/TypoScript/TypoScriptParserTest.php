<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Test case for \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
 */
class TypoScriptParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $typoScriptParser;

	/**
	 * Set up
	 *
	 * @return void
	 */
	protected function setUp() {
		$accessibleClassName = $this->buildAccessibleProxy('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
		$this->typoScriptParser = new $accessibleClassName();
	}

	/**
	 * Tear down
	 *
	 * @return void
	 */
	protected function tearDown() {
		unset($this->typoScriptParser);
	}

	/**
	 * @param string $typoScript
	 * @param array $expected
	 * @dataProvider typoScriptIsParsedToArrayDataProvider
	 * @test
	 */
	public function typoScriptIsParsedToArray($typoScript, array $expected) {
		$this->typoScriptParser->parse($typoScript);
		$this->assertEquals($expected, $this->typoScriptParser->setup);
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
			'CSC example #1' => array(
				'linkParams.ATagParams.dataWrap =  class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
				array(
					'linkParams.' => array(
						'ATagParams.' => array(
							'dataWrap' => 'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
						),
					),
				),
			),
			'CSC example #2' => array(
				'linkParams.ATagParams {' . LF .
					'dataWrap = class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"' . LF .
				'}',
				array(
					'linkParams.' => array(
						'ATagParams.' => array(
							'dataWrap' => 'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
						),
					),
				),
			),
			'CSC example #3' => array(
				'linkParams.ATagParams.dataWrap (' . LF .
					'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"' . LF .
				')',
				array(
					'linkParams.' => array(
						'ATagParams.' => array(
							'dataWrap' => 'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
						),
					),
				),
			),
		);
	}

}
?>