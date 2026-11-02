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

namespace TYPO3\CMS\Core\Security\Authorization\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionConstraintEvent;
use TYPO3\CMS\Core\Authentication\Event\ModifyPagePermissionsEvent;
use TYPO3\CMS\Core\Authentication\Exception\InvalidPageRecordException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\PageAncestryResolver;
use TYPO3\CMS\Core\Domain\Record\PagePermissions;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Decides the page permissions of a backend user: the SQL clause selecting the
 * pages a user has given permissions for, the webmount a page is found in, and
 * the permissions of a user on a page.
 *
 * The webmount of a page is found along its ancestry, resolved with one query
 * and kept for the request. The show permission of the pages in the ancestry
 * is evaluated in PHP. Only while a listener of the
 * ModifyPagePermissionConstraintEvent or the deprecated getPagePermsClause hook
 * is registered, or the caller passes a clause of its own, the pages of the
 * ancestry are selected with the clause in one more query.
 *
 * The deprecated hooks getPagePermsClause and calcPerms are called with the
 * backend user object of the context. Without that object, a registered hook
 * denies everything, as the hook may restrict the permissions.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[Autoconfigure(public: true)]
final readonly class PagePermissionEvaluator
{
    private const string HOOKS = 't3lib/class.t3lib_userauthgroup.php';

    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
        private PageAncestryResolver $pageAncestryResolver,
        private EventDispatcherInterface $eventDispatcher,
        private ListenerProvider $listenerProvider,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
    ) {}

    /**
     * Returns a clause selecting the pages the user holds all given permissions for,
     * " 1=1" for admins and " 1=0" without a user. Prefix it with " AND ".
     */
    public function getClause(PageAccessContext $context, int $permissions, string $tableAlias = 'pages'): string
    {
        if ($context->isAdmin) {
            return ' 1=1';
        }
        if ($context->userId <= 0) {
            return ' 1=0';
        }

        if ($this->hasConstraintListeners()) {
            $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('pages')->expr();
            $constraint = $this->eventDispatcher->dispatch(new ModifyPagePermissionConstraintEvent(
                $this->buildConstraint($expressionBuilder, $context, $permissions, $tableAlias),
                $expressionBuilder,
                $permissions,
                $tableAlias,
                $context->userId,
                $context->groupIds,
                $context->mounts->workspaceId,
            ))->getConstraint();
            $clause = ' (' . $constraint . ')';
        } else {
            $cacheIdentifier = 'page-permission-clause-' . hash('xxh3', $context->getPermissionIdentifier() . '|' . $permissions . '|' . $tableAlias);
            $clause = $this->runtimeCache->get($cacheIdentifier);
            if (!is_string($clause)) {
                $expressionBuilder = $this->connectionPool->getQueryBuilderForTable('pages')->expr();
                $clause = ' (' . $this->buildConstraint($expressionBuilder, $context, $permissions, $tableAlias) . ')';
                $this->runtimeCache->set($cacheIdentifier, $clause);
            }
        }
        return $this->applyClauseHooks($context, $clause, $permissions);
    }

    /**
     * Evaluates permission settings of a page as the clause of getClause() does,
     * without the ModifyPagePermissionConstraintEvent and the getPagePermsClause hook.
     */
    public function isGranted(PageAccessContext $context, ?PagePermissions $settings, int $permissions): bool
    {
        if ($settings === null || ($context->userId <= 0 && !$context->isAdmin)) {
            return false;
        }
        if ($context->isAdmin) {
            return true;
        }
        return $settings->getEverybodyPermissions()->isGranted($permissions)
            || ($settings->getOwnerUserId() === $context->userId && $settings->getOwnerPermissions()->isGranted($permissions))
            || (in_array($settings->getOwnerGroupId(), $context->groupIds, true) && $settings->getGroupPermissions()->isGranted($permissions));
    }

    /**
     * Returns the uid of the first webmount found in the rootline of a page, 1 for
     * admins, and null if the page is not within a webmount. Each page from the
     * page up to the webmount must be shown to the user.
     *
     * A workspace version is checked as its live page, a translation as its
     * default language page.
     *
     * @param int|array<string, mixed> $idOrRow page uid or page record
     * @param string|null $readPermsClause a clause of the caller the pages up to the webmount
     *                                     must match, null for the show permission of the user
     * @throws InvalidPageRecordException
     */
    public function findWebmount(PageAccessContext $context, int|array $idOrRow, ?string $readPermsClause = null, bool $useDeleteClause = true): ?int
    {
        if ($context->isAdmin) {
            return 1;
        }
        $id = $this->resolveDefaultLanguagePageId($idOrRow, $useDeleteClause);
        if ($id <= 0) {
            return null;
        }
        $ancestry = $this->pageAncestryResolver->resolve($id, $context->mounts->workspaceId, !$useDeleteClause);
        $pageIds = $ancestry->getPageIds();
        if ($readPermsClause === null && !$this->hasConstraintListeners() && !$this->hasHooks('getPagePermsClause')) {
            $isShown = fn(int $pageId): bool => $this->isGranted($context, $ancestry->getPermissions($pageId), Permission::PAGE_SHOW);
        } else {
            $shownPageIds = $this->selectPageIds($pageIds, $readPermsClause ?? $this->getClause($context, Permission::PAGE_SHOW), $useDeleteClause);
            $isShown = static fn(int $pageId): bool => in_array($pageId, $shownPageIds, true);
        }
        foreach ($pageIds as $pageId) {
            if (!$isShown($pageId)) {
                return null;
            }
            if (in_array($pageId, $context->mounts->webmounts, true)) {
                return $pageId;
            }
        }
        return null;
    }

    /**
     * Whether a page is shown to the user, as BackendUtility::readPageAccess()
     * decides: the page record grants the show permission, and the page lies
     * within a webmount. As readPageAccess() selects the page by its uid, the
     * settings of the record stored under the uid of the row decide: a row
     * overlaid with a workspace version or a translation (_ORIG_uid or
     * _LOCALIZED_UID set) is checked with the settings of that record, not
     * with the settings merged in by the overlay. A workspace version row or a
     * translation row is checked with its own settings.
     *
     * @param array<string, mixed> $row page record
     * @throws InvalidPageRecordException
     */
    public function isShown(PageAccessContext $context, array $row, bool $useDeleteClause = true): bool
    {
        if ($context->isAdmin) {
            return true;
        }
        if ($this->findWebmount($context, $row, null, $useDeleteClause) === null) {
            return false;
        }
        $settings = isset($row['_ORIG_uid']) || isset($row['_LOCALIZED_UID']) ? null : PagePermissions::fromRecord($row);
        if ($settings !== null && !$this->hasConstraintListeners() && !$this->hasHooks('getPagePermsClause')) {
            return $this->isGranted($context, $settings, Permission::PAGE_SHOW);
        }
        $pageId = (int)$row['uid'];
        return $this->selectPageIds([$pageId], $this->getClause($context, Permission::PAGE_SHOW), $useDeleteClause) === [$pageId];
    }

    /**
     * Returns the permissions of the user on a page as bitmask, all for admins
     * and none for pages outside the webmounts.
     *
     * @param array<string, mixed> $row page record with all perms_* fields
     */
    public function calculate(PageAccessContext $context, array $row, bool $useDeleteClause = true): int
    {
        if ($context->isAdmin) {
            return Permission::ALL;
        }
        if (!$this->findWebmount($context, $row, null, $useDeleteClause)) {
            return Permission::NOTHING;
        }
        return $this->calculateFromRecord($context, $row);
    }

    /**
     * Returns the permissions of the user on a page as bitmask, without checking
     * the webmounts. A user without groups holds no permissions, even if the page
     * grants permissions to everybody.
     *
     * @param array<string, mixed> $row page record with all perms_* fields
     */
    public function calculateFromRecord(PageAccessContext $context, array $row): int
    {
        if ($context->isAdmin) {
            return Permission::ALL;
        }
        $permissions = Permission::NOTHING;
        if (isset($row['perms_userid'], $row['perms_user'], $row['perms_groupid'], $row['perms_group'], $row['perms_everybody'])
            && $context->groupIds !== []
        ) {
            if ($context->userId === (int)$row['perms_userid']) {
                $permissions |= (int)$row['perms_user'];
            }
            $groupId = (int)$row['perms_groupid'];
            if ($groupId > 0 && in_array($groupId, $context->groupIds, true)) {
                $permissions |= (int)$row['perms_group'];
            }
            $permissions |= (int)$row['perms_everybody'];
        }
        $permissions = $this->eventDispatcher->dispatch(new ModifyPagePermissionsEvent(
            $row,
            $permissions,
            $context->userId,
            $context->groupIds,
            $context->mounts->workspaceId,
        ))->getPermissions();
        return $this->applyPermissionHooks($context, $row, $permissions);
    }

    private function applyClauseHooks(PageAccessContext $context, string $clause, int $permissions): string
    {
        if (!$this->hasHooks('getPagePermsClause')) {
            return $clause;
        }
        trigger_error(
            'The hook $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauthgroup.php\'][\'getPagePermsClause\'] is deprecated since TYPO3 v15.0 and will be removed in TYPO3 v16.0. Use the ModifyPagePermissionConstraintEvent instead.',
            E_USER_DEPRECATED
        );
        if ($context->legacyBackendUser === null) {
            return ' 1=0';
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::HOOKS]['getPagePermsClause'] as $hook) {
            $params = ['currentClause' => $clause, 'perms' => $permissions];
            $clause = GeneralUtility::callUserFunction($hook, $params, $context->legacyBackendUser);
        }
        return $clause;
    }

    private function applyPermissionHooks(PageAccessContext $context, array $row, int $permissions): int
    {
        if (!$this->hasHooks('calcPerms')) {
            return $permissions;
        }
        trigger_error(
            'The hook $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'t3lib/class.t3lib_userauthgroup.php\'][\'calcPerms\'] is deprecated since TYPO3 v15.0 and will be removed in TYPO3 v16.0. Use the ModifyPagePermissionsEvent instead.',
            E_USER_DEPRECATED
        );
        if ($context->legacyBackendUser === null) {
            return Permission::NOTHING;
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::HOOKS]['calcPerms'] as $hook) {
            $params = ['row' => $row, 'outputPermissions' => $permissions];
            $permissions = (int)GeneralUtility::callUserFunction($hook, $params, $context->legacyBackendUser);
        }
        return $permissions;
    }

    private function hasHooks(string $name): bool
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::HOOKS][$name]);
    }

    /**
     * Selects the pages of the ancestry matching the clause, as the walk of
     * BackendUtility::BEgetRootLine() with the clause does: on the live record
     * of a page with a workspace version.
     *
     * @param list<int> $pageIds
     * @return list<int>
     */
    private function selectPageIds(array $pageIds, string $clause, bool $useDeleteClause): array
    {
        if ($pageIds === []) {
            return [];
        }
        $cacheIdentifier = 'page-permission-ancestry-' . hash('xxh3', $clause . '|' . implode(',', $pageIds) . '|' . (int)$useDeleteClause);
        $shownPageIds = $this->runtimeCache->get($cacheIdentifier);
        if (is_array($shownPageIds)) {
            return $shownPageIds;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        if ($useDeleteClause) {
            $queryBuilder->getRestrictions()->add(new DeletedRestriction());
        }
        $shownPageIds = array_map(intval(...), $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)),
                QueryHelper::stripLogicalOperatorPrefix($clause)
            )
            ->executeQuery()
            ->fetchFirstColumn());
        $this->runtimeCache->set($cacheIdentifier, $shownPageIds);
        return $shownPageIds;
    }

    /**
     * Webmounts point to live pages in the default language. A page new in a
     * workspace is resolved along the ancestry of its own workspace by the
     * PageAncestryResolver.
     *
     * @return int the page uid, 0 if the page does not exist
     */
    private function resolveDefaultLanguagePageId(int|array $idOrRow, bool $useDeleteClause): int
    {
        $languageCapability = $this->tcaSchemaFactory->get('pages')->getCapability(TcaSchemaCapability::Language);
        $languageFieldName = $languageCapability->getLanguageField()->getName();
        $transOrigPointerFieldName = $languageCapability->getTranslationOriginPointerField()->getName();
        if (is_array($idOrRow)) {
            if (!isset($idOrRow['uid'])) {
                throw new InvalidPageRecordException('The given page record is invalid. Missing uid.', 1578950324);
            }
            $id = (int)$idOrRow['uid'];
            $record = isset($idOrRow['t3ver_oid'], $idOrRow[$languageFieldName], $idOrRow[$transOrigPointerFieldName])
                ? $idOrRow
                : $this->fetchPageRecord($id, ['t3ver_oid', $transOrigPointerFieldName, $languageFieldName], $useDeleteClause);
        } else {
            $id = $idOrRow;
            $record = $this->fetchPageRecord($id, ['t3ver_oid', $transOrigPointerFieldName, $languageFieldName], $useDeleteClause);
        }
        if (!is_array($record)) {
            return 0;
        }
        if ((int)($record['t3ver_oid'] ?? 0) > 0) {
            $id = (int)$record['t3ver_oid'];
        }
        if ((int)$record[$languageFieldName] !== LanguageMarker::DEFAULT_LANGUAGE && (int)$record[$transOrigPointerFieldName] !== 0) {
            $id = (int)$record[$transOrigPointerFieldName];
        }
        return $id;
    }

    /**
     * @param list<string> $fields
     * @return array<string, mixed>|null
     */
    private function fetchPageRecord(int $id, array $fields, bool $useDeleteClause): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        if ($useDeleteClause) {
            $queryBuilder->getRestrictions()->add(new DeletedRestriction());
        }
        $record = $queryBuilder
            ->select(...$fields)
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        return is_array($record) ? $record : null;
    }

    private function buildConstraint(ExpressionBuilder $expressionBuilder, PageAccessContext $context, int $permissions, string $tableAlias): CompositeExpression
    {
        $constraint = $expressionBuilder->or(
            $expressionBuilder->comparison(
                $expressionBuilder->bitAnd($tableAlias . '.perms_everybody', $permissions),
                ExpressionBuilder::EQ,
                $permissions
            ),
            $expressionBuilder->and(
                $expressionBuilder->eq($tableAlias . '.perms_userid', $context->userId),
                $expressionBuilder->comparison(
                    $expressionBuilder->bitAnd($tableAlias . '.perms_user', $permissions),
                    ExpressionBuilder::EQ,
                    $permissions
                )
            )
        );
        if ($context->groupIds !== []) {
            $constraint = $constraint->with(
                $expressionBuilder->and(
                    $expressionBuilder->in($tableAlias . '.perms_groupid', $context->groupIds),
                    $expressionBuilder->comparison(
                        $expressionBuilder->bitAnd($tableAlias . '.perms_group', $permissions),
                        ExpressionBuilder::EQ,
                        $permissions
                    )
                )
            );
        }
        return $constraint;
    }

    private function hasConstraintListeners(): bool
    {
        return !empty($this->listenerProvider->getAllListenerDefinitions()[ModifyPagePermissionConstraintEvent::class]);
    }
}
