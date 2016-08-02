<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Configuration\TypoScript\ConditionMatching;

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

use TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Tests\Unit\Configuration\TypoScript\ConditionMatching\Fixtures\TestConditionException;

/**
 * Test case
 */
class ConditionMatcherTest extends UnitTestCase
{
    /**
     * @var string Name of a key in $GLOBALS for this test
     */
    protected $testGlobalNamespace;

    /**
     * @var \TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher Class under test
     */
    protected $matchCondition;

    protected function setUp()
    {
        $this->testGlobalNamespace = $this->getUniqueId('TEST');
        GeneralUtility::flushInternalRuntimeCaches();
        $GLOBALS[$this->testGlobalNamespace] = array();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $GLOBALS['TSFE']->tmpl->rootLine = array(
            2 => array('uid' => 121, 'pid' => 111),
            1 => array('uid' => 111, 'pid' => 101),
            0 => array('uid' => 101, 'pid' => 0)
        );
        $this->matchCondition = GeneralUtility::makeInstance(ConditionMatcher::class);
    }

    /**
     * Tests whether a faulty expression fails.
     *
     * @test
     */
    public function simulateDisabledMatchAllConditionsFailsOnFaultyExpression()
    {
        $this->matchCondition->matchAll = false;
        $this->assertFalse($this->matchCondition->match('[nullCondition = This expression would return FALSE in general]'));
    }

    /**
     * Tests whether simulating positive matches for all conditions succeeds.
     *
     * @test
     */
    public function simulateEnabledMatchAllConditionsSucceeds()
    {
        $this->matchCondition->setSimulateMatchResult(true);
        $this->assertTrue($this->matchCondition->match('[nullCondition = This expression would return FALSE in general]'));
    }

