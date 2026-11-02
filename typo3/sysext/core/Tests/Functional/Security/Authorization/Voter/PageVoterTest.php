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

namespace TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Voter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionConstraintEvent;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Security\Authorization\BackendMountContext;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Page\Fixtures\PagePermissionHooks;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageVoterTest extends FunctionalTestCase
{
    private const array SCOPES = [
        Scope::READ => Permission::PAGE_SHOW,
        Scope::UPDATE => Permission::PAGE_EDIT,
        Scope::DELETE => Permission::PAGE_DELETE,
        Scope::CREATE => Permission::PAGE_NEW,
        Scope::UPDATE_CONTENT => Permission::CONTENT_EDIT,
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Page/Fixtures/PagePermissions.csv');
    }

    protected function tearDown(): void
    {
        $this->get(TokenStorageInterface::class)->setToken(null);
        unset(
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'],
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'],
        );
        parent::tearDown();
    }

    public static function decisionsDataProvider(): \Generator
    {
        $all = [Scope::READ => true, Scope::UPDATE => true, Scope::DELETE => true, Scope::CREATE => true, Scope::UPDATE_CONTENT => true];
        $none = [Scope::READ => false, Scope::UPDATE => false, Scope::DELETE => false, Scope::CREATE => false, Scope::UPDATE_CONTENT => false];
        $showAndContent = [...$none, Scope::READ => true, Scope::UPDATE_CONTENT => true];
        yield 'group permissions within the webmount' => [2, 0, 3, $showAndContent];
        yield 'translation within the webmount of its default language page' => [2, 0, 8, $showAndContent];
        yield 'page below a page not shown' => [2, 0, 5, $none];
        yield 'owner without groups is shown the page only' => [3, 0, 3, [...$none, Scope::READ => true]];
        yield 'permissions of two groups' => [5, 0, 4, $all];
        yield 'user without mounts' => [6, 0, 13, $none];
        yield 'everybody and group permissions within the webmount' => [4, 0, 7, [...$none, Scope::READ => true, Scope::UPDATE => true]];
        yield 'page moved out of the webmount in the workspace' => [4, 1, 7, $none];
        yield 'page outside the webmount in live' => [2, 0, 7, $none];
        yield 'page moved into the webmount in the workspace' => [2, 1, 7, [...$none, Scope::READ => true]];
        yield 'page new in the workspace' => [2, 1, 12, $all];
        yield 'page new in the workspace below pages of another group' => [4, 1, 12, $none];
        yield 'page overlaid with its workspace version is shown by the live page' => [2, 1, 3, [...$none, Scope::READ => true], 'workspace'];
        yield 'page overlaid with its translation is shown by the default language page' => [2, 0, 3, [...$none, Scope::READ => true], 'language'];
        yield 'page overlaid with the workspace version of its translation is shown by the default language page' => [2, 1, 3, [...$none, Scope::READ => true], 'language'];
    }

    /**
     * @param array<string, bool> $expected
     */
    #[Test]
    #[DataProvider('decisionsDataProvider')]
    public function pageVoterDecidesOnThePermissionsOfTheUser(int $userId, int $workspaceId, int $pageId, array $expected, string $overlay = ''): void
    {
        self::assertSame($expected, $this->decide($userId, $workspaceId, $pageId, $overlay));
    }

    /**
     * @param array<string, bool> $expected
     */
    #[Test]
    #[DataProvider('decisionsDataProvider')]
    public function pageVoterDecidesAlikeWhileAConstraintListenerIsRegistered(int $userId, int $workspaceId, int $pageId, array $expected, string $overlay = ''): void
    {
        $this->addNoOpConstraintListener();
        self::assertSame($expected, $this->decide($userId, $workspaceId, $pageId, $overlay));
    }

    /**
     * @return array<string, bool>
     */
    private function decide(int $userId, int $workspaceId, int $pageId, string $overlay): array
    {
        $backendUser = $this->setUpBackendUser($userId);
        $backendUser->workspace = $workspaceId;
        $this->setToken($this->createPrincipal($backendUser), BackendMountContext::fromBackendUser($backendUser));
        $row = $this->fetchPages()[$pageId];
        if ($overlay === 'workspace') {
            BackendUtility::workspaceOL('pages', $row, $workspaceId);
            self::assertIsArray($row);
            self::assertArrayHasKey('_ORIG_uid', $row);
        } elseif ($overlay === 'language') {
            $context = new Context();
            $context->setAspect('workspace', new WorkspaceAspect($workspaceId));
            $row = new PageRepository($context)->getLanguageOverlay('pages', $row, new LanguageAspect(2, 2));
            self::assertIsArray($row);
            self::assertArrayHasKey('_LOCALIZED_UID', $row);
        }
        $page = $this->createPage($row);
        $decisions = [];
        foreach (array_keys(self::SCOPES) as $scope) {
            $decisions[$scope] = $this->get(AuthorizationCheckerInterface::class)->isGranted($scope, $page);
        }
        return $decisions;
    }

    public static function usersAndWorkspacesDataProvider(): \Generator
    {
        foreach ([1, 2, 3, 4, 5, 6, 7] as $userId) {
            foreach ([0, 1] as $workspaceId) {
                yield 'user ' . $userId . ' in workspace ' . $workspaceId => [$userId, $workspaceId, false];
                yield 'user ' . $userId . ' in workspace ' . $workspaceId . ' with a constraint listener' => [$userId, $workspaceId, true];
            }
        }
    }

