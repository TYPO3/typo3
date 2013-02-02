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
 * Testcase for \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
 */
class TypoScriptParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
	 */
	protected $typoScriptParser;

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	protected function setUp() {
		$this->typoScriptParser = $this->getAccessibleMock('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser', array('dummy'), array(), '', FALSE);
	}

	/**
	 * Tears down this test case.
	 *
	 * @return void
	 */
	protected function tearDown() {
		unset($this->typoScriptParser);
	}

	/**
	 * Data provider for executeTypoScriptOperatorFunction
	 *
	 * @return array expected values, function-name, function-arguments, current value (value to execute function with)
	 */
	public function executeTypoScriptOperatorFunctionDataProvider() {
		return array(
			array('!abc', 'prependString', '!', 'abc'),
			array('abc!', 'appendString', '!', 'abc'),
			array('adef', 'removeString', 'bc', 'abcdef'),
			array('a123def', 'replaceString', 'bc|123', 'abcdef'),
			array('123,456,abc', 'addToList', 'abc', '123,456'),
			array('123,abc', 'removeFromList', '456', '123,456,abc'),
			array('123,456,abc', 'uniqueList', '', '123,456,abc,456'),
			array('456,abc,456,123', 'reverseList', '', '123,456,abc,456'),
			array('123,456,456,abc', 'sortList', 'sortList', '123,456,abc,456'),
			array('10,20,100', 'sortList', 'sortList|num', '10,100,20'),
		);
	}

	/**
	 * @test
	 * @dataProvider executeTypoScriptOperatorFunctionDataProvider
	 */
	public function executeTypoScriptOperatorFunction($expected, $tsFunc, $tsFuncArg, $currentValue) {
		$this->assertEquals($expected, $this->typoScriptParser->_call('executeTypoScriptOperatorFunction', $tsFunc, $tsFuncArg, $currentValue));
	}

}

?>