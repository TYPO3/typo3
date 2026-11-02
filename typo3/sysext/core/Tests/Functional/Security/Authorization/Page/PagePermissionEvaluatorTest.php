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

namespace TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionConstraintEvent;
use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionsEvent;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Domain\Record\PagePermissions;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Security\Authorization\Page\PageAccessContext;
use TYPO3\CMS\Core\Security\Authorization\Page\PagePermissionEvaluator;
use TYPO3\CMS\Core\Tests\Functional\Security\Authorization\Page\Fixtures\PagePermissionHooks;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PagePermissionEvaluatorTest extends FunctionalTestCase
{
    private const array USERS = [2, 3, 4, 5, 6, 7];
    private const array PERMISSIONS = [
        Permission::PAGE_SHOW,
        Permission::PAGE_EDIT,
        Permission::PAGE_DELETE,
        Permission::PAGE_NEW,
        Permission::CONTENT_EDIT,
        Permission::PAGE_SHOW | Permission::CONTENT_EDIT,
    ];
    private const array PAGES = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 99];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagePermissions.csv');
    }

    public static function usersDataProvider(): \Generator
    {
        foreach (self::USERS as $userId) {
            yield 'user ' . $userId => [$userId];
        }
    }

    #[Test]
    #[DataProvider('usersDataProvider')]
    public function clauseSelectsThePagesGrantedInPhp(int $userId): void
    {
        $context = $this->createContext($userId);
        $subject = $this->get(PagePermissionEvaluator::class);
        foreach (self::PERMISSIONS as $permissions) {
            $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $rows = $queryBuilder->select('*')->from('pages')->orderBy('uid')->executeQuery()->fetchAllAssociative();
            $grantedInPhp = [];
            foreach ($rows as $row) {
                if ($subject->isGranted($context, PagePermissions::fromRecord($row), $permissions)) {
                    $grantedInPhp[] = (int)$row['uid'];
                }
            }
            $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $selectedBySql = array_map(intval(...), $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(QueryHelper::stripLogicalOperatorPrefix($subject->getClause($context, $permissions)))
                ->orderBy('uid')
                ->executeQuery()
                ->fetchFirstColumn());
            self::assertSame($selectedBySql, $grantedInPhp, 'permissions ' . $permissions);
        }
    }

    public static function usersAndWorkspacesDataProvider(): \Generator
    {
        foreach (self::USERS as $userId) {
            foreach ([0, 1] as $workspaceId) {
                yield 'user ' . $userId . ' in workspace ' . $workspaceId => [$userId, $workspaceId];
            }
        }
    }

    #[Test]
    #[DataProvider('usersAndWorkspacesDataProvider')]
    public function webmountIsFoundAsByWalkingThePageTreeWithTheClause(int $userId, int $workspaceId): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $backendUser->workspace = $workspaceId;
        $context = PageAccessContext::fromBackendUser($backendUser);
        $subject = $this->get(PagePermissionEvaluator::class);
        $clause = $subject->getClause($context, Permission::PAGE_SHOW);
        foreach (self::PAGES as $pageId) {
            foreach ([true, false] as $useDeleteClause) {
                $message = 'page ' . $pageId . ($useDeleteClause ? '' : ' including deleted');
                $this->get(CacheManager::class)->getCache('runtime')->flush();
                $expected = $this->findWebmountByWalkingThePageTree($backendUser, $pageId, $clause, $useDeleteClause);
                $this->get(CacheManager::class)->getCache('runtime')->flush();
                self::assertSame($expected, $subject->findWebmount($context, $pageId, null, $useDeleteClause), $message);
                $this->get(CacheManager::class)->getCache('runtime')->flush();
                self::assertSame($expected, $subject->findWebmount($context, $pageId, $clause, $useDeleteClause), $message . ' with clause');
            }
        }
    }

    public static function calculatedPermissionsDataProvider(): \Generator
    {
        yield 'group permissions within the webmount' => [2, 3, Permission::PAGE_SHOW | Permission::CONTENT_EDIT];
        yield 'translation as its default language page' => [2, 8, Permission::PAGE_SHOW | Permission::CONTENT_EDIT];
        yield 'page below a page not shown' => [2, 5, Permission::NOTHING];
        yield 'page outside the webmounts' => [2, 7, Permission::NOTHING];
        yield 'owner without groups holds no permissions' => [3, 3, Permission::NOTHING];
        yield 'group and everybody permissions' => [4, 7, Permission::PAGE_SHOW | Permission::PAGE_EDIT];
        yield 'permissions of two groups' => [5, 4, Permission::ALL];
        yield 'user without mounts' => [6, 13, Permission::NOTHING];
        yield 'admin' => [1, 9, Permission::ALL];
    }

    #[Test]
    #[DataProvider('calculatedPermissionsDataProvider')]
    public function calculateReturnsThePermissionsOfTheUser(int $userId, int $pageId, int $expected): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $row = $this->fetchPage($pageId);
        self::assertSame($expected, $this->get(PagePermissionEvaluator::class)->calculate(PageAccessContext::fromBackendUser($backendUser), $row));
        self::assertSame($expected, $backendUser->calcPerms($row));
    }

    #[Test]
    public function calcPermsGrantsNothingForAMissingPageRecord(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        self::assertSame(Permission::NOTHING, $backendUser->calcPerms(false));
        self::assertSame(Permission::NOTHING, $backendUser->calcPerms(null));
        self::assertFalse($backendUser->doesUserHaveAccess(false, Permission::PAGE_SHOW));
    }

    #[Test]
    public function calcPermsGrantsAdminsAllPermissionsWithoutPageRecord(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        self::assertSame(Permission::ALL, $backendUser->calcPerms(false));
        self::assertSame(Permission::ALL, $backendUser->calcPerms(null));
        self::assertTrue($backendUser->doesUserHaveAccess(null, Permission::PAGE_NEW));
    }

    #[Test]
    public function webmountCheckFollowsChangedWebmountsAndWorkspace(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        self::assertNull($backendUser->isInWebMount(13));
        $backendUser->setWebmounts([13]);
        self::assertSame(13, $backendUser->isInWebMount(13));

        $backendUser = $this->setUpBackendUser(4);
        self::assertSame(6, $backendUser->isInWebMount(7));
        $backendUser->workspace = 1;
        self::assertNull($backendUser->isInWebMount(7));
    }

    #[Test]
    public function permissionsCanBeModifiedByListeners(): void
    {
        $this->addListener(ModifyPagePermissionsEvent::class, static function (ModifyPagePermissionsEvent $event): void {
            if ((int)$event->getPageRecord()['uid'] === 3 && $event->getGroupIds() === [1]) {
                $event->setPermissions($event->getPermissions() | Permission::PAGE_DELETE);
            }
        });
        $backendUser = $this->setUpBackendUser(2);
        self::assertSame(
            Permission::PAGE_SHOW | Permission::PAGE_DELETE | Permission::CONTENT_EDIT,
            $backendUser->calcPerms($this->fetchPage(3))
        );
    }

    #[Test]
    public function clauseOfListenersIsUsedToFindTheWebmount(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        self::assertSame(2, $backendUser->isInWebMount(3));
        $this->addListener(ModifyPagePermissionConstraintEvent::class, static function (ModifyPagePermissionConstraintEvent $event): void {
            $event->addConstraint($event->getExpressionBuilder()->neq($event->getTableAlias() . '.uid', 2));
        });
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertNull($backendUser->isInWebMount(3));
        self::assertStringContainsString(' <> 2', $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
    }

    #[Test]
    public function isShownAppliesTheClauseOfListenersToThePage(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $subject = $this->get(PagePermissionEvaluator::class);
        self::assertTrue($subject->isShown($backendUser->getPageAccessContext(), $this->fetchPage(3)));
        $this->addListener(ModifyPagePermissionConstraintEvent::class, static function (ModifyPagePermissionConstraintEvent $event): void {
            $event->addConstraint($event->getExpressionBuilder()->neq($event->getTableAlias() . '.uid', 3));
        });
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertFalse($subject->isShown($backendUser->getPageAccessContext(), $this->fetchPage(3)));
        self::assertTrue($subject->isShown($backendUser->getPageAccessContext(), $this->fetchPage(2)));
    }

    #[Test]
    public function listenersCanGrantPagesInAddition(): void
    {
        $this->addListener(ModifyPagePermissionConstraintEvent::class, static function (ModifyPagePermissionConstraintEvent $event): void {
            $expressionBuilder = $event->getExpressionBuilder();
            $event->setConstraint($expressionBuilder->or(
                $event->getConstraint(),
                $expressionBuilder->eq($event->getTableAlias() . '.uid', 6)
            ));
        });
        $backendUser = $this->setUpBackendUser(2);
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $selected = array_map(intval(...), $queryBuilder
            ->select('p.uid')
            ->from('pages', 'p')
            ->where(QueryHelper::stripLogicalOperatorPrefix(
                $this->get(PagePermissionEvaluator::class)->getClause($backendUser->getPageAccessContext(), Permission::PAGE_SHOW, 'p')
            ))
            ->orderBy('p.uid')
            ->executeQuery()
            ->fetchFirstColumn());
        self::assertContains(6, $selected);
        self::assertContains(3, $selected);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function deprecatedHooksAreStillApplied(): void
    {
        // Registered after the login, which drops webmounts the user may not see
        $backendUser = $this->setUpBackendUser(2);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'][] = PagePermissionHooks::class . '->modifyClause';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] = PagePermissionHooks::class . '->modifyPermissions';
        self::assertNull($backendUser->isInWebMount(3));
        self::assertSame(Permission::NOTHING, $backendUser->calcPerms($this->fetchPage(3)));
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause']);
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertSame(Permission::PAGE_SHOW | Permission::CONTENT_EDIT | Permission::PAGE_NEW, $backendUser->calcPerms($this->fetchPage(3)));
    }

    #[Test]
    public function clauseListenersReceiveTheLiveWorkspaceBeforeTheWorkspaceIsInitialized(): void
    {
        $workspaceIds = [];
        $this->addListener(ModifyPagePermissionConstraintEvent::class, static function (ModifyPagePermissionConstraintEvent $event) use (&$workspaceIds): void {
            $workspaceIds[] = $event->getWorkspaceId();
        });
        $this->setUpBackendUser(2);
        self::assertNotSame([], $workspaceIds);
        self::assertSame([0], array_values(array_unique($workspaceIds)));
    }

    private function createContext(int $userId): PageAccessContext
    {
        return PageAccessContext::fromBackendUser($this->setUpBackendUser($userId));
    }

    /**
     * The webmount check of BackendUserAuthentication::isInWebMount() before #111328
     */
    private function findWebmountByWalkingThePageTree(BackendUserAuthentication $backendUser, int $pageId, string $clause, bool $useDeleteClause): ?int
    {
        $record = BackendUtility::getRecord('pages', $pageId, 't3ver_oid,l10n_parent,sys_language_uid', '', $useDeleteClause);
        if (!is_array($record)) {
            return null;
        }
        $id = (int)$record['t3ver_oid'] > 0 ? (int)$record['t3ver_oid'] : $pageId;
        if ((int)$record['sys_language_uid'] !== 0 && (int)$record['l10n_parent'] !== 0) {
            $id = (int)$record['l10n_parent'];
        }
        $webmounts = array_map(intval(...), $backendUser->getWebmounts());
        foreach (BackendUtility::BEgetRootLine($id, ' AND ' . $clause, true, [], $useDeleteClause) as $page) {
            if ($page['uid'] && in_array((int)$page['uid'], $webmounts, true)) {
                return (int)$page['uid'];
            }
        }
        return null;
    }

    private function fetchPage(int $pageId): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder->select('*')->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $pageId))
            ->executeQuery()->fetchAssociative();
    }

    private function addListener(string $event, \Closure $listener): void
    {
        $identifier = 'page-permission-evaluator-test-' . md5($event);
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set($identifier, $listener);
        $this->get(ListenerProvider::class)->addListener($event, $identifier);
    }
}