    #[Test]
    #[DataProvider('usersAndWorkspacesDataProvider')]
    public function pageVoterAgreesWithTheBackendUserObject(int $userId, int $workspaceId, bool $withConstraintListener): void
    {
        if ($withConstraintListener) {
            $this->addNoOpConstraintListener();
        }
        $backendUser = $this->setUpBackendUser($userId);
        $backendUser->workspace = $workspaceId;
        $this->setToken($this->createPrincipal($backendUser), BackendMountContext::fromBackendUser($backendUser));
        foreach ($this->fetchPages() as $row) {
            $this->assertVoterAgreesWithTheBackendUserObject($backendUser, $row, 'page ' . $row['uid']);
            if ($workspaceId > 0 && (int)$row['t3ver_wsid'] === 0) {
                BackendUtility::workspaceOL('pages', $row, $workspaceId);
                if (is_array($row) && isset($row['_ORIG_uid'])) {
                    $this->assertVoterAgreesWithTheBackendUserObject($backendUser, $row, 'page ' . $row['uid'] . ' overlaid with ' . $row['_ORIG_uid']);
                }
            }
        }
    }

    private function assertVoterAgreesWithTheBackendUserObject(BackendUserAuthentication $backendUser, array $row, string $message): void
    {
        $page = $this->createPage($row);
        // readPageAccess() returns no deleted pages, admins pass the voter regardless
        if ((int)$row['deleted'] === 0) {
            self::assertSame(
                BackendUtility::readPageAccess((int)$row['uid'], $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) !== false,
                $this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $page),
                Scope::READ . ' ' . $message
            );
        }
        foreach (self::SCOPES as $scope => $permission) {
            if ($scope === Scope::READ) {
                continue;
            }
            self::assertSame(
                $backendUser->doesUserHaveAccess($row, $permission),
                $this->get(AuthorizationCheckerInterface::class)->isGranted($scope, $page),
                $scope . ' ' . $message
            );
        }
    }

    #[Test]
    public function tokenWithoutMountsTakesTheMountsOfTheRegisteredUser(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $principal = $this->createPrincipal($backendUser);
        $this->get(LegacyBackendUserRegistry::class)->register($principal, $backendUser);
        $this->setToken($principal, null);
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $this->createPage($this->fetchPages()[3])));
    }

    #[Test]
    public function tokenWithoutMountsIsDeniedForUnknownUsers(): void
    {
        $this->setToken($this->createPrincipal($this->setUpBackendUser(2)), null);
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $this->createPage($this->fetchPages()[3])));
    }

    #[Test]
    public function tokenWithoutMountsIsGrantedForAdmins(): void
    {
        $this->setToken($this->createPrincipal($this->setUpBackendUser(1)), null);
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::DELETE, $this->createPage($this->fetchPages()[3])));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedClauseHookIsApplied(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $principal = $this->createPrincipal($backendUser);
        $this->get(LegacyBackendUserRegistry::class)->register($principal, $backendUser);
        $this->setToken($principal, BackendMountContext::fromBackendUser($backendUser));
        $page = $this->createPage($this->fetchPages()[3]);
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $page));
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'][] = PagePermissionHooks::class . '->modifyClause';
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $page));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedPermissionsHookIsApplied(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $principal = $this->createPrincipal($backendUser);
        $this->get(LegacyBackendUserRegistry::class)->register($principal, $backendUser);
        $this->setToken($principal, BackendMountContext::fromBackendUser($backendUser));
        $page = $this->createPage($this->fetchPages()[3]);
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::CREATE, $page));
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] = PagePermissionHooks::class . '->modifyPermissions';
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::CREATE, $page));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedHooksDenyUsersWithoutUserObject(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $this->setToken($this->createPrincipal($backendUser), BackendMountContext::fromBackendUser($backendUser));
        $page = $this->createPage($this->fetchPages()[3]);
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::UPDATE_CONTENT, $page));
        self::assertTrue($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $page));
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] = PagePermissionHooks::class . '->modifyPermissions';
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::UPDATE_CONTENT, $page));
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'][] = PagePermissionHooks::class . '->modifyClause';
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertFalse($this->get(AuthorizationCheckerInterface::class)->isGranted(Scope::READ, $page));
    }

    private function createPrincipal(BackendUserAuthentication $backendUser): Principal
    {
        return new Principal(
            new PrincipalContext($backendUser->getRole(), (int)$backendUser->getUserId(), $backendUser->userGroupsUID),
            'backend',
            (string)$backendUser->getUserName(),
        );
    }

    private function setToken(Principal $principal, ?BackendMountContext $mounts): void
    {
        $token = new PrincipalToken($principal);
        if ($mounts !== null) {
            $token->setAttribute(BackendMountContext::TOKEN_ATTRIBUTE, $mounts);
        }
        $this->get(TokenStorageInterface::class)->setToken($token);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPages(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $pages = [];
        foreach ($queryBuilder->select('*')->from('pages')->executeQuery()->fetchAllAssociative() as $row) {
            $pages[(int)$row['uid']] = $row;
        }
        return $pages;
    }

    private function addNoOpConstraintListener(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('page-voter-test-constraint-listener', static function (ModifyPagePermissionConstraintEvent $event): void {});
        $this->get(ListenerProvider::class)->addListener(ModifyPagePermissionConstraintEvent::class, 'page-voter-test-constraint-listener');
        $this->get(CacheManager::class)->getCache('runtime')->flush();
    }

    private function createPage(array $row): Page
    {
        $page = $this->get(RecordFactory::class)->createFromDatabaseRow('pages', $row);
        self::assertInstanceOf(Page::class, $page);
        return $page;
    }
}
