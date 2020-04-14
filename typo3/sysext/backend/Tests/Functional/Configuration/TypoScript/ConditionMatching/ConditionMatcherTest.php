<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Tests\Functional\Configuration\TypoScript\ConditionMatching;

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the ConditionMatcher of EXT:backend
 */
class ConditionMatcherTest extends FunctionalTestCase
{
    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');

        $backendUser = new BackendUserAuthentication();
        $backendUser->user['uid'] = 13;
        $backendUser->user['admin'] = true;
        $backendUser->groupList = '13,14,15';
        GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($backendUser));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[usergroup(13)]'));
        self::assertTrue($subject->match('[usergroup("13")]'));
        self::assertTrue($subject->match('[usergroup(\'13\')]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesMultipleUserGroupId(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[usergroup("999,15,14,13")]'));
        self::assertTrue($subject->match('[usergroup(\'999,15,14,13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser("*")]'));
        self::assertTrue($subject->match('[loginUser(\'*\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesSingleLoggedInUser(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser(13)]'));
        self::assertTrue($subject->match('[loginUser("13")]'));
        self::assertTrue($subject->match('[loginUser(\'13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionDoesNotMatchSingleLoggedInUser(): void
    {
        $GLOBALS['BE_USER']->user['uid'] = 13;
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[loginUser(999)]'));
        self::assertFalse($subject->match('[loginUser("999")]'));
        self::assertFalse($subject->match('[loginUser(\'999\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesMultipleLoggedInUsers(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser("999,13")]'));
        self::assertTrue($subject->match('[loginUser(\'999,13\')]'));
    }

    /**
     * Tests whether checking for an admin user matches
     *
     * @test
     */
    public function adminUserConditionMatchesAdminUser(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[backend.user.isAdmin == true]'));
        self::assertTrue($subject->match('[backend.user.isAdmin != false]'));
        self::assertTrue($subject->match('[backend.user.isAdmin]'));
    }

    /**
     * Tests whether checking for workspace id matches current workspace id
     *
     * @test
     */
    public function workspaceIdConditionMatchesCurrentWorkspaceId(): void
    {
        $this->setUpWorkspaceAspect(0);
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[workspace.workspaceId === 0]'));
        self::assertTrue($subject->match('[workspace.workspaceId == 0]'));
        self::assertTrue($subject->match('[workspace.workspaceId == "0"]'));
        self::assertTrue($subject->match('[workspace.workspaceId == \'0\']'));
    }

    /**
     * Tests whether checking if workspace is live matches
     *
     * @test
     */
    public function workspaceIsLiveMatchesCorrectWorkspaceState(): void
    {
        $this->setUpWorkspaceAspect(1);
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[workspace.isLive]'));
        self::assertFalse($subject->match('[workspace.isLive === true]'));
        self::assertFalse($subject->match('[workspace.isLive == true]'));
        self::assertFalse($subject->match('[workspace.isLive !== false]'));
        self::assertFalse($subject->match('[workspace.isLive != false]'));
    }

    /**
     * Tests whether checking if workspace is offline matches
     *
     * @test
     */
    public function workspaceIsOfflineMatchesCorrectWorkspaceState(): void
    {
        $this->setUpWorkspaceAspect(1);
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[workspace.isOffline]'));
        self::assertTrue($subject->match('[workspace.isOffline === true]'));
        self::assertTrue($subject->match('[workspace.isOffline == true]'));
        self::assertTrue($subject->match('[workspace.isOffline !== false]'));
        self::assertTrue($subject->match('[workspace.isOffline != false]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesSingleValue(): void
    {
        $subject = $this->getConditionMatcher(2);
        self::assertTrue($subject->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        $subject = $this->getConditionMatcher(2);
        self::assertTrue($subject->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[tree.level == 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $subject = $this->getConditionMatcher(3);
        self::assertTrue($subject->match('[2 in tree.rootLineParentIds]'));
        self::assertTrue($subject->match('["2" in tree.rootLineParentIds]'));
        self::assertTrue($subject->match('[\'2\' in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page id is not found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchLastPageIdInRootline(): void
    {
        $subject = $this->getConditionMatcher(3);
        self::assertFalse($subject->match('[3 in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $subject = $this->getConditionMatcher(3);
        self::assertFalse($subject->match('[999 in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $subject = $this->getConditionMatcher(3);
        self::assertTrue($subject->match('[2 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline(): void
    {
        $subject = $this->getConditionMatcher(3);
        self::assertTrue($subject->match('[3 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $subject = $this->getConditionMatcher(3);
        self::assertFalse($subject->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesOlderRelease(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[compatVersion(7.0)]'));
        self::assertTrue($subject->match('[compatVersion("7.0")]'));
        self::assertTrue($subject->match('[compatVersion(\'7.0\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesSameRelease(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[compatVersion(' . TYPO3_branch . ')]'));
        self::assertTrue($subject->match('[compatVersion("' . TYPO3_branch . '")]'));
        self::assertTrue($subject->match('[compatVersion(\'' . TYPO3_branch . '\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionDoesNotMatchNewerRelease(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[compatVersion(15.0)]'));
        self::assertFalse($subject->match('[compatVersion("15.0")]'));
        self::assertFalse($subject->match('[compatVersion(\'15.0\')]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'ENV'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceENV(): void
    {
        $testKey = StringUtility::getUniqueId('test');
        putenv($testKey . '=testValue');
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[getenv("' . $testKey . '") == "testValue"]'));
    }

    /**
     * @test
     */
    public function usingTSFEInATestInBeContextIsAlwaysFalse(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[getTSFE().id == 1]'));
    }

    /**
     * @param int|null $pageId
     * @return ConditionMatcher
     */
    protected function getConditionMatcher(int $pageId = null): ConditionMatcher
    {
        $conditionMatcher = new ConditionMatcher(null, $pageId);
        $conditionMatcher->setLogger($this->prophesize(Logger::class)->reveal());

        return $conditionMatcher;
    }

    /**
     * Set up workspace aspect.
     *
     * @param int $workspaceId
     */
    protected function setUpWorkspaceAspect(int $workspaceId): void
    {
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }
}
