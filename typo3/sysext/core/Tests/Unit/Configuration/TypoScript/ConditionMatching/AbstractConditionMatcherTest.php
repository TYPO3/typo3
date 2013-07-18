<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\TypoScript\ConditionMatching;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Müller <typo3@t3node.com>
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
 * Testcases for
 * TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
 *
 * @author Steffen Müller <typo3@t3node.com
 */
class AbstractConditionMatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var $abstractConditionMatcherMock \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $abstractConditionMatcherMock = NULL;


	public function setUp() {
		$this->abstractConditionMatcherMock = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher');
		$this->abstractConditionMatcherMock->expects($this->any())
			->method('getContext')
			->will($this->returnValue('Production/Staging/Server2'));
	}

	/**
	 * @test
	 */
	public function getContextGetsContext() {
		$method = new \ReflectionMethod('TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher', 'getContext');
		$method->setAccessible(TRUE);

		$this->assertEquals('Production/Staging/Server2', $method->invoke($this->abstractConditionMatcherMock));
	}

	/**
	 * @test
	 * @dataProvider matchingContextConditions
	 */
	public function evaluateConditionCommonReturnsTrueForMatchingContexts($matchingContextCondition) {
		$method = new \ReflectionMethod('TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher', 'evaluateConditionCommon');
		$method->setAccessible(TRUE);

		$this->assertTrue($method->invokeArgs($this->abstractConditionMatcherMock, array('context', $matchingContextCondition)));
	}

	/**
	 * Data provider with matching context conditions.
	 *
	 * @return array
	 */
	public function matchingContextConditions() {
		return array(
			array('Production*'),
			array('Production/Staging/*'),
			array('Production/Staging/Server2'),
			array('/^Production.*$/'),
			array('/^Production\/.+\/Server\d+$/'),
		);
	}

	/**
	 * @test
	 * @dataProvider notMatchingContextConditions
	 */
	public function evaluateConditionCommonReturnsNullForNotMatchingContexts($notMatchingContextCondition) {
		$method = new \ReflectionMethod('TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher', 'evaluateConditionCommon');
		$method->setAccessible(TRUE);

		$this->assertNull($method->invokeArgs($this->abstractConditionMatcherMock, array('context', $notMatchingContextCondition)));
	}

	/**
	 * Data provider with not matching context conditions.
	 *
	 * @return array
	 */
	public function notMatchingContextConditions() {
		return array(
			array('Production'),
			array('Testing*'),
			array('Development/Profiling, Testing/Unit'),
			array('Testing/Staging/Server2'),
			array('/^Testing.*$/'),
			array('/^Production\/.+\/Host\d+$/'),
		);
	}
}
?>