<?php
declare(strict_types = 1);
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

use Prophecy\Argument;
use TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Tests\Unit\Configuration\TypoScript\ConditionMatching\Fixtures\TestConditionException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ConditionMatcherTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher Class under test
     */
    protected $matchCondition;

    protected function setUp(): void
    {
        $this->testGlobalNamespace = $this->getUniqueId('TEST');
        GeneralUtility::flushInternalRuntimeCaches();
        $GLOBALS[$this->testGlobalNamespace] = [];
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $GLOBALS['TSFE']->tmpl->rootLine = [
            2 => ['uid' => 121, 'pid' => 111],
            1 => ['uid' => 111, 'pid' => 101],
            0 => ['uid' => 101, 'pid' => 0]
        ];
        $this->matchCondition = GeneralUtility::makeInstance(ConditionMatcher::class);
    }

    /**
     * Tests whether a faulty expression fails.
     *
     * @test
     */
    public function simulateDisabledMatchAllConditionsFailsOnFaultyExpression(): void
    {
        $this->matchCondition->matchAll = false;
        $this->assertFalse($this->matchCondition->match('[nullCondition = This expression would return FALSE in general]'));
    }

    /**
     * Tests whether simulating positive matches for all conditions succeeds.
     *
     * @test
     */
    public function simulateEnabledMatchAllConditionsSucceeds(): void
    {
        $this->matchCondition->setSimulateMatchResult(true);
        $this->assertTrue($this->matchCondition->match('[nullCondition = This expression would return FALSE in general]'));
    }

    /**
     * Tests whether simulating positive matches for specific conditions succeeds.
     *
     * @test
     */
    public function simulateEnabledMatchSpecificConditionsSucceeds(): void
    {
        $testCondition = '[' . $this->getUniqueId('test') . ' = Any condition to simulate a positive match]';
        $this->matchCondition->setSimulateMatchConditions([$testCondition]);
        $this->assertTrue($this->matchCondition->match($testCondition));
    }

    /**
     * Tests whether the language comparison matches.
     *
     * @test
     */
    public function languageConditionMatchesSingleLanguageExpression(): void
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
    public function languageConditionMatchesMultipleLanguagesExpression(): void
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
    public function languageConditionMatchesCompleteLanguagesExpression(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3';
        $this->assertTrue($this->matchCondition->match('[language = de-de,de;q=0.8,en-us;q=0.5,en;q=0.3]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId(): void
    {
        $GLOBALS['TSFE']->gr_list = '13,14,15';
        $this->assertTrue($this->matchCondition->match('[usergroup = 13]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesMultipleUserGroupId(): void
    {
        $GLOBALS['TSFE']->gr_list = '13,14,15';
        $this->assertTrue($this->matchCondition->match('[usergroup = 999,15,14,13]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionDoesNotMatchDefaulUserGroupIds(): void
    {
        $GLOBALS['TSFE']->gr_list = '0,-1';
        $this->assertFalse($this->matchCondition->match('[usergroup = 0,-1]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser(): void
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
    public function loginUserConditionMatchesSingleLoggedInUser(): void
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
    public function loginUserConditionMatchesMultipleLoggedInUsers(): void
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
    public function loginUserConditionDoesNotMatchIfNotUserIsLoggedId(): void
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
    public function loginUserConditionMatchIfUserIsNotLoggedIn(): void
    {
        $GLOBALS['TSFE']->loginUser = false;
        $this->assertTrue($this->matchCondition->match('[loginUser = ]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnEqualExpression(): void
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
    public function globalVarConditionMatchesOnEqualExpressionWithMultipleValues(): void
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
    public function globalVarConditionMatchesOnNotEqualExpression(): void
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2]'));
    }

    /**
     * Tests whether numerical comparison does not match.
     *
     * @test
     */
    public function globalVarConditionDoesNotMatchOnNotEqualExpression(): void
    {
        $this->assertFalse($this->matchCondition->match('[globalVar = LIT:10 != 10]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnNotEqualExpressionWithMultipleValues(): void
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 != 20|30]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 != 10.2|20.3]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnLowerThanExpression(): void
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10 < 20]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.1 < 10.2]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnLowerThanOrEqualExpression(): void
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
    public function globalVarConditionMatchesOnGreaterThanExpression(): void
    {
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:20 > 10]'));
        $this->assertTrue($this->matchCondition->match('[globalVar = LIT:10.2 > 10.1]'));
    }

    /**
     * Tests whether numerical comparison matches.
     *
     * @test
     */
    public function globalVarConditionMatchesOnGreaterThanOrEqualExpression(): void
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
    public function globalVarConditionMatchesOnEmptyExpressionWithNoValueSet(): void
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
    public function globalVarConditionDoesNotMatchOnEmptyExpressionWithValueSetToZero(): void
    {
        $testKey = $this->getUniqueId('test');
        $_GET = [];
        $_POST = [$testKey => 0];
        $this->assertFalse($this->matchCondition->match('[globalVar = GP:' . $testKey . '=]'));
        $this->assertFalse($this->matchCondition->match('[globalVar = GP:' . $testKey . ' = ]'));
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
        $this->assertTrue($this->matchCondition->match('[globalVar = GP:' . $testKey . '|0=' . $testValue . ']'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEqualExpression(): void
    {
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3.Test.Condition]'));
        $this->assertFalse($this->matchCondition->match('[globalString = LIT:TYPO3.Test.Condition = TYPO3]'));
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
        $this->assertTrue($this->matchCondition->match('[globalString = GP:' . $testKey . '=]'));
        $this->assertTrue($this->matchCondition->match('[globalString = GP:' . $testKey . ' = ]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesOnEmptyLiteralExpressionWithValueSetToEmptyString(): void
    {
        $this->assertTrue($this->matchCondition->match('[globalString = LIT:=]'));
        $this->assertTrue($this->matchCondition->match('[globalString = LIT: = ]'));
    }

    /**
     * Tests whether string comparison matches.
     *
     * @test
     */
    public function globalStringConditionMatchesWildcardExpression(): void
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
    public function globalStringConditionMatchesRegularExpression(): void
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
    public function globalStringConditionMatchesEmptyRegularExpression(): void
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
    public function treeLevelConditionMatchesSingleValue(): void
    {
        $this->assertTrue($this->matchCondition->match('[treeLevel = 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        $this->assertTrue($this->matchCondition->match('[treeLevel = 999,998,2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        $this->assertFalse($this->matchCondition->match('[treeLevel = 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDupinRootline = 111]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesMultiplePageIdsInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDupinRootline = 999,111,101]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertFalse($this->matchCondition->match('[PIDupinRootline = 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchLastPageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertFalse($this->matchCondition->match('[PIDupinRootline = 121]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDinRootline = 111]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesMultiplePageIdsInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDinRootline = 999,111,101]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline(): void
    {
        $GLOBALS['TSFE']->id = 121;
        $this->assertTrue($this->matchCondition->match('[PIDinRootline = 121]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
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
    public function compatVersionConditionMatchesOlderRelease(): void
    {
        $this->assertTrue($this->matchCondition->match('[compatVersion = 7.0]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesSameRelease(): void
    {
        $this->assertTrue($this->matchCondition->match('[compatVersion = ' . TYPO3_branch . ']'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionDoesNotMatchNewerRelease(): void
    {
        $this->assertFalse($this->matchCondition->match('[compatVersion = 15.0]'));
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
        $this->assertTrue($this->matchCondition->match('[globalString = GP:testGet = getTest]'));
        $this->assertTrue($this->matchCondition->match('[globalString = GP:testPost = postTest]'));
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

        $this->assertTrue($this->matchCondition->match('[globalString = TSFE:id = 1234567]'));
        $this->assertTrue($this->matchCondition->match('[globalString = TSFE:testSimpleObject|testSimpleVariable = testValue]'));
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

        $this->assertTrue($this->matchCondition->match('[globalString = session:foo|bar = 1234567]'));
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
        $this->assertTrue($this->matchCondition->match('[globalString = ENV:' . $testKey . ' = testValue]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'IENV'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceIENV(): void
    {
        $_SERVER['HTTP_HOST'] = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') . ':1234567';
        $this->assertTrue($this->matchCondition->match('[globalString = IENV:TYPO3_PORT = 1234567]'));
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
        $this->assertTrue($this->matchCondition->match('[globalString = ' . $this->testGlobalNamespace . '|first = testFirst]'));
        $this->assertTrue($this->matchCondition->match('[globalString = ' . $this->testGlobalNamespace . '|second|third = testThird]'));
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
        $this->assertTrue($this->matchCondition->match('[siteLanguage = locale = en_US.UTF-8]'));
        $this->assertTrue($this->matchCondition->match('[siteLanguage = locale = de_DE, locale = en_US.UTF-8]'));
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
        $this->assertFalse($this->matchCondition->match('[siteLanguage = locale = en_UK.UTF-8]'));
        $this->assertFalse($this->matchCondition->match('[siteLanguage = locale = de_DE, title = UK]'));
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
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $this->assertTrue($this->matchCondition->match('[site = identifier = angelo]'));
        $this->assertTrue($this->matchCondition->match('[site = rootPageId = 13]'));
        $this->assertTrue($this->matchCondition->match('[site = base = https://typo3.org/]'));
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
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $this->assertFalse($this->matchCondition->match('[site = identifier = berta]'));
        $this->assertFalse($this->matchCondition->match('[site = rootPageId = 14, rootPageId=23]'));
    }

    /**
     * @test
     */
    public function matchThrowsExceptionIfConditionClassDoesNotInheritFromAbstractCondition(): void
    {
        $this->expectException(InvalidTypoScriptConditionException::class);
        $this->expectExceptionCode(1410286153);
        $this->matchCondition->match('[stdClass = foo]');
    }

    /**
     * @test
     */
    public function matchCallsTestConditionAndHandsOverParameters(): void
    {
        $this->expectException(TestConditionException::class);
        $this->expectExceptionCode(1411581139);
        $this->matchCondition->match('[TYPO3\\CMS\\Frontend\\Tests\\Unit\\Configuration\\TypoScript\\ConditionMatching\\Fixtures\\TestCondition = 7, != 6]');
    }
}
