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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheFrontendProphecy->has(Argument::any())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::any(), Argument::any())->willReturn(null);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheManagerProphecy->getCache('cache_core')->willReturn($cacheFrontendProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $corePackageProphecy = $this->prophesize(PackageInterface::class);
        $corePackageProphecy->getPackagePath()->willReturn(__DIR__ . '/../../../../../../../sysext/core/');
        $packageManagerProphecy->getActivePackages()->willReturn([
            $corePackageProphecy->reveal()
        ]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManagerProphecy->reveal());

        $this->testGlobalNamespace = $this->getUniqueId('TEST');
        $GLOBALS[$this->testGlobalNamespace] = [];
        $GLOBALS['TSFE'] = $this->prophesize(TypoScriptFrontendController::class)->reveal();
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
        $this->assertTrue($subject->match('[loginUser(\'*\') == false]'));
        $this->assertTrue($subject->match('[loginUser("*") == false]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesSingleValue(): void
    {
        $this->assertTrue($this->subject->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        $this->assertTrue($this->subject->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        $this->assertFalse($this->subject->match('[tree.level == 999]'));
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
        $this->assertTrue($this->subject->match('[111 in tree.rootLineIds]'));
        $this->assertTrue($this->subject->match('["111" in tree.rootLineIds]'));
        $this->assertTrue($this->subject->match('[\'111\' in tree.rootLineIds]'));
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
        $this->assertFalse($this->subject->match('[999 in tree.rootLineIds]'));
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
        $this->assertTrue($this->subject->match('[111 in tree.rootLineIds]'));
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
        $this->assertFalse($this->subject->match('[compatVersion(15.0)]'));
        $this->assertFalse($this->subject->match('[compatVersion("15.0")]'));
        $this->assertFalse($this->subject->match('[compatVersion(\'15.0\')]'));
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
        $this->assertTrue($this->subject->match('[getenv("' . $testKey . '") == "testValue"]'));
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
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $this->getFreshConditionMatcher();
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
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $this->getFreshConditionMatcher();
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
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('site', $site);
        $this->getFreshConditionMatcher();
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
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('site', $site);
        $this->getFreshConditionMatcher();
        $this->assertFalse($this->subject->match('[site("identifier") == "berta"]'));
        $this->assertFalse($this->subject->match('[site("rootPageId") == 14 && site("rootPageId") == 23]'));
    }
}
