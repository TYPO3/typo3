<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
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

	/**
	 * @test
	 * @return void
	 */
	public function testUserFuncWithClassMethodCall() {
		$this->assertTrue($this->conditionMatcher->match('[userFunc = ConditionMatcherUserFunctions::isTrue(1)]'));
	}

}

?>
