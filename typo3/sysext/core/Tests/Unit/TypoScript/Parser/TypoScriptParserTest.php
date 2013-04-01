<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Stefan Neufeind <info (at) speedpartner.de>
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
	 * Data provider for executeValueModifierReturnsModifiedResult
	 *
	 * @return array modifier name, modifier arguments, current value, expected result
	 */
	public function executeValueModifierDataProvider() {
		return array(
			'prependString with string' => array(
				'prependString',
				'abc',
				'!',
				'!abc'
			),
			'prependString with empty string' => array(
				'prependString',
				'foo',
				'',
				'foo',
			),
			'appendString with string' => array(
				'appendString',
				'abc',
				'!',
				'abc!',
			),
			'appendString with empty string' => array(
				'appendString',
				'abc',
				'',
				'abc',
			),
			'removeString removes simple string' => array(
				'removeString',
				'abcdef',
				'bc',
				'adef',
			),
			'removeString removes nothing if no match' => array(
				'removeString',
				'abcdef',
				'foo',
				'abcdef',
			),
			'removeString removes multiple matches' => array(
				'removeString',
				'FooBarFoo',
				'Foo',
				'Bar',
			),
			'replaceString replaces simple match' => array(
				'replaceString',
				'abcdef',
				'bc|123',
				'a123def',
			),
			'replaceString replaces simple match with nothing' => array(
				'replaceString',
				'abcdef',
				'bc',
				'adef',
			),
			'replaceString replaces multiple matches' => array(
				'replaceString',
				'FooBarFoo',
				'Foo|Bar',
				'BarBarBar',
			),
			'addToList adds at end of existing list' => array(
				'addToList',
				'123,456',
				'789',
				'123,456,789',
			),
			'addToList adds nothing' => array(
				'addToList',
				'123,456',
				'',
				'123,456,', // This result is probably not what we want (appended comma) ... fix it?
			),
			'addToList adds to empty list' => array(
				'addToList',
				'',
				'foo',
				'foo',
			),
			'removeFromList removes value from list' => array(
				'removeFromList',
				'123,456,789,abc',
				'456',
				'123,789,abc',
			),
			'removeFromList removes value at beginning of list' => array(
				'removeFromList',
				'123,456,abc',
				'123',
				'456,abc',
			),
			'removeFromList removes value at end of list' => array(
				'removeFromList',
				'123,456,abc',
				'abc',
				'123,456',
			),
			'removeFromList removes multiple values from list' => array(
				'removeFromList',
				'foo,123,bar,123',
				'123',
				'foo,bar',
			),
			'removeFromList removes empty values' => array(
				'removeFromList',
				'foo,,bar',
				'',
				'foo,bar',
			),
			'uniqueList removes duplicates' => array(
				'uniqueList',
				'123,456,abc,456,456',
				'',
				'123,456,abc',
			),
			'uniqueList removes duplicate empty list values' => array(
				'uniqueList',
				'123,,456,,abc',
				'',
				'123,,456,abc',
			),
			'reverseList returns list reversed' => array(
				'reverseList',
				'123,456,abc,456',
				'',
				'456,abc,456,123',
			),
			'reverseList keeps empty values' => array(
				'reverseList',
				',123,,456,abc,,456',
				'',
				'456,,abc,456,,123,',
			),
			'reverseList does not change single element' => array(
				'reverseList',
				'123',
				'',
				'123',
			),
			'sortList sorts a list' => array(
				'sortList',
				'10,100,0,20,abc',
				'',
				'0,10,20,100,abc',
			),
			'sortList sorts a list numeric' => array(
				'sortList',
				'10,0,100,-20,abc',
				'numeric',
				'-20,0,abc,10,100',
			),
			'sortList sorts a list descending' => array(
				'sortList',
				'10,100,0,20,abc,-20',
				'descending',
				'abc,100,20,10,0,-20',
			),
			'sortList sorts a list numeric descending' => array(
				'sortList',
				'10,100,0,20,abc,-20',
				'descending,numeric',
				'100,20,10,0,abc,-20',
			),
			'sortList ignores invalid modifier arguments' => array(
				'sortList',
				'10,100,20',
				'foo,descending,bar',
				'100,20,10',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider executeValueModifierDataProvider
	 */
	public function executeValueModifierReturnsModifiedResult($modifierName, $currentValue, $modifierArgument, $expected) {
		$actualValue = $this->typoScriptParser->_call('executeValueModifier', $modifierName, $modifierArgument, $currentValue);
		$this->assertEquals($expected, $actualValue);
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