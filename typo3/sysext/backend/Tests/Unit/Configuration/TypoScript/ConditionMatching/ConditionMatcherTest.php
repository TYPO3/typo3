<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Configuration\TypoScript\ConditionMatching;

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
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher.
 */
class ConditionMatcherTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $rootline;

    /**
     * @var \TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher
     */
    protected $matchCondition;

    /**
     * @var string
     */
    protected $testGlobalNamespace;

    /**
     * @var string
     */
    protected $testTableName;

    /**
     * @var bool Reset singletons
     */
    protected $resetSingletonInstances = true;

    /**
     * Set up tests
     */
    protected function setUp()
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

        $this->testTableName = 'conditionMatcherTestTable';
        $this->testGlobalNamespace = $this->getUniqueId('TEST');
        $GLOBALS['TCA'][$this->testTableName] = ['ctrl' => []];
        $GLOBALS[$this->testGlobalNamespace] = [];
        $this->setUpBackend();
        $this->matchCondition = $this->getAccessibleMock(ConditionMatcher::class, ['determineRootline'], [], '', false);
        $this->matchCondition->method('determineRootline')->willReturn([
            2 => ['uid' => 121, 'pid' => 111],
            1 => ['uid' => 111, 'pid' => 101],
            0 => ['uid' => 101, 'pid' => 0]
        ]);
        $this->matchCondition->__construct();
        $loggerProphecy = $this->prophesize(Logger::class);
        $this->matchCondition->setLogger($loggerProphecy->reveal());
    }

    /**
     * Set up a backend
     */
    private function setUpBackend()
    {
        $this->rootline = [
            2 => ['uid' => 121, 'pid' => 111],
            1 => ['uid' => 111, 'pid' => 101],
            0 => ['uid' => 101, 'pid' => 0]
        ];
        $GLOBALS['BE_USER'] = $this->getMockBuilder(BackendUserAuthentication::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['BE_USER']->groupList = '13,14,15';
        $GLOBALS['BE_USER']->user['uid'] = 13;
        $GLOBALS['BE_USER']->user['admin'] = 1;

        GeneralUtility::makeInstance(Context::class)
            ->setAspect('backend.user', new UserAspect($GLOBALS['BE_USER']));
    }

    /**
     * Set up database mock
     */
    private function setUpDatabaseMockForDeterminePageId()
    {
        $this->matchCondition = $this->getAccessibleMock(ConditionMatcher::class, ['determineRootline', 'determinePageId'], [], '', false);
        $this->matchCondition->method('determineRootline')->willReturn([
            2 => ['uid' => 121, 'pid' => 111],
            1 => ['uid' => 111, 'pid' => 101],
            0 => ['uid' => 101, 'pid' => 0]
        ]);
        $this->matchCondition->__construct();
        $loggerProphecy = $this->prophesize(Logger::class);
        $this->matchCondition->setLogger($loggerProphecy->reveal());

        $this->matchCondition->expects($this->once())->method('determinePageId')->willReturn(999);
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId()
    {
        $this->assertTrue($this->matchCondition->match('[usergroup(13)]'));
        $this->assertTrue($this->matchCondition->match('[usergroup("13")]'));
        $this->assertTrue($this->matchCondition->match('[usergroup(\'13\')]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesMultipleUserGroupId()
    {
        $this->assertTrue($this->matchCondition->match('[usergroup("999,15,14,13")]'));
        $this->assertTrue($this->matchCondition->match('[usergroup(\'999,15,14,13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser()
    {
        $this->assertTrue($this->matchCondition->match('[loginUser("*")]'));
        $this->assertTrue($this->matchCondition->match('[loginUser(\'*\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesSingleLoggedInUser()
    {
        $this->assertTrue($this->matchCondition->match('[loginUser(13)]'));
        $this->assertTrue($this->matchCondition->match('[loginUser("13")]'));
        $this->assertTrue($this->matchCondition->match('[loginUser(\'13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionDoesNotMatchSingleLoggedInUser()
    {
        $GLOBALS['BE_USER']->user['uid'] = 13;
        $this->assertFalse($this->matchCondition->match('[loginUser(999)]'));
        $this->assertFalse($this->matchCondition->match('[loginUser("999")]'));
        $this->assertFalse($this->matchCondition->match('[loginUser(\'999\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesMultipleLoggedInUsers()
    {
        $this->assertTrue($this->matchCondition->match('[loginUser("999,13")]'));
        $this->assertTrue($this->matchCondition->match('[loginUser(\'999,13\')]'));
    }

    /**
     * Tests whether checkinf for an admin user matches
     *
     * @test
     */
    public function adminUserConditionMatchesAdminUser()
    {
        $this->assertTrue($this->matchCondition->match('[backend.user.isAdmin == true]'));
        $this->assertTrue($this->matchCondition->match('[backend.user.isAdmin != false]'));
        $this->assertTrue($this->matchCondition->match('[backend.user.isAdmin]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesSingleValue()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->assertTrue($this->matchCondition->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->assertTrue($this->matchCondition->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->assertFalse($this->matchCondition->match('[tree.level == 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->matchCondition->setPageId(121);
        $this->assertTrue($this->matchCondition->match('[111 in tree.rootLineIds]'));
        $this->assertTrue($this->matchCondition->match('["111" in tree.rootLineIds]'));
        $this->assertTrue($this->matchCondition->match('[\'111\' in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->matchCondition->setPageId(121);
        $this->assertFalse($this->matchCondition->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->matchCondition->setPageId(121);
        $this->assertTrue($this->matchCondition->match('[111 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->matchCondition->setPageId(121);
        $this->assertTrue($this->matchCondition->match('[121 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline()
    {
        $this->matchCondition->setRootline($this->rootline);
        $this->matchCondition->setPageId(121);
        $this->assertFalse($this->matchCondition->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesOlderRelease()
    {
        $this->assertTrue($this->matchCondition->match('[compatVersion(7.0)]'));
        $this->assertTrue($this->matchCondition->match('[compatVersion("7.0")]'));
        $this->assertTrue($this->matchCondition->match('[compatVersion(\'7.0\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesSameRelease()
    {
        $this->assertTrue($this->matchCondition->match('[compatVersion(' . TYPO3_branch . ')]'));
        $this->assertTrue($this->matchCondition->match('[compatVersion("' . TYPO3_branch . '")]'));
        $this->assertTrue($this->matchCondition->match('[compatVersion(\'' . TYPO3_branch . '\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionDoesNotMatchNewerRelease()
    {
        $this->assertFalse($this->matchCondition->match('[compatVersion(15.0)]'));
        $this->assertFalse($this->matchCondition->match('[compatVersion("15.0")]'));
        $this->assertFalse($this->matchCondition->match('[compatVersion(\'15.0\')]'));
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
        $this->assertTrue($this->matchCondition->match('[getenv("' . $testKey . '") == "testValue"]'));
    }

    /**
     * @test
     */
    public function usingTSFEInATestInBeContextIsAlwaysFalse(): void
    {
        $this->assertFalse($this->matchCondition->match('[getTSFE().id == 1]'));
    }
}
