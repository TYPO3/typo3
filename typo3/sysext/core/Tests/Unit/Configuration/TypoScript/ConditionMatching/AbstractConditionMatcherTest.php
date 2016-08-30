<?php
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\TypoScript\ConditionMatching;

/*
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test cases
 */
class AbstractConditionMatcherTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Core\ApplicationContext
     */
    protected $backupApplicationContext = null;

    /**
     * @var AbstractConditionMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $conditionMatcher;

    /**
     * @var \ReflectionMethod
     */
    protected $evaluateConditionCommonMethod;

    /**
     * Set up
     */
    protected function setUp()
    {
        require_once('Fixtures/ConditionMatcherUserFuncs.php');

        GeneralUtility::flushInternalRuntimeCaches();

        $this->backupApplicationContext = GeneralUtility::getApplicationContext();
        $this->conditionMatcher = $this->getMockForAbstractClass(AbstractConditionMatcher::class);
        $this->evaluateConditionCommonMethod = new \ReflectionMethod(AbstractConditionMatcher::class, 'evaluateConditionCommon');
        $this->evaluateConditionCommonMethod->setAccessible(true);
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        Fixtures\GeneralUtilityFixture::setApplicationContext($this->backupApplicationContext);
        parent::tearDown();
    }

    /**
     * Data provider with matching applicationContext conditions.
     *
     * @return array[]
     */
    public function matchingApplicationContextConditionsDataProvider()
    {
        return [
            ['Production*'],
            ['Production/Staging/*'],
            ['Production/Staging/Server2'],
            ['/^Production.*$/'],
            ['/^Production\\/.+\\/Server\\d+$/'],
        ];
    }

    /**
     * @test
     * @dataProvider matchingApplicationContextConditionsDataProvider
     */
    public function evaluateConditionCommonReturnsTrueForMatchingContexts($matchingContextCondition)
    {
        /** @var \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext */
        $applicationContext = new ApplicationContext('Production/Staging/Server2');
        Fixtures\GeneralUtilityFixture::setApplicationContext($applicationContext);

        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs($this->conditionMatcher, ['applicationContext', $matchingContextCondition])
        );
    }

    /**
     * Data provider with not matching applicationContext conditions.
     *
     * @return array[]
     */
    public function notMatchingApplicationContextConditionsDataProvider()
    {
        return [
            ['Production'],
            ['Testing*'],
            ['Development/Profiling, Testing/Unit'],
            ['Testing/Staging/Server2'],
            ['/^Testing.*$/'],
            ['/^Production\\/.+\\/Host\\d+$/'],
        ];
    }

    /**
     * @test
     * @dataProvider notMatchingApplicationContextConditionsDataProvider
     */
    public function evaluateConditionCommonReturnsNullForNotMatchingApplicationContexts($notMatchingApplicationContextCondition)
    {
        /** @var \TYPO3\CMS\Core\Core\ApplicationContext $applicationContext */
        $applicationContext = new ApplicationContext('Production/Staging/Server2');
        Fixtures\GeneralUtilityFixture::setApplicationContext($applicationContext);

        $this->assertFalse(
            $this->evaluateConditionCommonMethod->invokeArgs($this->conditionMatcher, ['applicationContext', $notMatchingApplicationContextCondition])
        );
    }

    /**
     * Data provider for evaluateConditionCommonEvaluatesIpAddressesCorrectly
     *
     * @return array[]
     */
    public function evaluateConditionCommonDevIpMaskDataProvider()
    {
        return [
            // [0] $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
            // [1] Actual IP
            // [2] Expected condition result
            'IP matches' => [
                '127.0.0.1',
                '127.0.0.1',
                true,
            ],
            'ipv4 wildcard subnet' => [
                '127.0.0.1/24',
                '127.0.0.2',
                true,
            ],
            'ipv6 wildcard subnet' => [
                '0:0::1/128',
                '::1',
                true,
            ],
            'List of addresses matches' => [
                '1.2.3.4, 5.6.7.8',
                '5.6.7.8',
                true,
            ],
            'IP does not match' => [
                '127.0.0.1',
                '127.0.0.2',
                false,
            ],
            'ipv4 subnet does not match' => [
                '127.0.0.1/8',
                '126.0.0.1',
                false,
            ],
            'ipv6 subnet does not match' => [
                '::1/127',
                '::2',
                false
            ],
            'List of addresses does not match' => [
                '127.0.0.1, ::1',
                '::2',
                false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider evaluateConditionCommonDevIpMaskDataProvider
     */
    public function evaluateConditionCommonEvaluatesIpAddressesCorrectly($devIpMask, $actualIp, $expectedResult)
    {
        // Do not trigger proxy stuff of GeneralUtility::getIndPEnv
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['reverseProxyIP']);

        $_SERVER['REMOTE_ADDR'] = $actualIp;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = $devIpMask;

        $actualResult = $this->evaluateConditionCommonMethod->invokeArgs($this->conditionMatcher, ['IP', 'devIP']);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function testUserFuncIsCalled()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunction']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithSingleArgument()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithSingleArgument(x)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithIntegerZeroArgument()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithSingleArgument(0)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithWhitespaceArgument()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithNoArgument( )']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleArguments()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments(1,2,3)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsNullBoolString()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments(0,true,"foo")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsNullStringBool()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments(0,"foo",true)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsStringBoolNull()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments("foo",true,0)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsStringNullBool()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments("foo",0,true)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsBoolNullString()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments(true,0,"foo")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsBoolStringNull()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments(true,"foo",0)']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsNullBoolStringSingleQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments(0,true,'foo')"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsNullStringBoolSingleQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments(0,'foo',true)"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsStringBoolNullSingleQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments('foo',true,0)"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsStringNullBoolSingleQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments('foo',0,true)"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsBoolNullStringSingleQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments(true,0,'foo')"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleDifferentArgumentsBoolStringNullSingleQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments(true,'foo',0)"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleSingleQuotedArguments()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', "user_testFunctionWithThreeArguments('foo','bar', 'baz')"]
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleSoubleQuotedArguments()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments("foo","bar","baz")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncReturnsFalse()
    {
        $this->assertFalse(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionFalse']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleArgumentsAndQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments(1,2,"3,4,5,6")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleArgumentsAndQuotesAndSpaces()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArguments ( 1 , 2, "3, 4, 5, 6" )']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleArgumentsAndQuotesAndSpacesStripped()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArgumentsSpaces ( 1 , 2, "3, 4, 5, 6" )']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithSpacesInQuotes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithSpaces(" 3, 4, 5, 6 ")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithMultipleArgumentsAndQuotesAndSpacesStrippedAndEscapes()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithThreeArgumentsSpaces ( 1 , 2, "3, \"4, 5\", 6" )']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithQuoteMissing()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testFunctionWithQuoteMissing ("value \")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithQuotesInside()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'user_testQuotes("1 \" 2")']
            )
        );
    }

    /**
     * @test
     */
    public function testUserFuncWithClassMethodCall()
    {
        $this->assertTrue(
            $this->evaluateConditionCommonMethod->invokeArgs(
                $this->conditionMatcher,
                ['userFunc', 'ConditionMatcherUserFunctions::isTrue(1)']
            )
        );
    }
}
