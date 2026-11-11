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

namespace TYPO3\CMS\Core\Domain\Repository;

use Doctrine\DBAL\Platforms\TrimMode;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Fetches the descendants of pages - their subpages, the subpages of these and so on -
 * with one recursive common table expression (CTE).
 *
 * Only pages of the default language are part of the page tree. Pages are resolved for
 * the given workspace: a page with a workspace version is found at the position of its
 * version, pages new in the workspace are included, and pages deleted in the workspace
 * are left out together with their subpages, unless they are requested.
 *
 * Access restrictions are not applied by default, callers hand them in as constraints:
 * a page not matching them is left out together with its subpages.
 *
 * @internal The building block for backend and frontend consumers, not part of the public API yet
 */
#[Autoconfigure(public: true)]
final readonly class PageDescendantsRepository
{
    public const int MAX_TRAVERSAL_LEVELS = 100;

    /**
     * Fields of the page tree structure, which are part of every returned row.
     */
    private const array STRUCTURE_FIELDS = ['uid', 'pid', 'sorting'];

    private const array CTE_FIELDS = ['uid', 'pid', 'sorting', '_ORIG_uid', '__CTE_RECORD_UID__', '__CTE_LEVEL__', '__CTE_PATH__', '__CTE_IS_CYCLE__'];

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Returns the descendants of the given pages, without the given pages themselves,
     * ordered depth-first: each page is followed by its subpages, pages with the same
     * parent page are ordered by their "sorting" value.
     *
     * Each row holds the uid of the live page as "uid", the parent page uid (in the
     * workspace) as "pid", "sorting" and the level below the given pages as "_LEVEL",
     * starting with 1. A page with a workspace version additionally holds the uid of the
     * version as "_ORIG_uid", other requested fields are then taken from the version.
     *
     * @param list<int> $pageIds Pages to start from, their descendants are returned in this order
     * @param int $depth Number of levels below the given pages, 1 for their direct subpages only
     * @param (\Closure(ExpressionBuilder, string): string)|null $liveConstraint SQL constraint
     *        evaluated on the live page, receiving the table alias of the live page ("pages"),
     *        e.g. for the page permissions, which are not versioned. Column names may be
     *        unqualified or qualified with "pages"
     * @param list<QueryRestrictionInterface> $restrictions Restrictions evaluated on the page
     *        (the workspace version, if there is one), e.g. to leave out hidden pages
     * @param list<string> $fields Additional fields of the page records to return, '*' for all
     * @param bool $includeDeleted Whether deleted pages and their subpages are included
     * @param bool $includeDeletedInWorkspace Whether pages deleted in the workspace and their subpages are included
     * @return list<array<string, mixed>>
     */
    public function findDescendants(
        array $pageIds,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint = null,
        array $restrictions = [],
        array $fields = [],
        bool $includeDeleted = false,
        bool $includeDeletedInWorkspace = false,
    ): array {
        $pageIds = array_values(array_unique(array_map(intval(...), $pageIds)));
        $depth = min($depth, self::MAX_TRAVERSAL_LEVELS);
        if ($pageIds === [] || $depth < 1) {
            return [];
        }
        $cte = $this->connectionPool->getQueryBuilderForTable('pages');
        $cte->getRestrictions()->removeAll();
        $this->addCommonTableExpression($cte, $cte, 'cte', $pageIds, $depth, $workspaceId, $liveConstraint, $restrictions, $includeDeleted, $includeDeletedInWorkspace);
        // Fields are returned in the order of the table columns, like a page record
        $selectFields = [];
        foreach ($this->resolveFields($fields) as $field) {
            $selectFields[] = (in_array($field, self::STRUCTURE_FIELDS, true) ? 'cte.' : 'record.') . $field;
        }
        if (count($selectFields) > count(self::STRUCTURE_FIELDS)) {
            $cte->innerJoin(
                'cte',
                'pages',
                'record',
                $cte->expr()->eq('record.uid', $cte->quoteIdentifier('cte.__CTE_RECORD_UID__'))
            );
        }
        foreach (array_diff(self::CTE_FIELDS, self::STRUCTURE_FIELDS) as $field) {
            $selectFields[] = 'cte.' . $field;
        }
        $rows = $cte->select(...$selectFields)->from('cte')->executeQuery()->fetchAllAssociative();
        return $this->sortDepthFirst($pageIds, $rows);
    }

    /**
     * Returns a constraint for a field holding a page uid - for example the "pid" of the
     * records of a table - to be one of the given pages or one of their descendants, down
     * to $depth levels below the given pages.
     *
     * The descendants are resolved by the database within the query, as a subquery with a
     * recursive common table expression, so no list of page uids is built. The descendants
     * are resolved as by findDescendants(). If the query builder does not use the database
     * connection of the pages table, the descendants are fetched first and the constraint
     * holds their uids.
     *
     * @param QueryBuilder $queryBuilder Query the constraint is added to, its named parameters are used
     * @param string $fieldName Field holding a page uid, e.g. "pid" or "tt_content.pid"
     * @param list<int> $pageIds Pages, whose uids are always matched by the constraint
     * @param int $depth Number of levels below the given pages, 0 for the given pages only
     * @param (\Closure(ExpressionBuilder, string): string)|null $liveConstraint See findDescendants()
     * @param list<QueryRestrictionInterface> $restrictions See findDescendants()
     * @param bool $includeDeleted See findDescendants()
     * @param bool $includeDeletedInWorkspace See findDescendants()
     */
    public function createPageTreeConstraint(
        QueryBuilder $queryBuilder,
        string $fieldName,
        array $pageIds,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint = null,
        array $restrictions = [],
        bool $includeDeleted = false,
        bool $includeDeletedInWorkspace = false,
    ): string {
        $pageIds = array_values(array_unique(array_map(intval(...), $pageIds)));
        $depth = min($depth, self::MAX_TRAVERSAL_LEVELS);
        $expr = $queryBuilder->expr();
        if ($pageIds === []) {
            return '1 = 0';
        }
        $pageConstraint = $expr->in($fieldName, $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY));
        if ($depth < 1) {
            return $pageConstraint;
        }
        if ($queryBuilder->getConnection() !== $this->connectionPool->getConnectionForTable('pages')) {
            $descendants = $this->findDescendants($pageIds, $depth, $workspaceId, $liveConstraint, $restrictions, [], $includeDeleted, $includeDeletedInWorkspace);
            if ($descendants === []) {
                return $pageConstraint;
            }
            return (string)$expr->or(
                $pageConstraint,
                $expr->in($fieldName, $queryBuilder->quoteArrayBasedValueListToIntegerList(array_column($descendants, 'uid')))
            );
        }
        // A unique name, as the query may contain further common table expressions
        $cteName = 'page_descendants_' . count($queryBuilder->getParameters());
        $subQuery = $this->connectionPool->getQueryBuilderForTable('pages');
        $subQuery->getRestrictions()->removeAll();
        $this->addCommonTableExpression($subQuery, $queryBuilder, $cteName, $pageIds, $depth, $workspaceId, $liveConstraint, $restrictions, $includeDeleted, $includeDeletedInWorkspace);
        $subQuery->select($cteName . '.uid')->from($cteName);
        return (string)$expr->or(
            $pageConstraint,
            $queryBuilder->quoteIdentifier($fieldName) . ' IN (' . $subQuery->getSQL() . ')'
        );
    }

    /**
     * Adds the recursive common table expression resolving the descendants of the given pages
     * to $queryBuilder. Its named parameters are created in $parameterQueryBuilder, which is the
     * query the SQL is finally executed with.
     */
    private function addCommonTableExpression(
        QueryBuilder $queryBuilder,
        QueryBuilder $parameterQueryBuilder,
        string $cteName,
        array $pageIds,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint,
        array $restrictions,
        bool $includeDeleted,
        bool $includeDeletedInWorkspace,
    ): void {
        $queryBuilder->typo3_withRecursive(
            $cteName,
            false,
            $this->createPartQueryBuilder($parameterQueryBuilder, $cteName, $workspaceId, $liveConstraint, $restrictions, $pageIds, $depth, $includeDeleted, $includeDeletedInWorkspace),
            $this->createPartQueryBuilder($parameterQueryBuilder, $cteName, $workspaceId, $liveConstraint, $restrictions, null, $depth, $includeDeleted, $includeDeletedInWorkspace),
            array_map($parameterQueryBuilder->quoteIdentifier(...), self::CTE_FIELDS),
        );
    }

    /**
     * Creates the initial part of the CTE - the subpages of the given pages - if page ids
     * are given, and the recursive part - the subpages of the pages found so far - if not.
     *
     * The live page is joined as "pages". The live constraint is evaluated in a subquery on
     * "pages", so clauses of BackendUserAuthentication::getPagePermsClause() can be used as
     * live constraint, and unqualified column names are not ambiguous. In a workspace, the
     * version of a page - if there is one - is joined as "workspace", pages new in the
     * workspace are their own live page.
     */
    private function createPartQueryBuilder(
        QueryBuilder $cte,
        string $cteName,
        int $workspaceId,
        ?\Closure $liveConstraint,
        array $restrictions,
        ?array $pageIds,
        int $depth,
        bool $includeDeleted,
        bool $includeDeletedInWorkspace,
    ): QueryBuilder {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $expr = $queryBuilder->expr();
        $isInitial = $pageIds !== null;
        $pageAlias = $workspaceId === 0 ? 'pages' : 'workspace';
        $liveUid = $cte->quoteIdentifier('pages.uid');
        $versionUid = $workspaceId === 0
            ? 'null'
            : $expr->if(
                $expr->gt($pageAlias . '.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                $cte->quoteIdentifier($pageAlias . '.uid'),
                'null',
            );

        $queryBuilder->selectLiteral(
            $expr->as($liveUid, 'uid'),
            $cte->quoteIdentifier($pageAlias . '.pid'),
            $cte->quoteIdentifier($pageAlias . '.sorting'),
            $expr->as($versionUid, '_ORIG_uid'),
            $expr->as($cte->quoteIdentifier($pageAlias . '.uid'), '__CTE_RECORD_UID__'),
            $isInitial
                ? $expr->castInt('1', '__CTE_LEVEL__')
                : $expr->castInt(sprintf('(%s + 1)', $expr->castInt($cte->quoteIdentifier('c.__CTE_LEVEL__'))), '__CTE_LEVEL__'),
            $isInitial
                ? $expr->castText($liveUid, '__CTE_PATH__')
                : $expr->castText(
                    $expr->concat(
                        $expr->trim('c.__CTE_PATH__', TrimMode::TRAILING, ' '),
                        $cte->quote(','),
                        $liveUid
                    ),
                    '__CTE_PATH__'
                ),
            $isInitial
                ? $expr->castInt('0 <> 0', '__CTE_IS_CYCLE__')
                : $expr->as(
                    sprintf('%s <> 0', $expr->castInt(sprintf('(%s)', $expr->inSet('c.__CTE_PATH__', $liveUid, true)))),
                    '__CTE_IS_CYCLE__'
                ),
        );

        $constraints = [];
        if ($isInitial) {
            $queryBuilder->from('pages', $pageAlias);
            $constraints[] = $expr->in($pageAlias . '.pid', $cte->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY));
        } else {
            $queryBuilder
                ->from($cteName, 'c')
                ->innerJoin('c', 'pages', $pageAlias, $expr->eq($pageAlias . '.pid', $cte->quoteIdentifier('c.uid')));
            $constraints[] = $expr->eq('c.__CTE_IS_CYCLE__', $cte->createNamedParameter(0, Connection::PARAM_INT));
            $constraints[] = $expr->lt('c.__CTE_LEVEL__', $cte->createNamedParameter($depth, Connection::PARAM_INT));
        }
        if (!$includeDeleted) {
            $constraints[] = $expr->eq($pageAlias . '.deleted', $cte->createNamedParameter(0, Connection::PARAM_INT));
        }
        $constraints[] = $expr->eq($pageAlias . '.sys_language_uid', $cte->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT));

        if ($workspaceId === 0) {
            $constraints[] = $expr->eq($pageAlias . '.t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT));
        } else {
            // A live page is replaced by its version in the workspace
            $queryBuilder->leftJoin(
                $pageAlias,
                'pages',
                'v',
                (string)$expr->and(
                    $expr->eq('v.t3ver_oid', $cte->quoteIdentifier($pageAlias . '.uid')),
                    $expr->eq('v.t3ver_wsid', $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)),
                    $expr->eq('v.deleted', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                )
            );
            $constraints[] = $expr->or(
                $expr->and(
                    $expr->eq($pageAlias . '.t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    $expr->isNull('v.uid'),
                ),
                $expr->eq($pageAlias . '.t3ver_wsid', $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)),
            );
            if (!$includeDeletedInWorkspace) {
                $constraints[] = $expr->neq($pageAlias . '.t3ver_state', $cte->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT));
            }
            // Pages new in the workspace are their own live page
            $queryBuilder->innerJoin(
                $pageAlias,
                'pages',
                'pages',
                $expr->eq('pages.uid', $expr->if(
                    $expr->gt($pageAlias . '.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    $cte->quoteIdentifier($pageAlias . '.t3ver_oid'),
                    $cte->quoteIdentifier($pageAlias . '.uid'),
                ))
            );
        }

        foreach ($restrictions as $restriction) {
            $constraint = $restriction->buildExpression([$pageAlias => 'pages'], $expr);
            if ($constraint->count() > 0) {
                $constraints[] = $constraint;
            }
        }
        if ($liveConstraint !== null) {
            // Evaluated in a scope of its own, so unqualified column names resolve to the live page
            $constraints[] = $expr->in(
                'pages.uid',
                sprintf(
                    'SELECT %s FROM %s WHERE %s',
                    $cte->quoteIdentifier('pages.uid'),
                    $cte->quoteIdentifier('pages'),
                    $liveConstraint($expr, 'pages')
                )
            );
        }
        return $queryBuilder->where(...$constraints);
    }

    /**
     * @param list<string> $fields
     * @return list<string> The structure fields and the requested fields, in the order of the table columns
     */
    private function resolveFields(array $fields): array
    {
        if ($fields === []) {
            return self::STRUCTURE_FIELDS;
        }
        $columnNames = $this->connectionPool
            ->getConnectionForTable('pages')
            ->getSchemaInformation()
            ->listTableColumnNames('pages');
        if (in_array('*', $fields, true)) {
            return array_values($columnNames);
        }
        return array_values(array_filter(
            $columnNames,
            static fn(string $columnName): bool => in_array($columnName, self::STRUCTURE_FIELDS, true) || in_array($columnName, $fields, true)
        ));
    }

    /**
     * @param list<int> $pageIds
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function sortDepthFirst(array $pageIds, array $rows): array
    {
        $rowsByParent = [];
        foreach ($rows as $row) {
            if ((bool)$row['__CTE_IS_CYCLE__']) {
                continue;
            }
            $row = $this->cleanRow($row);
            // A page is found once per path leading to it, which only differs in a cycling page tree
            $rowsByParent[$row['pid']][$row['uid']] ??= $row;
        }
        foreach ($rowsByParent as &$children) {
            usort($children, static fn(array $a, array $b): int => [$a['sorting'], $a['uid']] <=> [$b['sorting'], $b['uid']]);
        }
        unset($children);

        $result = [];
        $visited = array_fill_keys($pageIds, true);
        $stack = array_map(static fn(int $pageId): array => [$pageId, 0, null], array_reverse($pageIds));
        while ($stack !== []) {
            [$pageId, $level, $row] = array_pop($stack);
            if ($row !== null) {
                $result[] = $row;
            }
            foreach (array_reverse($rowsByParent[$pageId] ?? []) as $child) {
                if (!isset($visited[$child['uid']])) {
                    $visited[$child['uid']] = true;
                    $stack[] = [$child['uid'], $level + 1, $child + ['_LEVEL' => $level + 1]];
                }
            }
        }
        return $result;
    }

    private function cleanRow(array $row): array
    {
        $row['uid'] = (int)$row['uid'];
        $row['pid'] = (int)$row['pid'];
        $row['sorting'] = (int)$row['sorting'];
        if ($row['_ORIG_uid'] === null) {
            unset($row['_ORIG_uid']);
        } else {
            $row['_ORIG_uid'] = (int)$row['_ORIG_uid'];
        }
        unset($row['__CTE_RECORD_UID__'], $row['__CTE_LEVEL__'], $row['__CTE_PATH__'], $row['__CTE_IS_CYCLE__']);
        return $row;
    }
}
