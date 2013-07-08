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

use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcases for
 * TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class AbstractConditionMatcherTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Core\ApplicationContext
	 */
	protected $backupApplicationContext = NULL;

	/**
	 *
	 */
	public function setUp() {
		$this->backupApplicationContext = GeneralUtility::getApplicationContext();
	}

	/**
	 *
	 */
	public function tearDown() {
		Fixtures\GeneralUtilityFixture::setApplicationContext($this->backupApplicationContext);
		unset($this->backupApplicationContext);
	}

	/**
	 * Data provider with matching applicationContext conditions.
	 *
	 * @return array
	 */
	public function matchingApplicationContextConditions() {
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
	 * @dataProvider matchingApplicationContextConditions
	 */
	public function evaluateConditionCommonReturnsTrueForMatchingContexts($matchingContextCondition) {

		/** @var \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext */
		$applicationContext = new ApplicationContext('Production/Staging/Server2');
		Fixtures\GeneralUtilityFixture::setApplicationContext($applicationContext);

		/** @var \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $abstractConditionMatcherMock */
		$abstractConditionMatcherMock = $this->getMockForAbstractClass(
			'TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher',
			array(),
			'',
			TRUE,
			TRUE,
			TRUE,
			array('evaluateConditionCommon')
		);

		$method = new \ReflectionMethod(
			'TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher',
			'evaluateConditionCommon'
		);
		$method->setAccessible(TRUE);

		$this->assertTrue(
			$method->invokeArgs($abstractConditionMatcherMock, array('applicationContext', $matchingContextCondition))
		);
	}

	/**
	 * Data provider with not matching applicationContext conditions.
	 *
	 * @return array
	 */
	public function notMatchingApplicationContextConditions() {
		return array(
			array('Production'),
			array('Testing*'),
			array('Development/Profiling, Testing/Unit'),
			array('Testing/Staging/Server2'),
			array('/^Testing.*$/'),
			array('/^Production\/.+\/Host\d+$/'),
		);
	}

	/**
	 * @test
	 * @dataProvider notMatchingApplicationContextConditions
	 */
	public function evaluateConditionCommonReturnsNullForNotMatchingApplicationContexts($notMatchingApplicationContextCondition) {

		/** @var \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext */
		$applicationContext = new ApplicationContext('Production/Staging/Server2');
		Fixtures\GeneralUtilityFixture::setApplicationContext($applicationContext);

		/** @var \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $abstractConditionMatcherMock */
		$abstractConditionMatcherMock = $this->getMockForAbstractClass(
			'TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher',
			array(),
			'',
			TRUE,
			TRUE,
			TRUE,
			array('evaluateConditionCommon')
		);

		$method = new \ReflectionMethod(
			'TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher',
			'evaluateConditionCommon'
		);
		$method->setAccessible(TRUE);

		$this->assertNull(
			$method->invokeArgs($abstractConditionMatcherMock, array('applicationContext', $notMatchingApplicationContextCondition))
		);
	}
}
