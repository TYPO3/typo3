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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\Uid\UuidV4;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

/**
 * @internal
 */
class ReportRepository
{
    protected const TABLE_NAME = 'sys_http_report';
    protected const TYPE = 'csp-report';

    public function __construct(protected readonly ConnectionPool $pool) {}

    /**
     * @return list<Report>
     */
    public function findAll(ReportDemand $demand = null): array
    {
        $demand ??= ReportDemand::create();
        $result = $this->prepareQueryBuilder($demand)
            ->select('*')
            ->executeQuery();
        return array_map(
            static fn(array $row) => Report::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    /**
     * @return list<SummarizedReport>
     */
    public function findAllSummarized(ReportDemand $demand = null): array
    {
        $demand ??= ReportDemand::create();
        $queryBuilder = $this->prepareQueryBuilder($demand, 'report');
        $uuidQueryBuilder = $this->getQueryBuilder()->from(self::TABLE_NAME, 'tab_uuid');
        $summaryQueryBuilder = $this->getQueryBuilder()->from(self::TABLE_NAME, 'tab_summary');
        $expr = $queryBuilder->expr();

        // these nested query builders are doing a bunch of things to meet `ONLY_FULL_GROUP_BY` constraints
        // + inner "summary" builder: build [summary; created] relation, summary must be distinct
        // + helping "uuid" builder: build [uuid <= {summary; created}] relation, summary must be distinct
        // + outer "report" builder: finally query [* <= {uuid <= {summary; created}}],
        //   conditions/filters are applied to this effective outer query builder

        $summaryQueryBuilder
            ->selectLiteral($this->createFunctionLiteral(
                $queryBuilder,
                'MAX',
                'tab_summary.created',
                'created'
            ))
            ->addSelectLiteral('summary')
            ->groupBy('summary');

        $this->applySummaryJoin(
            $uuidQueryBuilder,
            'tab_uuid',
            $summaryQueryBuilder->getSQL(),
            'res_summary',
            (string)$expr->and(
                $expr->eq('tab_uuid.summary', 'res_summary.summary'),
                $expr->eq('tab_uuid.created', 'res_summary.created')
            )
        );
        $uuidQueryBuilder
            ->selectLiteral($this->createFunctionLiteral(
                $queryBuilder,
                // using `MAX(col)` since `ANY_VALUE(col)` is not supported by PostgreSQL
                'MAX',
                'tab_uuid.uuid',
                'uuid'
            ))
            ->groupBy('tab_uuid.summary');

        $this->applySummaryJoin(
            $queryBuilder,
            'report',
            $uuidQueryBuilder->getSQL(),
            'res_uuid',
            $expr->eq('report.uuid', 'res_uuid.uuid')
        );
        $result = $queryBuilder
            ->select('report.*')
            ->executeQuery();

        $summaryCountMap = $this->fetchSummaryCountMap();

        return array_map(
            static fn(array $row) => SummarizedReport::fromArray($row)
                ->withCount($summaryCountMap[$row['summary']] ?? 0),
            $result->fetchAllAssociative()
        );
    }

    public function findByUuid(UuidV4 $uuid): ?Report
    {
        $result = $this->getConnection()->select(
            ['*'],
            self::TABLE_NAME,
            ['uuid' => (string)$uuid]
        );
        $row = $result->fetchAssociative();
        if (empty($row)) {
            return null;
        }
        return Report::fromArray($row);
    }

    /**
     * @return list<Report>
     */
    public function findBySummary(string ...$summaries): array
    {
        if ($summaries === []) {
            return [];
        }
        $demand = ReportDemand::forSummaries($summaries);
        $result = $this->prepareQueryBuilder($demand)
            ->select('*')
            ->executeQuery();
        return array_map(
            static fn(array $row) => SummarizedReport::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    public function add(Report $report): bool
    {
        return $this->getConnection()->insert(
            self::TABLE_NAME,
            array_merge(
                $report->toArray(),
                ['type' => self::TYPE]
            )
        ) === 1;
    }

    public function updateStatus(ReportStatus $status, UuidV4 ...$uuids): int
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->update(self::TABLE_NAME)
            ->set('status', $status->value)
            ->set('changed', time())
            ->where(
                $queryBuilder->expr()->in(
                    'uuid',
                    $queryBuilder->createNamedParameter($uuids, ArrayParameterType::STRING)
                )
            )
            ->executeStatement();
    }

    public function remove(UuidV4 $uuid): bool
    {
        return $this->getConnection()->delete(
            self::TABLE_NAME,
            ['uuid' => (string)$uuid]
        ) === 1;
    }

    public function removeAll(?Scope $scope = null): int
    {
        if ($scope === null) {
            return $this->getConnection()->truncate(self::TABLE_NAME);
        }
        return $this->getConnection()->delete(self::TABLE_NAME, ['scope' => (string)$scope]);
    }

    /**
     * @return array<string, int>
     */
    protected function fetchSummaryCountMap(): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder
            ->select('summary')
            ->addSelectLiteral(sprintf(
                'COUNT(%s) AS %s',
                $queryBuilder->quoteIdentifier('summary'),
                $queryBuilder->quoteIdentifier('summary_count')
            ))
            ->from(self::TABLE_NAME)
            ->groupBy('summary')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_combine(
            array_column($rows, 'summary'),
            array_column($rows, 'summary_count'),
        );
    }

    protected function prepareQueryBuilder(ReportDemand $demand, string $alias = null): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->from(self::TABLE_NAME, $alias);
        $this->applyStaticTypeCondition($queryBuilder, $alias);
        $this->applyDemand($demand, $queryBuilder, $alias);
        return $queryBuilder;
    }

    protected function applyDemand(ReportDemand $demand, QueryBuilder $queryBuilder, string $alias = null): void
    {
        $this->applyDemandConditions($demand, $queryBuilder, $alias);
        $this->applyDemandSorting($demand, $queryBuilder, $alias);
    }

    protected function applyDemandConditions(ReportDemand $demand, QueryBuilder $queryBuilder, string $alias = null): void
    {
        $expr = $queryBuilder->expr();
        $aliasPrefix = $this->prepareAliasPrefix($alias);
        if ($demand->status !== null) {
            $queryBuilder->andWhere($expr->eq(
                $aliasPrefix . 'status',
                $queryBuilder->createNamedParameter($demand->status->value, Connection::PARAM_INT)
            ));
        }
        if ($demand->scope !== null) {
            $queryBuilder->andWhere($expr->eq(
                $aliasPrefix . 'scope',
                $queryBuilder->createNamedParameter((string)$demand->scope)
            ));
        }
        if ($demand->summaries !== null) {
            $queryBuilder->andWhere($expr->in(
                $aliasPrefix . 'summary',
                $queryBuilder->createNamedParameter(
                    $demand->summaries,
                    ArrayParameterType::STRING
                ),
            ));
        }
        if ($demand->requestTime !== null) {
            $requestTimeParam = $queryBuilder->createNamedParameter(
                $demand->requestTime,
                Connection::PARAM_INT
            );
            if ($demand->afterRequestTime) {
                $queryBuilder->andWhere($expr->gt($aliasPrefix . 'request_time', $requestTimeParam));
            } else {
                $queryBuilder->andWhere($expr->eq($aliasPrefix . 'request_time', $requestTimeParam));
            }
        }
    }

    protected function applyDemandSorting(ReportDemand $demand, QueryBuilder $queryBuilder, string $alias = null): void
    {
        $aliasPrefix = $this->prepareAliasPrefix($alias);
        if ($demand->orderFieldName !== null && $demand->orderDirection !== null) {
            $queryBuilder->orderBy(
                $aliasPrefix . $demand->orderFieldName,
                $demand->orderDirection
            );
        }
    }

    protected function applyStaticTypeCondition(QueryBuilder $queryBuilder, string $alias = null): void
    {
        $aliasPrefix = $this->prepareAliasPrefix($alias);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $aliasPrefix . 'type',
                $queryBuilder->createNamedParameter(self::TYPE)
            )
        );
    }

    protected function applySummaryJoin(QueryBuilder $queryBuilder, string $fromAlias, string $join, string $alias, string $condition): void
    {
        $queryBuilder->getConcreteQueryBuilder()->join(
            $queryBuilder->quoteIdentifier($fromAlias),
            sprintf('(%s)', $join),
            $queryBuilder->quoteIdentifier($alias),
            $condition
        );
    }

    protected function createFunctionLiteral(QueryBuilder $queryBuilder, string $functionName, string $fieldName, string $alias = null): string
    {
        $values = [
            $functionName,
            $queryBuilder->quoteIdentifier($fieldName),
        ];
        if ($alias === null) {
            $format = '%s(%s)';
        } else {
            $format = '%s(%s) AS %s';
            $values[] = $queryBuilder->quoteIdentifier($alias);
        }
        return vsprintf($format, $values);
    }

    protected function prepareAliasPrefix(string $alias = null): string
    {
        return $alias === null ? '' : $alias . '.';
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->pool->getQueryBuilderForTable(self::TABLE_NAME);
    }

    protected function getConnection(): Connection
    {
        return $this->pool->getConnectionForTable(self::TABLE_NAME);
    }
}
