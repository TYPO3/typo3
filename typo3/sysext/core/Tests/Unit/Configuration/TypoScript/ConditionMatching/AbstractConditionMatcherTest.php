<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\TypoScript\ConditionMatching;

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

use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once(ExtensionManagementUtility::extPath('core', 'Tests/Unit/Configuration/ConditionMatcherUserFuncs.php'));

/**
 * Testcases for
 * TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
class AbstractConditionMatcherTest extends UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Core\ApplicationContext
	 */
	protected $backupApplicationContext = NULL;

	/**
	 * @var AbstractConditionMatcher|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $conditionMatcher;

	/**
	 * @var \ReflectionMethod
	 */
	protected $evaluateConditionCommonMethod;

	/**
	 *
	 */
	public function setUp() {
		$this->backupApplicationContext = GeneralUtility::getApplicationContext();
		$this->conditionMatcher = $this->getMockForAbstractClass('TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher');
		$this->evaluateConditionCommonMethod = new \ReflectionMethod('TYPO3\\CMS\\Core\\Configuration\\TypoScript\\ConditionMatching\\AbstractConditionMatcher', 'evaluateConditionCommon');
		$this->evaluateConditionCommonMethod->setAccessible(true);
	}

	/**
	 *
	 */
	public function tearDown() {
		Fixtures\GeneralUtilityFixture::setApplicationContext($this->backupApplicationContext);
		parent::tearDown();
	}

	/**
	 * Data provider with matching applicationContext conditions.
	 *
	 * @return array
	 */
	public function matchingApplicationContextConditionsDataProvider() {
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
	 * @dataProvider matchingApplicationContextConditionsDataProvider
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
	public function notMatchingApplicationContextConditionsDataProvider() {
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
	 * @dataProvider notMatchingApplicationContextConditionsDataProvider
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

	/**
	 * Data provider for evaluateConditionCommonEvaluatesIpAddressesCorrectly
	 *
	 * @return array
	 */
	public function evaluateConditionCommonDevIpMaskDataProvider() {
		return array(
			// [0] $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
			// [1] Actual IP
			// [2] Expected condition result
			'IP matches' => array(
				'127.0.0.1',
				'127.0.0.1',
				TRUE
			),
			'ipv4 wildcard subnet' => array(
				'127.0.0.1/24',
				'127.0.0.2',
				TRUE
			),
			'ipv6 wildcard subnet' => array(
				'0:0::1/128',
				'::1',
				TRUE
			),
			'List of addresses matches' => array(
				'1.2.3.4, 5.6.7.8',
				'5.6.7.8',
				TRUE
			),
			'IP does not match' => array(
				'127.0.0.1',
				'127.0.0.2',
				NULL
			),
			'ipv4 subnet does not match' => array(
				'127.0.0.1/8',
				'126.0.0.1',
				NULL
			),
			'ipv6 subnet does not match' => array(
				'::1/127',
				'::2',
				NULL
			),
			'List of addresses does not match' => array(
				'127.0.0.1, ::1',
				'::2',
				NULL
			),
		);
	}

	/**
	 * @test
	 * @dataProvider evaluateConditionCommonDevIpMaskDataProvider
	 */
	public function evaluateConditionCommonEvaluatesIpAddressesCorrectly($devIpMask, $actualIp, $expectedResult) {
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

		// Do not trigger proxy stuff of GeneralUtility::getIndPEnv
		unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']);

		$_SERVER['REMOTE_ADDR'] = $actualIp;
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIpMask;

		$actualResult = $method->invokeArgs($abstractConditionMatcherMock, array('IP', 'devIP'));
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function testUserFuncIsCalled() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunction')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithSingleArgument() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithSingleArgument(x)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithIntegerZeroArgument() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithSingleArgument(0)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithWhitespaceArgument() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithNoArgument( )')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleArguments() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments(1,2,3)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsNullBoolString() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments(0,true,"foo")')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsNullStringBool() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments(0,"foo",true)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsStringBoolNull() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments("foo",true,0)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsStringNullBool() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments("foo",0,true)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsBoolNullString() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments(true,0,"foo")')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsBoolStringNull() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments(true,"foo",0)')
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsNullBoolStringSingleQuotes() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments(0,true,'foo')")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsNullStringBoolSingleQuotes() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments(0,'foo',true)")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsStringBoolNullSingleQuotes() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments('foo',true,0)")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsStringNullBoolSingleQuotes() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments('foo',0,true)")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsBoolNullStringSingleQuotes() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments(true,0,'foo')")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleDifferentArgumentsBoolStringNullSingleQuotes() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments(true,'foo',0)")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleSingleQuotedArguments() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', "user_testFunctionWithThreeArguments('foo','bar', 'baz')")
			)
		);
	}

	/**
	 * @test
	 */
	public function testUserFuncWithMultipleSoubleQuotedArguments() {
		$this->assertTrue(
			$this->evaluateConditionCommonMethod->invokeArgs(
				$this->conditionMatcher,
				array('userFunc', 'user_testFunctionWithThreeArguments("foo","bar","baz")')
			)
		);
	}
}
