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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Fetches the descendants of records in a table that references itself through a field
 * holding the uid of the parent record - "pages.pid" or "sys_category.parent" - with one
 * recursive common table expression (CTE).
 *
 * The table's TCA decides what is taken into account: records marked as deleted are left
 * out together with their descendants, only records of the default language and of all
 * languages are part of the tree, and records are resolved for the given workspace: a
 * record with a workspace version is found at the position of its version, records new
 * in the workspace are included, and records deleted in the workspace are left out
 * together with their descendants, unless they are requested.
 *
 * Access restrictions are not applied by default, callers hand them in as constraints:
 * a record not matching them is left out together with its descendants.
 *
 * @internal The building block for backend and frontend consumers, not part of the public API yet
 */
#[Autoconfigure(public: true)]
final readonly class RecordDescendantsRepository
{
    public const int MAX_TRAVERSAL_LEVELS = 100;

    public function __construct(
        private ConnectionPool $connectionPool,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Returns the descendants of the given records, without the given records themselves,
     * ordered depth-first: each record is followed by its descendants, records with the same
     * parent are ordered by the sorting field of the table, or by uid if it has none.
     *
     * Each row holds the uid of the live record as "uid", the parent uid (in the workspace)
     * under the name of the parent field, the sorting field if the table has one, and the
     * level below the given records as "_LEVEL", starting with 1. A record with a workspace
     * version additionally holds the uid of the version as "_ORIG_uid", other requested
     * fields are then taken from the version.
     *
     * @param list<int> $uids Records to start from, their descendants are returned in this order
     * @param int $depth Number of levels below the given records, 1 for their direct children only
     * @param (\Closure(ExpressionBuilder, string): string)|null $liveConstraint SQL constraint
     *        evaluated on the live record, receiving its table alias, which is the table name,
     *        e.g. for page permissions, which are not versioned. Column names may be unqualified
     *        or qualified with the table name
     * @param list<QueryRestrictionInterface> $restrictions Restrictions evaluated on the record
     *        (the workspace version, if there is one), e.g. to leave out hidden records
     * @param list<string> $fields Additional fields of the records to return, '*' for all
     * @param bool $includeDeleted Whether deleted records and their descendants are included
     * @param bool $includeDeletedInWorkspace Whether records deleted in the workspace and their descendants are included
     * @return list<array<string, mixed>>
     */
    public function findDescendants(
        string $table,
        string $parentField,
        array $uids,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint = null,
        array $restrictions = [],
        array $fields = [],
        bool $includeDeleted = false,
        bool $includeDeletedInWorkspace = false,
    ): array {
        $uids = array_values(array_unique(array_map(intval(...), $uids)));
        $depth = min($depth, self::MAX_TRAVERSAL_LEVELS);
        if ($uids === [] || $depth < 1) {
            return [];
        }
        $schema = $this->tcaSchemaFactory->get($table);
        $workspaceId = $schema->hasCapability(TcaSchemaCapability::Workspace) ? $workspaceId : 0;
        $sortingField = $schema->hasCapability(TcaSchemaCapability::SortByField)
            ? $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName()
            : null;
        $structureFields = array_values(array_filter(['uid', $parentField, $sortingField]));
        $cte = $this->connectionPool->getQueryBuilderForTable($table);
        $cte->getRestrictions()->removeAll();
        $cteFields = $this->addCommonTableExpression($cte, $cte, 'cte', $schema, $parentField, $sortingField, $uids, $depth, $workspaceId, $liveConstraint, $restrictions, $includeDeleted, $includeDeletedInWorkspace);
        // Fields are returned in the order of the table columns, like a record
        $selectFields = [];
        foreach ($this->resolveFields($table, $structureFields, $fields) as $field) {
            $selectFields[] = (in_array($field, $structureFields, true) ? 'cte.' : 'record.') . $field;
        }
        if (count($selectFields) > count($structureFields)) {
            $cte->innerJoin(
                'cte',
                $table,
                'record',
                $cte->expr()->eq('record.uid', $cte->quoteIdentifier('cte.__CTE_RECORD_UID__'))
            );
        }
        foreach (array_diff($cteFields, $structureFields) as $field) {
            $selectFields[] = 'cte.' . $field;
        }
        $rows = $cte->select(...$selectFields)->from('cte')->executeQuery()->fetchAllAssociative();
        return $this->sortDepthFirst($uids, $rows, $parentField, $sortingField);
    }

    /**
     * Returns the live uids of all descendants of the given records, without the given records.
     * Uids below 1 are ignored, they do not stand for a record.
     *
     * @param list<int> $uids
     * @return list<int>
     */
    public function findDescendantUids(string $table, string $parentField, array $uids, int $workspaceId): array
    {
        $uids = array_values(array_filter(array_map(intval(...), $uids), static fn(int $uid): bool => $uid > 0));
        return array_column(
            $this->findDescendants($table, $parentField, $uids, self::MAX_TRAVERSAL_LEVELS, $workspaceId),
            'uid'
        );
    }

    /**
     * Returns a constraint for a field holding a uid of a record of $table - for example the
     * "pid" of the records of a table referring to "pages" - to be one of the given records or
     * one of their descendants, down to $depth levels below the given records.
     *
     * The descendants are resolved by the database within the query, as a subquery with a
     * recursive common table expression, so no list of uids is built. The descendants are
     * resolved as by findDescendants(). If the query builder does not use the database
     * connection of $table, the descendants are fetched first and the constraint holds
     * their uids.
     *
     * @param QueryBuilder $queryBuilder Query the constraint is added to, its named parameters are used
     * @param string $fieldName Field holding a uid of $table, e.g. "pid" or "tt_content.pid"
     * @param list<int> $uids Records, whose uids are always matched by the constraint
     * @param int $depth Number of levels below the given records, 0 for the given records only
     * @param (\Closure(ExpressionBuilder, string): string)|null $liveConstraint See findDescendants()
     * @param list<QueryRestrictionInterface> $restrictions See findDescendants()
     * @param bool $includeDeleted See findDescendants()
     * @param bool $includeDeletedInWorkspace See findDescendants()
     */
    public function createTreeConstraint(
        QueryBuilder $queryBuilder,
        string $fieldName,
        string $table,
        string $parentField,
        array $uids,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint = null,
        array $restrictions = [],
        bool $includeDeleted = false,
        bool $includeDeletedInWorkspace = false,
    ): string {
        $uids = array_values(array_unique(array_map(intval(...), $uids)));
        $depth = min($depth, self::MAX_TRAVERSAL_LEVELS);
        $expr = $queryBuilder->expr();
        if ($uids === []) {
            return '1 = 0';
        }
        $uidConstraint = $expr->in($fieldName, $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT_ARRAY));
        if ($depth < 1) {
            return $uidConstraint;
        }
        if ($queryBuilder->getConnection() !== $this->connectionPool->getConnectionForTable($table)) {
            $descendants = $this->findDescendants($table, $parentField, $uids, $depth, $workspaceId, $liveConstraint, $restrictions, [], $includeDeleted, $includeDeletedInWorkspace);
            if ($descendants === []) {
                return $uidConstraint;
            }
            return (string)$expr->or(
                $uidConstraint,
                $expr->in($fieldName, $queryBuilder->quoteArrayBasedValueListToIntegerList(array_column($descendants, 'uid')))
            );
        }
        $schema = $this->tcaSchemaFactory->get($table);
        $workspaceId = $schema->hasCapability(TcaSchemaCapability::Workspace) ? $workspaceId : 0;
        $sortingField = $schema->hasCapability(TcaSchemaCapability::SortByField)
            ? $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName()
            : null;
        // A unique name, as the query may contain further common table expressions
        $cteName = $table . '_descendants_' . count($queryBuilder->getParameters());
        $subQuery = $this->connectionPool->getQueryBuilderForTable($table);
        $subQuery->getRestrictions()->removeAll();
        $this->addCommonTableExpression($subQuery, $queryBuilder, $cteName, $schema, $parentField, $sortingField, $uids, $depth, $workspaceId, $liveConstraint, $restrictions, $includeDeleted, $includeDeletedInWorkspace);
        $subQuery->select($cteName . '.uid')->from($cteName);
        return (string)$expr->or(
            $uidConstraint,
            $queryBuilder->quoteIdentifier($fieldName) . ' IN (' . $subQuery->getSQL() . ')'
        );
    }

    /**
     * Adds the recursive common table expression resolving the descendants of the given records
     * to $queryBuilder. Its named parameters are created in $parameterQueryBuilder, which is the
     * query the SQL is finally executed with.
     *
     * @param list<int> $uids
     * @param list<QueryRestrictionInterface> $restrictions
     * @return list<string> The fields of the common table expression
     */
    private function addCommonTableExpression(
        QueryBuilder $queryBuilder,
        QueryBuilder $parameterQueryBuilder,
        string $cteName,
        TcaSchema $schema,
        string $parentField,
        ?string $sortingField,
        array $uids,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint,
        array $restrictions,
        bool $includeDeleted,
        bool $includeDeletedInWorkspace,
    ): array {
        $cteFields = [...array_values(array_filter(['uid', $parentField, $sortingField])), '_ORIG_uid', '__CTE_RECORD_UID__', '__CTE_LEVEL__', '__CTE_PATH__', '__CTE_IS_CYCLE__'];
        $queryBuilder->typo3_withRecursive(
            $cteName,
            false,
            $this->createPartQueryBuilder($parameterQueryBuilder, $cteName, $schema, $parentField, $sortingField, $workspaceId, $liveConstraint, $restrictions, $uids, $depth, $includeDeleted, $includeDeletedInWorkspace),
            $this->createPartQueryBuilder($parameterQueryBuilder, $cteName, $schema, $parentField, $sortingField, $workspaceId, $liveConstraint, $restrictions, null, $depth, $includeDeleted, $includeDeletedInWorkspace),
            array_map($parameterQueryBuilder->quoteIdentifier(...), $cteFields),
        );
        return $cteFields;
    }

    /**
     * Creates the initial part of the CTE - the children of the given records - if uids are
     * given, and the recursive part - the children of the records found so far - if not.
     *
     * The live record is joined with the table name as alias. The live constraint is evaluated
     * in a subquery on the table, so clauses of BackendUserAuthentication::getPagePermsClause()
     * can be used as live constraint, and unqualified column names are not ambiguous. In a
     * workspace, the version of a record - if there is one - is joined as "workspace", records
     * new in the workspace are their own live record.
     *
     * @param list<QueryRestrictionInterface> $restrictions
     * @param list<int>|null $uids
     */
    private function createPartQueryBuilder(
        QueryBuilder $cte,
        string $cteName,
        TcaSchema $schema,
        string $parentField,
        ?string $sortingField,
        int $workspaceId,
        ?\Closure $liveConstraint,
        array $restrictions,
        ?array $uids,
        int $depth,
        bool $includeDeleted,
        bool $includeDeletedInWorkspace,
    ): QueryBuilder {
        $table = $schema->getName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $expr = $queryBuilder->expr();
        $isInitial = $uids !== null;
        $recordAlias = $workspaceId === 0 ? $table : 'workspace';
        $liveUid = $cte->quoteIdentifier($table . '.uid');
        $versionUid = $workspaceId === 0
            ? 'null'
            : $expr->if(
                $expr->gt($recordAlias . '.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                $cte->quoteIdentifier($recordAlias . '.uid'),
                'null',
            );
        $queryBuilder->selectLiteral(...array_values(array_filter([
            $expr->as($liveUid, 'uid'),
            $cte->quoteIdentifier($recordAlias . '.' . $parentField),
            $sortingField !== null ? $cte->quoteIdentifier($recordAlias . '.' . $sortingField) : null,
            $expr->as($versionUid, '_ORIG_uid'),
            $expr->as($cte->quoteIdentifier($recordAlias . '.uid'), '__CTE_RECORD_UID__'),
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
        ])));
        $constraints = [];
        if ($isInitial) {
            $queryBuilder->from($table, $recordAlias);
            $constraints[] = $expr->in($recordAlias . '.' . $parentField, $cte->createNamedParameter($uids, Connection::PARAM_INT_ARRAY));
        } else {
            $queryBuilder
                ->from($cteName, 'c')
                ->innerJoin('c', $table, $recordAlias, $expr->eq($recordAlias . '.' . $parentField, $cte->quoteIdentifier('c.uid')));
            $constraints[] = $expr->eq('c.__CTE_IS_CYCLE__', $cte->createNamedParameter(0, Connection::PARAM_INT));
            $constraints[] = $expr->lt('c.__CTE_LEVEL__', $cte->createNamedParameter($depth, Connection::PARAM_INT));
        }
        $deleteField = $schema->hasCapability(TcaSchemaCapability::SoftDelete)
            ? $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName()
            : null;
        if (!$includeDeleted && $deleteField !== null) {
            $constraints[] = $expr->eq($recordAlias . '.' . $deleteField, $cte->createNamedParameter(0, Connection::PARAM_INT));
        }
        if ($schema->hasCapability(TcaSchemaCapability::Language)) {
            $constraints[] = $expr->in(
                $recordAlias . '.' . $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                $cte->createNamedParameter([LanguageMarker::ALL_LANGUAGES, LanguageMarker::DEFAULT_LANGUAGE], Connection::PARAM_INT_ARRAY)
            );
        }
        if ($workspaceId === 0) {
            if ($schema->hasCapability(TcaSchemaCapability::Workspace)) {
                $constraints[] = $expr->eq($recordAlias . '.t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT));
            }
        } else {
            // A live record is replaced by its version in the workspace
            $versionConstraints = [
                $expr->eq('v.t3ver_oid', $cte->quoteIdentifier($recordAlias . '.uid')),
                $expr->eq('v.t3ver_wsid', $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)),
            ];
            if ($deleteField !== null) {
                $versionConstraints[] = $expr->eq('v.' . $deleteField, $cte->createNamedParameter(0, Connection::PARAM_INT));
            }
            $queryBuilder->leftJoin($recordAlias, $table, 'v', (string)$expr->and(...$versionConstraints));
            $constraints[] = $expr->or(
                $expr->and(
                    $expr->eq($recordAlias . '.t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    $expr->isNull('v.uid'),
                ),
                $expr->eq($recordAlias . '.t3ver_wsid', $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)),
            );
            if (!$includeDeletedInWorkspace) {
                $constraints[] = $expr->neq($recordAlias . '.t3ver_state', $cte->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT));
            }
            // Records new in the workspace are their own live record
            $queryBuilder->innerJoin(
                $recordAlias,
                $table,
                $table,
                $expr->eq($table . '.uid', $expr->if(
                    $expr->gt($recordAlias . '.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    $cte->quoteIdentifier($recordAlias . '.t3ver_oid'),
                    $cte->quoteIdentifier($recordAlias . '.uid'),
                ))
            );
        }
        foreach ($restrictions as $restriction) {
            $constraint = $restriction->buildExpression([$recordAlias => $table], $expr);
            if ($constraint->count() > 0) {
                $constraints[] = $constraint;
            }
        }
        if ($liveConstraint !== null) {
            // Evaluated in a scope of its own, so unqualified column names resolve to the live record
            $constraints[] = $expr->in(
                $table . '.uid',
                sprintf(
                    'SELECT %s FROM %s WHERE %s',
                    $cte->quoteIdentifier($table . '.uid'),
                    $cte->quoteIdentifier($table),
                    $liveConstraint($expr, $table)
                )
            );
        }
        return $queryBuilder->where(...$constraints);
    }

    /**
     * @param list<string> $structureFields
     * @param list<string> $fields
     * @return list<string> The structure fields and the requested fields, in the order of the table columns
     */
    private function resolveFields(string $table, array $structureFields, array $fields): array
    {
        if ($fields === []) {
            return $structureFields;
        }
        $columnNames = $this->connectionPool
            ->getConnectionForTable($table)
            ->getSchemaInformation()
            ->listTableColumnNames($table);
        if (in_array('*', $fields, true)) {
            return array_values($columnNames);
        }
        return array_values(array_filter(
            $columnNames,
            static fn(string $columnName): bool => in_array($columnName, $structureFields, true) || in_array($columnName, $fields, true)
        ));
    }

    /**
     * @param list<int> $uids
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function sortDepthFirst(array $uids, array $rows, string $parentField, ?string $sortingField): array
    {
        $rowsByParent = [];
        foreach ($rows as $row) {
            if ((bool)$row['__CTE_IS_CYCLE__']) {
                continue;
            }
            $row = $this->cleanRow($row, $parentField, $sortingField);
            // A record is found once per path leading to it, which only differs in a cycling tree
            $rowsByParent[$row[$parentField]][$row['uid']] ??= $row;
        }
        foreach ($rowsByParent as &$children) {
            usort(
                $children,
                static fn(array $a, array $b): int => [$sortingField !== null ? $a[$sortingField] : 0, $a['uid']] <=> [$sortingField !== null ? $b[$sortingField] : 0, $b['uid']]
            );
        }
        unset($children);
        $result = [];
        $visited = array_fill_keys($uids, true);
        $stack = array_map(static fn(int $uid): array => [$uid, 0, null], array_reverse($uids));
        while ($stack !== []) {
            [$uid, $level, $row] = array_pop($stack);
            if ($row !== null) {
                $result[] = $row;
            }
            foreach (array_reverse($rowsByParent[$uid] ?? []) as $child) {
                if (!isset($visited[$child['uid']])) {
                    $visited[$child['uid']] = true;
                    $stack[] = [$child['uid'], $level + 1, $child + ['_LEVEL' => $level + 1]];
                }
            }
        }
        return $result;
    }

    private function cleanRow(array $row, string $parentField, ?string $sortingField): array
    {
        $row['uid'] = (int)$row['uid'];
        $row[$parentField] = (int)$row[$parentField];
        if ($sortingField !== null) {
            $row[$sortingField] = (int)$row[$sortingField];
        }
        if ($row['_ORIG_uid'] === null) {
            unset($row['_ORIG_uid']);
        } else {
            $row['_ORIG_uid'] = (int)$row['_ORIG_uid'];
        }
        unset($row['__CTE_RECORD_UID__'], $row['__CTE_LEVEL__'], $row['__CTE_PATH__'], $row['__CTE_IS_CYCLE__']);
        return $row;
    }
}
