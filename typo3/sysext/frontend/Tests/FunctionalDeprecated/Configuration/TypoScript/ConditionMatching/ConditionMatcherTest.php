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

namespace TYPO3\CMS\Frontend\Tests\FunctionalDeprecated\Configuration\TypoScript\ConditionMatching;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\ExpressionLanguage\Node\BinaryNode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConditionMatcherTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/pages.csv');
        $this->setupFrontendController(3);
    }

    /**
     * Tests whether usergroup comparison matches.
     */
    #[Test]
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
     */
    #[Test]
    public function usergroupConditionMatchesMultipleUserGroupId(): void
    {
        $this->setupFrontendUserContext([13, 14, 15]);
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[usergroup(999,15,14,13)]'));
        self::assertTrue($subject->match('[usergroup("999,15,14,13")]'));
        self::assertTrue($subject->match('[usergroup(\'999,15,14,13\')]'));
    }

    /**
     * Tests whether usergroup comparison does not match.
     */
    #[Test]
    public function usergroupConditionDoesNotMatchDefaultUserGroupIds(): void
    {
        $this->setupFrontendUserContext([0, -1]);
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[usergroup("0,-1")]'));
        self::assertFalse($subject->match('[usergroup(\'0,-1\')]'));
    }

    /**
     * Tests whether checking for a user group user matches
     */
    #[Test]
    public function frontendUserGroupInOperatorConditionMatchesGroupId(): void
    {
        $this->setupFrontendUserContext([13, 14, 15]);
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[14 in frontend.user.userGroupIds]'));
    }

    /**
     * Tests whether checking for a user group user matches
     */
    #[Test]
    public function backendUserGroupInOperatorConditionMatchesGroupId(): void
    {
        $backendUser = new BackendUserAuthentication();
        $backendUser->userGroupsUID = [13, 14, 15];
        GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($backendUser));

        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[14 in backend.user.userGroupIds]'));
    }

    /**
     * Tests whether user comparison matches.
     */
    #[Test]
    public function loginUserConditionMatchesAnyLoggedInUser(): void
    {
        $this->setupFrontendUserContext([13]);
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser("*")]'));
        self::assertTrue($subject->match('[loginUser(\'*\')]'));
    }

    /**
     * Tests whether user comparison matches.
     */
    #[Test]
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
     */
    #[Test]
    public function loginUserConditionMatchesMultipleLoggedInUsers(): void
    {
        $this->setupFrontendUserContext([13, 14, 15]);
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser("999,13")]'));
        self::assertTrue($subject->match('[loginUser(\'999,13\')]'));
    }

    /**
     * Tests whether user comparison matches.
     */
    #[Test]
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
     */
    #[Test]
    public function loginUserConditionMatchIfUserIsNotLoggedIn(): void
    {
        $this->setupFrontendUserContext();
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[loginUser(\'*\') == false]'));
        self::assertTrue($subject->match('[loginUser("*") == false]'));
    }

    /**
     * Tests whether checking for workspace id matches current workspace id
     */
    #[Test]
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
     */
    #[Test]
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
     */
    #[Test]
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
     */
    #[Test]
    public function treeLevelConditionMatchesSingleValue(): void
    {
        self::assertTrue($this->getConditionMatcher()->match('[tree.level == 2]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     */
    #[Test]
    public function treeLevelConditionMatchesMultipleValues(): void
    {
        self::assertTrue($this->getConditionMatcher()->match('[tree.level in [999,998,2]]'));
    }

    /**
     * Tests whether treeLevel comparison matches.
     */
    #[Test]
    public function treeLevelConditionDoesNotMatchFaultyValue(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[tree.level == 999]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     */
    #[Test]
    public function PIDupinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $subject = $this->getConditionMatcher();

        if (!$this->isSymfonySeven()) {
            // Before Symfony 7+ everything was chaos.
            self::assertTrue($subject->match('[2 in tree.rootLineParentIds]'));
            self::assertTrue($subject->match('["2" in tree.rootLineParentIds]'));
            self::assertTrue($subject->match('[\'2\' in tree.rootLineParentIds]'));
            return;
        }

        // Symfony 7 removed deprecated code paths and therefore started using strict `in_array()` check for the
        // `in` and `notIn` condition in the expression language. Basically this is a breaking change in regard
        // for TYPO3 users, but as the use of symfony 7 is the choice for project it's reasonable to live with it.
        self::assertTrue($subject->match('[2 in tree.rootLineParentIds]'));
        self::assertFalse($subject->match('["2" in tree.rootLineParentIds]'));
        self::assertFalse($subject->match('[\'2\' in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page id is not found in the previous rootline entries.
     */
    #[Test]
    public function PIDupinRootlineConditionDoesNotMatchLastPageIdInRootline(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[3 in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page Id is found in the previous rootline entries.
     */
    #[Test]
    public function PIDupinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[999 in tree.rootLineParentIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     */
    #[Test]
    public function PIDinRootlineConditionMatchesSinglePageIdInRootline(): void
    {
        $this->setupFrontendController(3);
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     */
    #[Test]
    public function PIDinRootlineConditionMatchesLastPageIdInRootline(): void
    {
        self::assertTrue($this->getConditionMatcher()->match('[3 in tree.rootLineIds]'));
    }

    /**
     * Tests whether a page Id is found in all rootline entries.
     */
    #[Test]
    public function PIDinRootlineConditionDoesNotMatchPageIdNotInRootline(): void
    {
        self::assertFalse($this->getConditionMatcher()->match('[999 in tree.rootLineIds]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     */
    #[Test]
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
     */
    #[Test]
    public function compatVersionConditionMatchesSameRelease(): void
    {
        $typo3Version = new Typo3Version();
        self::assertTrue($this->getConditionMatcher()->match('[compatVersion(' . $typo3Version->getBranch() . ')]'));
    }

    /**
     * Tests whether the compatibility version can be evaluated.
     * (e.g. 7.9 is compatible to 7.0 but not to 15.0)
     */
    #[Test]
    public function compatVersionConditionDoesNotMatchNewerRelease(): void
    {
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[compatVersion(15.0)]'));
        self::assertFalse($subject->match('[compatVersion("15.0")]'));
        self::assertFalse($subject->match('[compatVersion(\'15.0\')]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'TSFE'.
     */
    #[Test]
    public function genericGetVariablesSucceedsWithNamespaceTSFE(): void
    {
        $GLOBALS['TSFE']->id = 1234567;

        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[getTSFE().id == 1234567]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'session'.
     */
    #[Test]
    public function genericGetVariablesSucceedsWithNamespaceSession(): void
    {
        $frontendUserAuthenticationMock = $this->createMock(FrontendUserAuthentication::class);
        $frontendUserAuthenticationMock->method('getSessionData')->with('foo')->willReturn(['bar' => 1234567]);
        $GLOBALS['TSFE']->fe_user = $frontendUserAuthenticationMock;

        self::assertTrue($this->getConditionMatcher()->match('[session("foo|bar") == 1234567]'));
    }

    /**
     * Tests whether the generic fetching of variables works with the namespace 'ENV'.
     */
    #[Test]
    public function genericGetVariablesSucceedsWithNamespaceENV(): void
    {
        $testKey = StringUtility::getUniqueId('test');
        putenv($testKey . '=testValue');
        self::assertTrue($this->getConditionMatcher()->match('[getenv("' . $testKey . '") == "testValue"]'));
    }

    /**
     * Tests whether any property of a site language matches the request
     */
    #[Test]
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
                    'locale' => 'en-UK',
                ],
            ],
        ]);
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $subject = $this->getConditionMatcher();
        self::assertTrue($subject->match('[siteLanguage("locale") == "en-US"]'));
        self::assertTrue($subject->match('[siteLanguage("locale").posixFormatted() == "en_US.UTF-8"]'));
        self::assertTrue($subject->match('[siteLanguage("locale").posixFormatted() in ["de_DE", "en_US.UTF-8"]]'));
    }

    /**
     * Tests whether any property of a site language does NOT match the request
     */
    #[Test]
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
                ],
            ],
        ]);
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('language', $site->getLanguageById(0));
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[siteLanguage("locale") == "en_UK.UTF-8"]'));
        self::assertFalse($subject->match('[siteLanguage("locale") == "de_DE" && siteLanguage("title") == "UK"]'));
    }

    /**
     * Tests whether any property of a site matches the request
     */
    #[Test]
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
     */
    #[Test]
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
                ],
            ],
        ]);
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('site', $site);
        $subject = $this->getConditionMatcher();
        self::assertFalse($subject->match('[site("identifier") == "berta"]'));
        self::assertFalse($subject->match('[site("rootPageId") == 14 && site("rootPageId") == 23]'));
    }

    /**
     * @todo: It would be good to have another FE related test that actively sets up a page tree and uses a
     *        condition like "[tree.pagelayout == "pagets__simple"]" to make sure the full FE processing chain
     *        including TS parsing kicks in properly.
     */
    #[Test]
    public function pageLayoutIsResolvedCorrectlyFromBackendLayoutNextLevel(): void
    {
        $fullRootLine = [
            [
                'uid' => 4, // Deepest / current page
                'backend_layout_next_level' => '', // Current page
            ],
            [
                'uid' => 3,
                'backend_layout_next_level' => 'pagets__article',
            ],
            [
                'uid' => 2, // Could be TypoScript record with 'root' flag set
                'backend_layout_next_level' => 'pagets__default',
            ],
            [
                'uid' => 1, // Uppermost page
                'backend_layout_next_level' => '',
            ],
        ];
        $conditionMatcher = new ConditionMatcher(null, null, null, $fullRootLine);
        self::assertTrue($conditionMatcher->match('[tree.pagelayout == "pagets__article"]'));
    }

    /**
     * @todo: It would be good to have another FE related test that actively sets up a page tree and uses a
     *        condition like "[tree.pagelayout == "pagets__simple"]" to make sure the full FE processing chain
     *        including TS parsing kicks in properly.
     */
    #[Test]
    public function pageLayoutIsResolvedCorrectlyFromBackendLayout(): void
    {
        $GLOBALS['TSFE']->page = [
            'backend_layout' => 'pagets__special_layout',
        ];
        $fullRootLine = [
            [
                'uid' => 4, // Deepest / current page
                'backend_layout' => 'pagets__special_layout',
                'backend_layout_next_level' => '',
            ],
            [
                'uid' => 3,
                'backend_layout_next_level' => 'pagets__article',
            ],
            [
                'uid' => 2, // Could be TypoScript record with 'root' flag set
                'backend_layout_next_level' => 'pagets__default',
            ],
            [
                'uid' => 1, // Uppermost page
                'backend_layout_next_level' => '',
            ],
        ];
        $conditionMatcher = new ConditionMatcher(null, null, null, $fullRootLine);
        self::assertTrue($conditionMatcher->match('[tree.pagelayout == "pagets__special_layout"]'));
    }

    protected function getConditionMatcher(): ConditionMatcher
    {
        $conditionMatcher = new ConditionMatcher();
        $conditionMatcher->setLogger(new NullLogger());

        return $conditionMatcher;
    }

    protected function setupFrontendUserContext(array $groups = []): void
    {
        $frontendUser = $GLOBALS['TSFE']->fe_user;
        $frontendUser->user['uid'] = empty($groups) ? 0 : 13;
        foreach ($groups as $groupId) {
            $frontendUser->userGroups[$groupId] = ['uid' => $groupId];
        }

        GeneralUtility::makeInstance(Context::class)->setAspect('frontend.user', new UserAspect($frontendUser, $groups));
    }

    /**
     * Set up workspace aspect.
     */
    protected function setUpWorkspaceAspect(int $workspaceId): void
    {
        GeneralUtility::makeInstance(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
    }

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
                ],
            ],
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
        $GLOBALS['TSFE']->config['rootLine'] = [
            0 => ['uid' => 1, 'pid' => 0],
            1 => ['uid' => 2, 'pid' => 1],
            2 => ['uid' => 3, 'pid' => 2],
        ];
    }

    private function isSymfonySeven(): bool
    {
        // Symfony 7 dropped the `inArray` method with 7.0.0 from the BinaryNode, so we can use it as a check here for
        // the version and avoid to deal with composer version information here.
        return method_exists(
            BinaryNode::class,
            'inArray'
        ) === false;
    }
}
