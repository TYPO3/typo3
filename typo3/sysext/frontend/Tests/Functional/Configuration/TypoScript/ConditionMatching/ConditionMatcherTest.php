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

namespace TYPO3\CMS\Frontend\Tests\Functional\Configuration\TypoScript\ConditionMatching;

use Prophecy\Argument;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for the ConditionMatcher of EXT:frontend
 */
class ConditionMatcherTest extends FunctionalTestCase
{
    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest();

        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->setupFrontendController(3);
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionMatchesSingleGroupId(): void
    {
        $this->setupFrontendUserContext([13]);
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
        $this->setupFrontendUserContext([13, 14, 15]);
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[usergroup(999,15,14,13)]'));
        self::assertTrue($subject->match('[usergroup("999,15,14,13")]'));
        self::assertTrue($subject->match('[usergroup(\'999,15,14,13\')]'));
    }

    /**
     * Tests whether usergroup comparison matches.
     *
     * @test
     */
    public function usergroupConditionDoesNotMatchDefaultUserGroupIds(): void
    {
        $this->setupFrontendUserContext([0, -1]);
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[usergroup("0,-1")]'));
        self::assertFalse($subject->match('[usergroup(\'0,-1\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionMatchesAnyLoggedInUser(): void
    {
        $this->setupFrontendUserContext([13]);
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
        $this->setupFrontendUserContext([13, 14, 15]);
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
    public function loginUserConditionMatchesMultipleLoggedInUsers(): void
    {
        $this->setupFrontendUserContext([13, 14, 15]);
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser("999,13")]'));
        self::assertTrue($subject->match('[loginUser(\'999,13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     *
     * @test
     */
    public function loginUserConditionDoesNotMatchIfNotUserIsLoggedId(): void
    {
        $this->setupFrontendUserContext();
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[loginUser("*")]'));
        self::assertTrue($subject->match('[loginUser("*") == false]'));
        self::assertFalse($subject->match('[loginUser("13")]'));
        self::assertFalse($subject->match('[loginUser(\'*\')]'));
        self::assertFalse($subject->match('[loginUser(\'13\')]'));
    }

    /**
     * Tests whether user is not logged in
     *
     * @test
     */
    public function loginUserConditionMatchIfUserIsNotLoggedIn(): void
    {
        $this->setupFrontendUserContext();
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser(\'*\') == false]'));
        self::assertTrue($subject->match('[loginUser("*") == false]'));
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
        self::assertTrue($this->getConditionMatcher()->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        self::assertTrue($this->getConditionMatcher()->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     *
     * @test
     */
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[tree.level == 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $subject = $this->getConditionMatcher();
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
        self::assertFalse($this->getConditionMatcher()->match('[3 in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     *
     * @test
     */
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[999 in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $this->setupFrontendController(3);
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionMatchesLastPageIdInRootline(): void
    {
        self::assertTrue($this->getConditionMatcher()->match('[3 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     *
     * @test
     */
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[999 in tree.rootLineIds]'));
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
        self::assertTrue($this->getConditionMatcher()->match('[compatVersion(' . TYPO3_branch . ')]'));
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
     * Tests whether the generic fetching of variables works with the namespace 'TSFE'.
     *
     * @test
     */
    public function genericGetVariablesSucceedsWithNamespaceTSFE(): void
    {
        $GLOBALS['TSFE']->id = 1234567;
        $GLOBALS['TSFE']->testSimpleObject = new \stdClass();
        $GLOBALS['TSFE']->testSimpleObject->testSimpleVariable = 'testValue';

        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[getTSFE().id == 1234567]'));
        self::assertTrue($subject->match('[getTSFE().testSimpleObject.testSimpleVariable == "testValue"]'));
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

        self::assertTrue($this->getConditionMatcher()->match('[session("foo|bar") == 1234567]'));
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
        self::assertTrue($this->getConditionMatcher()->match('[getenv("' . $testKey . '") == "testValue"]'));
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
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[siteLanguage("locale") == "en_US.UTF-8"]'));
        self::assertTrue($subject->match('[siteLanguage("locale") in ["de_DE", "en_US.UTF-8"]]'));
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
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[siteLanguage("locale") == "en_UK.UTF-8"]'));
        self::assertFalse($subject->match('[siteLanguage("locale") == "de_DE" && siteLanguage("title") == "UK"]'));
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
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[site("identifier") == "angelo"]'));
        self::assertTrue($subject->match('[site("rootPageId") == 13]'));
        self::assertTrue($subject->match('[site("base") == "https://typo3.org/"]'));
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
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[site("identifier") == "berta"]'));
        self::assertFalse($subject->match('[site("rootPageId") == 14 && site("rootPageId") == 23]'));
    }

    /**
     * @return ConditionMatcher
     */
    protected function getConditionMatcher(): ConditionMatcher
    {
        $conditionMatcher = new ConditionMatcher();
        $conditionMatcher->setLogger($this->prophesize(Logger::class)->reveal());

        return $conditionMatcher;
    }

    /**
     * @param array $groups
     */
    protected function setupFrontendUserContext(array $groups = []): void
    {
        $frontendUser = $GLOBALS['TSFE']->fe_user;
        $frontendUser->user['uid'] = 13;
        $frontendUser->groupData['uid'] = $groups;

        GeneralUtility::makeInstance(Context::class)->setAspect('frontend.user', new UserAspect($frontendUser, $groups));
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

    /**
     * @param int $pageId
     */
    protected function setupFrontendController(int $pageId): void
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
        $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getLanguageById(0),
            new PageArguments($pageId, '0', []),
            new FrontendUserAuthentication()
        );
        $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $GLOBALS['TSFE']->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $GLOBALS['TSFE']->tmpl->rootLine = [
            0 => ['uid' => 1, 'pid' => 0],
            1 => ['uid' => 2, 'pid' => 1],
            2 => ['uid' => 3, 'pid' => 2],
        ];
    }
}
