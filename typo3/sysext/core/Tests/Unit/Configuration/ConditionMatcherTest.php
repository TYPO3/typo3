<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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

require_once(ExtensionManagementUtility::extPath('core', 'Tests/Unit/Configuration/ConditionMatcherUserFuncs.php'));

/**
 * Test class for ConditionMatcher functions
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class ConditionMatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/** @var ConditionMatcher */
	protected $conditionMatcher;

	public function setUp() {
		$this->conditionMatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TypoScript\\ConditionMatching\\ConditionMatcher');
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncIsCalled() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunction]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithSingleArgument() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithSingleArgument(x)]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithMultipleArguments() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithThreeArguments(1,2,3)]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncReturnsFalse() {
		$this->assertFalse($this->conditionMatcher->match('[userFunc = user_testFunctionFalse]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithMultipleArgumentsAndQuotes() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithThreeArguments(1,2,"3,4,5,6")]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithMultipleArgumentsAndQuotesAndSpaces() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithThreeArguments ( 1 , 2, "3, 4, 5, 6" ) ]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithMultipleArgumentsAndQuotesAndSpacesStripped() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithThreeArgumentsSpaces ( 1 , 2, "3, 4, 5, 6" ) ]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithSpacesInQuotes() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithSpaces(" 3, 4, 5, 6 ")]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithMultipleArgumentsAndQuotesAndSpacesStrippedAndEscapes() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithThreeArgumentsSpaces ( 1 , 2, "3, \"4, 5\", 6" ) ]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithQuoteMissing() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testFunctionWithQuoteMissing ("value \") ]'));
	}

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithQuotesInside() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = user_testQuotes("1 \" 2") ]'));
	}

}

?>