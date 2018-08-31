<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\UnitDeprecated\Configuration\TypoScript\ConditionMatching;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Tests\UnitDeprecated\Configuration\TypoScript\ConditionMatching\Fixtures\TestConditionException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ConditionMatcherTest extends UnitTestCase
{
    /**
     * @var ConditionMatcher
     */
    protected $subject;

    /**
     * @var string
     */
    protected $testGlobalNamespace;

    /**
     * @var bool Reset singletons
     */
    protected $resetSingletonInstances = true;

    protected function setUp(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();

        $this->testGlobalNamespace = $this->getUniqueId('TEST');
        $GLOBALS[$this->testGlobalNamespace] = [];
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->page = [];
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $GLOBALS['TSFE']->tmpl->rootLine = [
            2 => ['uid' => 121, 'pid' => 111],
            1 => ['uid' => 111, 'pid' => 101],
            0 => ['uid' => 101, 'pid' => 0]
        ];

        $frontedUserAuthentication = $this->getMockBuilder(FrontendUserAuthentication::class)
            ->setMethods(['dummy'])
            ->getMock();

        $frontedUserAuthentication->user['uid'] = 13;
        $frontedUserAuthentication->groupData['uid'] = [14];
        $GLOBALS['TSFE']->fe_user = $frontedUserAuthentication;
        $this->getFreshConditionMatcher();
    }

    protected function getFreshConditionMatcher()
    {
        $this->subject = new ConditionMatcher(new Context([
            'frontend.user' => new UserAspect($GLOBALS['TSFE']->fe_user)
        ]));
        $this->subject->setLogger($this->prophesize(Logger::class)->reveal());
    }

    /**
     * Tests whether a faulty expression fails.
     *
     * @test
     */
    public function simulateDisabledMatchAllConditionsFailsOnFaultyExpression(): void
    {
        $this->getFreshConditionMatcher();
        $this->assertFalse($this->subject->match('[nullCondition = This expression would return FALSE in general]'));
    }

    /**
     * Tests whether simulating positive matches for all conditions succeeds.
     *
     * @test
     */
    public function simulateEnabledMatchAllConditionsSucceeds(): void
    {
        $this->getFreshConditionMatcher();
        $this->subject->setSimulateMatchResult(true);
        $this->assertTrue($this->subject->match('[nullCondition = This expression would return FALSE in general]'));
    }

    /**
     * Tests whether simulating positive matches for specific conditions succeeds.
     *
     * @test
     */
    public function simulateEnabledMatchSpecificConditionsSucceeds(): void
    {
        $this->getFreshConditionMatcher();
        $testCondition = '[' . $this->getUniqueId('test') . ' = Any condition to simulate a positive match]';
        $this->subject->setSimulateMatchConditions([$testCondition]);
        $this->assertTrue($this->subject->match($testCondition));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesSingleLanguageExpression(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[language = *de*]'));
        $this->assertTrue($this->subject->match('[language = *de-de*]'));
        // Test expression language
        // @TODO: not work yet, looks like test setup issue
//        $this->assertTrue($this->subject->match('[like(request.getNormalizedParams().getHttpAcceptLanguage(), "**de*")]'));
//        $this->assertTrue($this->subject->match('[like(request.getNormalizedParams().getHttpAcceptLanguage(), "**de-de*")]'));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesMultipleLanguagesExpression(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[language = *en*,*de*]'));
        $this->assertTrue($this->subject->match('[language = *en-us*,*de-de*]'));
        // Test expression language
        // @TODO: not work yet, looks like test setup issue
//        $this->assertTrue($this->subject->match('[like(request.getNormalizedParams().getHttpAcceptLanguage(), "*en*,*de*")]'));
//        $this->assertTrue($this->subject->match('[like(request.getNormalizedParams().getHttpAcceptLanguage(), "*en-us*,*de-de*")]'));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesCompleteLanguagesExpression(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[language = de-de,de;q=0.8,en-us;q=0.5,en;q=0.3]'));
        // Test expression language
        // @TODO: not work yet, looks like test setup issue
//        $this->assertTrue($this->subject->match('[request.getNormalizedParams().getHttpAcceptLanguage() == "de-de,de;q=0.8,en-us;q=0.5,en;q=0.3"]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId(): void
    {
        $subject = new ConditionMatcher(new Context([
            'frontend.user' => new UserAspect(new FrontendUserAuthentication(), [13, 14, 15])
        ]));
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $this->assertTrue($subject->match('[usergroup = 13]'));
        // Test expression language
        $this->assertTrue($subject->match('[usergroup(13)]'));
        $this->assertTrue($subject->match('[usergroup("13")]'));
        $this->assertTrue($subject->match('[usergroup(\'13\')]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesMultipleUserGroupId(): void
    {
        $subject = new ConditionMatcher(new Context([
            'frontend.user' => new UserAspect(new FrontendUserAuthentication(), [13, 14, 15])
        ]));
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $this->assertTrue($subject->match('[usergroup = 999,15,14,13]'));
        // Test expression language
        $this->assertFalse($subject->match('[usergroup(999,15,14,13)]'));
        $this->assertTrue($subject->match('[usergroup("999,15,14,13")]'));
        $this->assertTrue($subject->match('[usergroup(\'999,15,14,13\')]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionDoesNotMatchDefaulUserGroupIds(): void
    {
        $subject = new ConditionMatcher(new Context([
            'frontend.user' => new UserAspect(new FrontendUserAuthentication(), [0, -1])
        ]));
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $this->assertFalse($subject->match('[usergroup = 0,-1]'));
        // Test expression language
        $this->assertFalse($subject->match('[usergroup("0,-1")]'));
        $this->assertFalse($subject->match('[usergroup(\'0,-1\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser(): void
    {
        $this->getFreshConditionMatcher();
        // @TODO: not work yet, looks like test setup issue
        $this->assertTrue($this->subject->match('[loginUser = *]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[loginUser("*")]'));
        $this->assertTrue($this->subject->match('[loginUser(\'*\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesSingleLoggedInUser(): void
    {
        $this->getFreshConditionMatcher();
        // @TODO: not work yet, looks like test setup issue
        $this->assertTrue($this->subject->match('[loginUser = 13]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[loginUser(13)]'));
        $this->assertTrue($this->subject->match('[loginUser("13")]'));
        $this->assertTrue($this->subject->match('[loginUser(\'13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesMultipleLoggedInUsers(): void
    {
        $this->getFreshConditionMatcher();
        // @TODO: not work yet, looks like test setup issue
        $this->assertTrue($this->subject->match('[loginUser = 999,13]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[loginUser("999,13")]'));
        $this->assertTrue($this->subject->match('[loginUser(\'999,13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionDoesNotMatchIfNotUserIsLoggedId(): void
    {
        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 13;
        $subject = new ConditionMatcher(new Context([
            'frontend.user' => new UserAspect($user)
        ]));
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $this->assertFalse($subject->match('[loginUser = *]'));
        $this->assertFalse($subject->match('[loginUser = 13]'));
        // Test expression language
        $this->assertFalse($subject->match('[loginUser("*")]'));
        $this->assertTrue($subject->match('[loginUser("*") == false]'));
        $this->assertFalse($subject->match('[loginUser("13")]'));
        $this->assertFalse($subject->match('[loginUser(\'*\')]'));
        $this->assertFalse($subject->match('[loginUser(\'13\')]'));
    }

    /**
     * Tests whether user is not logged in
     *
     * @test
     */
    public function loginUserConditionMatchIfUserIsNotLoggedIn(): void
    {
        $user = new FrontendUserAuthentication();
        $subject = new ConditionMatcher(new Context([
            'frontend.user' => new UserAspect($user)
        ]));
        $loggerProphecy = $this->prophesize(Logger::class);
        $subject->setLogger($loggerProphecy->reveal());
        $this->assertTrue($subject->match('[loginUser = ]'));
        // Test expression language
        $this->assertTrue($subject->match('[loginUser(\'*\') == false]'));
        $this->assertTrue($subject->match('[loginUser("*") == false]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEqualExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 = 10]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 = 10.1]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 == 10]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 == 10.1]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEqualExpressionWithMultipleValues(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 = 10|20|30]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 = 10.1|20.2|30.3]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:20 = 10|20|30]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:20.2 = 10.1|20.2|30.3]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 == 10|20|30]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 == 10.1|20.2|30.3]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:20 == 10|20|30]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:20.2 == 10.1|20.2|30.3]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnNotEqualExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 != 20]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 != 10.2]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison does not match.
     *
     * @test
     */
    public function globalVarConditionDoesNotMatchOnNotEqualExpression(): void
    {
        $this->assertFalse($this->subject->match('[globalVar = LIT:10 != 10]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnNotEqualExpressionWithMultipleValues(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 != 20|30]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 != 10.2|20.3]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnLowerThanExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 < 20]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 < 10.2]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnLowerThanOrEqualExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 <= 10]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 <= 20]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 <= 10.1]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 <= 10.2]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnGreaterThanExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:20 > 10]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.2 > 10.1]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnGreaterThanOrEqualExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalVar = LIT:10 >= 10]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:20 >= 10]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.1 >= 10.1]'));
        $this->assertTrue($this->subject->match('[globalVar = LIT:10.2 >= 10.1]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEmptyExpressionWithNoValueSet(): void
    {
        $testKey = $this->getUniqueId('test');
        $this->assertTrue($this->subject->match('[globalVar = GP:' . $testKey . '=]'));
        $this->assertTrue($this->subject->match('[globalVar = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionDoesNotMatchOnEmptyExpressionWithValueSetToZero(): void
    {
        $testKey = $this->getUniqueId('test');
        $_GET = [];
        $_POST = [$testKey => 0];
        $this->assertFalse($this->subject->match('[globalVar = GP:' . $testKey . '=]'));
        $this->assertFalse($this->subject->match('[globalVar = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether an array with zero as key matches its value
     *
     * @test
     */
    public function globalVarConditionMatchesOnArrayExpressionWithZeroAsKey(): void
    {
        $testKey = $this->getUniqueId('test');
        $testValue = '1';
        $_GET = [];
        $_POST = [$testKey => ['0' => $testValue]];
        $this->assertTrue($this->subject->match('[globalVar = GP:' . $testKey . '|0=' . $testValue . ']'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEqualExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.Test.Condition]'));
        $this->assertFalse($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEmptyExpressionWithValueSetToEmptyString(): void
    {
        $testKey = $this->getUniqueId('test');
        $_GET = [];
        $_POST = [$testKey => ''];
        $this->assertTrue($this->subject->match('[globalString = GP:' . $testKey . '=]'));
        $this->assertTrue($this->subject->match('[globalString = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEmptyLiteralExpressionWithValueSetToEmptyString(): void
    {
        $this->assertTrue($this->subject->match('[globalString = LIT:=]'));
        $this->assertTrue($this->subject->match('[globalString = LIT: = ]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesWildcardExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3?Test?Condition]'));
        $this->assertTrue($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.T*t.Condition]'));
        $this->assertTrue($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3?T*t?Condition]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesRegularExpression(): void
    {
        $this->assertTrue($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = /^[A-Za-z3.]+$/]'));
        $this->assertTrue($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = /^TYPO3\\..+Condition$/]'));
        $this->assertFalse($this->subject->match('[globalString = LIT:TYPO3.Test.Condition = /^FALSE/]'));
        // Test expression language
        // Access with LIT is not possible in expression language, because constants available as variable
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesEmptyRegularExpression(): void
    {
        $testKey = $this->getUniqueId('test');
        $GLOBALS['_SERVER'][$testKey] = '';
        $this->assertTrue($this->subject->match('[globalString = _SERVER|' . $testKey . ' = /^$/]'));
        // Test expression language
        // Access request by request() method
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesSingleValue(): void
    {
        $this->assertTrue($this->subject->match('[treeLevel = 2]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        $this->assertTrue($this->subject->match('[treeLevel = 999,998,2]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        $this->assertFalse($this->subject->match('[treeLevel = 999]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[tree.level == 999]'));
    }

    /**
     * @return array
     */
    public function pageDataProvider(): array
    {
        return [
            '[page|layout = 0]' => ['[page|layout = 0]', true],
            '[page|layout = 1]' => ['[page|layout = 1]', false],
            '[page|title = Foo]' => ['[page|title = Foo]', true],
        ];
    }

    /**
     * @test
     * @dataProvider pageDataProvider
     * @param string $expression
     * @param bool $expected
     */
    public function checkConditionMatcherForPage(string $expression, bool $expected): void
    {
        $GLOBALS['TSFE']->page = ['title' => 'Foo', 'layout' => 0];
        $this->getFreshConditionMatcher();
        $this->assertSame($expected, $this->subject->match($expression));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[PIDupinRootline = 111]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[111 in tree.rootLineIds]'));
        $this->assertTrue($this->subject->match('["111" in tree.rootLineIds]'));
        $this->assertTrue($this->subject->match('[\'111\' in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesMultiplePageIdsInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[PIDupinRootline = 999,111,101]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[999 in tree.rootLineIds][111 in tree.rootLineIds][101 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertFalse($this->subject->match('[PIDupinRootline = 999]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchLastPageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertFalse($this->subject->match('[PIDupinRootline = 121]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[page.uid != 121 && 121 in rootLineUids]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[PIDinRootline = 111]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[111 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesMultiplePageIdsInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[PIDinRootline = 999,111,101]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[999 in tree.rootLineIds][111 in tree.rootLineIds][101 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[PIDinRootline = 121]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[121 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertFalse($this->subject->match('[PIDinRootline = 999]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesOlderRelease(): void
    {
        $this->assertTrue($this->subject->match('[compatVersion = 7.0]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[compatVersion(7.0)]'));
        $this->assertTrue($this->subject->match('[compatVersion("7.0")]'));
        $this->assertTrue($this->subject->match('[compatVersion(\'7.0\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesSameRelease(): void
    {
        $this->assertTrue($this->subject->match('[compatVersion = ' . TYPO3_branch . ']'));
        // Test expression language
        $this->assertTrue($this->subject->match('[compatVersion(' . TYPO3_branch . ')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionDoesNotMatchNewerRelease(): void
    {
        $this->assertFalse($this->subject->match('[compatVersion = 15.0]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[compatVersion(15.0)]'));
        $this->assertFalse($this->subject->match('[compatVersion("15.0")]'));
        $this->assertFalse($this->subject->match('[compatVersion(\'15.0\')]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'GP'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceGP(): void
    {
        $_GET = ['testGet' => 'getTest'];
        $_POST = ['testPost' => 'postTest'];
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[globalString = GP:testGet = getTest]'));
        $this->assertTrue($this->subject->match('[globalString = GP:testPost = postTest]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'TSFE'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceTSFE(): void
    {
        $GLOBALS['TSFE']->id = 1234567;
        $GLOBALS['TSFE']->testSimpleObject = new \stdClass();
        $GLOBALS['TSFE']->testSimpleObject->testSimpleVariable = 'testValue';

        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[globalString = TSFE:id = 1234567]'));
        $this->assertTrue($this->subject->match('[globalString = TSFE:testSimpleObject|testSimpleVariable = testValue]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[getTSFE().id == 1234567]'));
        $this->assertTrue($this->subject->match('[getTSFE().testSimpleObject.testSimpleVariable == "testValue"]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'session'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceSession(): void
    {
        $prophecy = $this->prophesize(FrontendUserAuthentication::class);
        $prophecy->getSessionData(Argument::exact('foo'))->willReturn(['bar' => 1234567]);
        $GLOBALS['TSFE']->fe_user = $prophecy->reveal();

        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[globalString = session:foo|bar = 1234567]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[session("foo|bar") == 1234567]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'ENV'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceENV(): void
    {
        $testKey = $this->getUniqueId('test');
        putenv($testKey . '=testValue');
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[globalString = ENV:' . $testKey . ' = testValue]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[getenv("' . $testKey . '") == "testValue"]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'IENV'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceIENV(): void
    {
        $_SERVER['HTTP_HOST'] = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') . ':1234567';
        // getIndpEnv() is polluted after above call, clear cache to have it recalculate for subject execption
        GeneralUtility::flushInternalRuntimeCaches();
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[globalString = IENV:TYPO3_PORT = 1234567]'));
        // Test expression language
        // @TODO: not work yet, looks like test setup issue
//        $this->assertTrue($this->subject->match('[request.getNormalizedParams().getRequestPort() == 1234567]'));
    }

    /**
     * Tests whether the generic fetching of variables works with any global namespace.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithAnyGlobalNamespace(): void
    {
        $GLOBALS[$this->testGlobalNamespace] = [
            'first' => 'testFirst',
            'second' => ['third' => 'testThird']
        ];
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[globalString = ' . $this->testGlobalNamespace . '|first = testFirst]'));
        $this->assertTrue($this->subject->match('[globalString = ' . $this->testGlobalNamespace . '|second|third = testThird]'));
    }

    /**
     * Tests whether any property of a site language matches the request
     *
     * @test
     */
    public function siteLanguageMatchesCondition(): void
    {
        $site = new Site('angelo', 13, [
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
                [
                    'languageId' => 2,
                    'title' => 'UK',
                    'locale' => 'en_UK.UTF-8',
                ]
            ]
        ]);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[siteLanguage = locale = en_US.UTF-8]'));
        $this->assertTrue($this->subject->match('[siteLanguage = locale = de_DE, locale = en_US.UTF-8]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[siteLanguage("locale") == "en_US.UTF-8"]'));
        $this->assertTrue($this->subject->match('[siteLanguage("locale") in ["de_DE", "en_US.UTF-8"]]'));
    }

    /**
     * Tests whether any property of a site language does NOT match the request
     *
     * @test
     */
    public function siteLanguageDoesNotMatchCondition(): void
    {
        $site = new Site('angelo', 13, [
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
                [
                    'languageId' => 2,
                    'title' => 'UK',
                    'locale' => 'en_UK.UTF-8',
                ]
            ]
        ]);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $this->getFreshConditionMatcher();
        $this->assertFalse($this->subject->match('[siteLanguage = locale = en_UK.UTF-8]'));
        $this->assertFalse($this->subject->match('[siteLanguage = locale = de_DE, title = UK]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[siteLanguage("locale") == "en_UK.UTF-8"]'));
        $this->assertFalse($this->subject->match('[siteLanguage("locale") == "de_DE" && siteLanguage("title") == "UK"]'));
    }

    /**
     * Tests whether any property of a site matches the request
     *
     * @test
     */
    public function siteMatchesCondition(): void
    {
        $site = new Site('angelo', 13, ['languages' => [], 'base' => 'https://typo3.org/']);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('site', $site);
        $this->getFreshConditionMatcher();
        $this->assertTrue($this->subject->match('[site = identifier = angelo]'));
        $this->assertTrue($this->subject->match('[site = rootPageId = 13]'));
        $this->assertTrue($this->subject->match('[site = base = https://typo3.org/]'));
        // Test expression language
        $this->assertTrue($this->subject->match('[site("identifier") == "angelo"]'));
        $this->assertTrue($this->subject->match('[site("rootPageId") == 13]'));
        $this->assertTrue($this->subject->match('[site("base") == "https://typo3.org/"]'));
    }

    /**
     * Tests whether any property of a site that does NOT match the request
     *
     * @test
     */
    public function siteDoesNotMatchCondition(): void
    {
        $site = new Site('angelo', 13, [
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'United States',
                    'locale' => 'en_US.UTF-8',
                ],
                [
                    'languageId' => 2,
                    'title' => 'UK',
                    'locale' => 'en_UK.UTF-8',
                ]
            ]
        ]);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('site', $site);
        $this->getFreshConditionMatcher();
        $this->assertFalse($this->subject->match('[site = identifier = berta]'));
        $this->assertFalse($this->subject->match('[site = rootPageId = 14, rootPageId=23]'));
        // Test expression language
        $this->assertFalse($this->subject->match('[site("identifier") == "berta"]'));
        $this->assertFalse($this->subject->match('[site("rootPageId") == 14 && site("rootPageId") == 23]'));
    }

    /**
     * @test
     */
    public function matchThrowsExceptionIfConditionClassDoesNotInheritFromAbstractCondition(): void
    {
        $this->expectException(InvalidTypoScriptConditionException::class);
        $this->expectExceptionCode(1410286153);
        $this->getFreshConditionMatcher();
        $loggerProphecy = $this->prophesize(Logger::class);
        $this->subject->setLogger($loggerProphecy->reveal());
        $this->subject->match('[stdClass = foo]');
    }

    /**
     * @test
     */
    public function matchCallsTestConditionAndHandsOverParameters(): void
    {
        $this->expectException(TestConditionException::class);
        $this->expectExceptionCode(1411581139);
        $this->getFreshConditionMatcher();
        $loggerProphecy = $this->prophesize(Logger::class);
        $this->subject->setLogger($loggerProphecy->reveal());
        $this->subject->match('[TYPO3\\CMS\\Frontend\\Tests\\UnitDeprecated\\Configuration\\TypoScript\\ConditionMatching\\Fixtures\\TestCondition = 7, != 6]');
    }
}