    /**
     * Tests whether simulating positive matches for specific conditions succeeds.
     *
     * @test
     */
    public function simulateEnabledMatchSpecificConditionsSucceeds()
    {
        $testCondition = '[' . $this->getUniqueId('test') . ' = Any condition to simulate a positive match]';
        $this->matchCondition->setSimulateMatchConditions(array($testCondition));
        $this->assertTrue($this->matchCondition->match($testCondition));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesSingleLanguageExpression()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->assertTrue($this->matchCondition->match('[language = *de*]'));
        $this->assertTrue($this->matchCondition->match('[language = *de-de*]'));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesMultipleLanguagesExpression()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->assertTrue($this->matchCondition->match('[language = *en*,*de*]'));
        $this->assertTrue($this->matchCondition->match('[language = *en-us*,*de-de*]'));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesCompleteLanguagesExpression()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->assertTrue($this->matchCondition->match('[language = de-de,de;q=0.8,en-us;q=0.5,en;q=0.3]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId()
    {
        $GLOBALS['TSFE']->gr_list = '13,14,15';
        $this->assertTrue($this->matchCondition->match('[usergroup = 13]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesMultipleUserGroupId()
    {
        $GLOBALS['TSFE']->gr_list = '13,14,15';
        $this->assertTrue($this->matchCondition->match('[usergroup = 999,15,14,13]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionDoesNotMatchDefaulUserGroupIds()
    {
        $GLOBALS['TSFE']->gr_list = '0,-1';
        $this->assertFalse($this->matchCondition->match('[usergroup = 0,-1]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser()
    {
        $GLOBALS['TSFE']->loginUser = true;
        $GLOBALS['TSFE']->fe_user->user['uid'] = 13;
        $this->assertTrue($this->matchCondition->match('[loginUser = *]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesSingleLoggedInUser()
    {
        $GLOBALS['TSFE']->loginUser = true;
        $GLOBALS['TSFE']->fe_user->user['uid'] = 13;
        $this->assertTrue($this->matchCondition->match('[loginUser = 13]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesMultipleLoggedInUsers()
    {
        $GLOBALS['TSFE']->loginUser = true;
        $GLOBALS['TSFE']->fe_user->user['uid'] = 13;
        $this->assertTrue($this->matchCondition->match('[loginUser = 999,13]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionDoesNotMatchIfNotUserIsLoggedId()
    {
        $GLOBALS['TSFE']->loginUser = false;
        $GLOBALS['TSFE']->fe_user->user['uid'] = 13;
        $this->assertFalse($this->matchCondition->match('[loginUser = *]'));
        $this->assertFalse($this->matchCondition->match('[loginUser = 13]'));
    }

    /**
     * Tests whether user is not logged in
     *
     * @test
     */
    public function loginUserConditionMatchIfUserIsNotLoggedIn()
    {
        $GLOBALS['TSFE']->loginUser = false;
        $this->assertTrue($this->matchCondition->match('[loginUser = ]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEqualExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 = 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 = 10.1]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 == 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 == 10.1]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEqualExpressionWithMultipleValues()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 = 10|20|30]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 = 10.1|20.2|30.3]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 = 10|20|30]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20.2 = 10.1|20.2|30.3]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 == 10|20|30]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 == 10.1|20.2|30.3]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 == 10|20|30]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20.2 == 10.1|20.2|30.3]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnNotEqualExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2]'));
    }

    /**
     * Tests whether numerical comparison does not match.
     *
     * @test
     */
    public function globalVarConditionDoesNotMatchOnNotEqualExpression()
    {
        $this->assertFalse($this->matchCondition->match('[globalVar = LIT:10 != 10]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnNotEqualExpressionWithMultipleValues()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20|30]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2|20.3]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnLowerThanExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 < 20]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 < 10.2]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnLowerThanOrEqualExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 <= 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 <= 20]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 <= 10.1]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 <= 10.2]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnGreaterThanExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 > 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 > 10.1]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnGreaterThanOrEqualExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 >= 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 >= 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 >= 10.1]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 >= 10.1]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEmptyExpressionWithNoValueSet()
    {
        $testKey = $this->getUniqueId('test');
        $this->assertTrue($this->matchCondition->match('[globalVar = GP:' . $testKey . '=]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionDoesNotMatchOnEmptyExpressionWithValueSetToZero()
    {
        $testKey = $this->getUniqueId('test');
        $_GET = array();
        $_POST = array($testKey => 0);
        $this->assertFalse($this->matchCondition->match('[globalVar = GP:' . $testKey . '=]'));
        $this->assertFalse($this->matchCondition->match('[globalVar = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether an array with zero as key matches its value
     *
     * @test
     */
    public function globalVarConditionMatchesOnArrayExpressionWithZeroAsKey()
    {
        $testKey = $this->getUniqueId('test');
        $testValue = '1';
        $_GET = array();
        $_POST = array($testKey => array('0' => $testValue));
        $this->assertTrue($this->matchCondition->match('[globalVar = GP:' . $testKey . '|0=' . $testValue . ']'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEqualExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.Test.Condition]'));
        $this->assertFalse($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEmptyExpressionWithValueSetToEmptyString()
    {
        $testKey = $this->getUniqueId('test');
        $_GET = array();
        $_POST = array($testKey => '');
        $this->assertTrue($this->matchCondition->match('[globalString = GP:' . $testKey . '=]'));
        $this->assertTrue($this->matchCondition->match('[globalString = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEmptyLiteralExpressionWithValueSetToEmptyString()
    {
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:=]'));
        $this->assertTrue($this->matchCondition->match('[globalString = LIT: = ]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesWildcardExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3?Test?Condition]'));
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.T*t.Condition]'));
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3?T*t?Condition]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesRegularExpression()
    {
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = /^[A-Za-z3.]+$/]'));
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = /^TYPO3\\..+Condition$/]'));
        $this->assertFalse($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = /^FALSE/]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesEmptyRegularExpression()
    {
        $testKey = $this->getUniqueId('test');
        $_SERVER[$testKey] = '';
        $this->assertTrue($this->matchCondition->match('[globalString = _SERVER|' . $testKey . ' = /^$/]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesSingleValue()
    {
        $this->assertTrue($this->matchCondition->match('[treeLevel = 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues()
    {
        $this->assertTrue($this->matchCondition->match('[treeLevel = 999,998,2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue()
    {
        $this->assertFalse($this->matchCondition->match('[treeLevel = 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDupinRootline = 111]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesMultiplePageIdsInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDupinRootline = 999,111,101]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertFalse($this->matchCondition->match('[PIDupinRootline = 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchLastPageIdInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertFalse($this->matchCondition->match('[PIDupinRootline = 121]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDinRootline = 111]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesMultiplePageIdsInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDinRootline = 999,111,101]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDinRootline = 121]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline()
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertFalse($this->matchCondition->match('[PIDinRootline = 999]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesOlderRelease()
    {
        $this->assertTrue($this->matchCondition->match('[compatVersion = 7.0]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesSameRelease()
    {
        $this->assertTrue($this->matchCondition->match('[compatVersion = ' . TYPO3_branch . ']'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionDoesNotMatchNewerRelease()
    {
        $this->assertFalse($this->matchCondition->match('[compatVersion = 15.0]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'GP'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceGP()
    {
        $_GET = array('testGet' => 'getTest');
        $_POST = array('testPost' => 'postTest');
        $this->assertTrue($this->matchCondition->match('[globalString = GP:testGet = getTest]'));
        $this->assertTrue($this->matchCondition->match('[globalString = GP:testPost = postTest]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'TSFE'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceTSFE()
    {
        $GLOBALS['TSFE']->id = 1234567;
        $GLOBALS['TSFE']->testSimpleObject = new \stdClass();
        $GLOBALS['TSFE']->testSimpleObject->testSimpleVariable = 'testValue';
        $this->assertTrue($this->matchCondition->match('[globalString = TSFE:id = 1234567]'));
        $this->assertTrue($this->matchCondition->match('[globalString = TSFE:testSimpleObject|testSimpleVariable = testValue]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'ENV'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceENV()
    {
        $testKey = $this->getUniqueId('test');
        putenv($testKey . '=testValue');
        $this->assertTrue($this->matchCondition->match('[globalString = ENV:' . $testKey . ' = testValue]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'IENV'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceIENV()
    {
        $_SERVER['HTTP_HOST'] = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') . ':1234567';
        $this->assertTrue($this->matchCondition->match('[globalString = IENV:TYPO3_PORT = 1234567]'));
    }

    /**
     * Tests whether the generic fetching of variables works with any global namespace.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithAnyGlobalNamespace()
    {
        $GLOBALS[$this->testGlobalNamespace] = array(
            'first' => 'testFirst',
            'second' => array('third' => 'testThird')
        );
        $this->assertTrue($this->matchCondition->match('[globalString = ' . $this->testGlobalNamespace . '|first = testFirst]'));
        $this->assertTrue($this->matchCondition->match('[globalString = ' . $this->testGlobalNamespace . '|second|third = testThird]'));
    }

    /**
     * @test
     */
    public function matchThrowsExceptionIfConditionClassDoesNotInheritFromAbstractCondition()
    {
        $this->expectException(InvalidTypoScriptConditionException::class);
        $this->expectExceptionCode(1410286153);
        $this->matchCondition->match('[stdClass = foo]');
    }

    /**
     * @test
     */
    public function matchCallsTestConditionAndHandsOverParameters()
    {
        $this->expectException(TestConditionException::class);
        $this->expectExceptionCode(1411581139);
        $this->matchCondition->match('[TYPO3\\CMS\\Frontend\\Tests\\Unit\\Configuration\\TypoScript\\ConditionMatching\\Fixtures\\TestCondition = 7, != 6]');
    }
}
