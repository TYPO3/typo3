<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Tests\Functional\Configuration\TypoScript\ConditionMatching;

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

use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the ConditionMatcher of EXT:backend
 */
class ConditionMatcherTest extends FunctionalTestCase
{
    /**
     * @var ConditionMatcher|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

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

        $this->subject = new ConditionMatcher();
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId(): void
    {
        self::assertTrue($this->subject->match('[usergroup(13)]'));
        self::assertTrue($this->subject->match('[usergroup("13")]'));
        self::assertTrue($this->subject->match('[usergroup(\'13\')]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesMultipleUserGroupId(): void
    {
        self::assertTrue($this->subject->match('[usergroup("999,15,14,13")]'));
        self::assertTrue($this->subject->match('[usergroup(\'999,15,14,13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser(): void
    {
        self::assertTrue($this->subject->match('[loginUser("*")]'));
        self::assertTrue($this->subject->match('[loginUser(\'*\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesSingleLoggedInUser(): void
    {
        self::assertTrue($this->subject->match('[loginUser(13)]'));
        self::assertTrue($this->subject->match('[loginUser("13")]'));
        self::assertTrue($this->subject->match('[loginUser(\'13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionDoesNotMatchSingleLoggedInUser(): void
    {
        $GLOBALS['BE_USER']->user['uid'] = 13;
        self::assertFalse($this->subject->match('[loginUser(999)]'));
        self::assertFalse($this->subject->match('[loginUser("999")]'));
        self::assertFalse($this->subject->match('[loginUser(\'999\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesMultipleLoggedInUsers(): void
    {
        self::assertTrue($this->subject->match('[loginUser("999,13")]'));
        self::assertTrue($this->subject->match('[loginUser(\'999,13\')]'));
    }

    /**
     * Tests whether checking for an admin user matches
     *
     * @test
     */
    public function adminUserConditionMatchesAdminUser(): void
    {
        self::assertTrue($this->subject->match('[backend.user.isAdmin == true]'));
        self::assertTrue($this->subject->match('[backend.user.isAdmin != false]'));
        self::assertTrue($this->subject->match('[backend.user.isAdmin]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesSingleValue(): void
    {
        $this->subject->setPageId(2);
        $this->subject->__construct();
        self::assertTrue($this->subject->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        $this->subject->setPageId(2);
        $this->subject->__construct();
        self::assertTrue($this->subject->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        self::assertFalse($this->subject->match('[tree.level == 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $this->subject->setPageId(3);
        $this->subject->__construct();
        self::assertTrue($this->subject->match('[2 in tree.rootLineIds]'));
        self::assertTrue($this->subject->match('["2" in tree.rootLineIds]'));
        self::assertTrue($this->subject->match('[\'2\' in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $this->subject->setPageId(3);
        self::assertFalse($this->subject->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $this->subject->setPageId(3);
        $this->subject->__construct();
        self::assertTrue($this->subject->match('[2 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline(): void
    {
        $this->subject->setPageId(3);
        $this->subject->__construct();
        self::assertTrue($this->subject->match('[3 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        $this->subject->setPageId(3);
        self::assertFalse($this->subject->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesOlderRelease(): void
    {
        self::assertTrue($this->subject->match('[compatVersion(7.0)]'));
        self::assertTrue($this->subject->match('[compatVersion("7.0")]'));
        self::assertTrue($this->subject->match('[compatVersion(\'7.0\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionMatchesSameRelease(): void
    {
        self::assertTrue($this->subject->match('[compatVersion(' . TYPO3_branch . ')]'));
        self::assertTrue($this->subject->match('[compatVersion("' . TYPO3_branch . '")]'));
        self::assertTrue($this->subject->match('[compatVersion(\'' . TYPO3_branch . '\')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     *
     * @test
     */
    public function compatVersionConditionDoesNotMatchNewerRelease(): void
    {
        self::assertFalse($this->subject->match('[compatVersion(15.0)]'));
        self::assertFalse($this->subject->match('[compatVersion("15.0")]'));
        self::assertFalse($this->subject->match('[compatVersion(\'15.0\')]'));
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
        self::assertTrue($this->subject->match('[getenv("' . $testKey . '") == "testValue"]'));
    }
}
