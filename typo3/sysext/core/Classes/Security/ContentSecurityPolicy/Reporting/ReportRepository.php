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

    public function __construct(protected readonly ConnectionPool $pool)
    {
    }

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
            static fn (array $row) => Report::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    /**
     * @return list<SummarizedReport>
     */
    public function findAllSummarized(ReportDemand $demand = null): array
    {
        $demand ??= ReportDemand::create();
        $queryBuilder = $this->prepareQueryBuilder($demand);
        $subQueryBuilder = $this->prepareQueryBuilder($demand, $queryBuilder)
            ->select('uuid')
            ->groupBy('summary');
        $result = $queryBuilder
            ->select('*')
            ->where($queryBuilder->expr()->in('uuid', $subQueryBuilder->getSQL()))
            ->executeQuery();

        $summaryCountMap = $this->fetchSummaryCountMap();

        return array_map(
            static fn (array $row) => SummarizedReport::fromArray($row)
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
            static fn (array $row) => SummarizedReport::fromArray($row),
            $result->fetchAllAssociative()
        );
    }

    public function add(Report $report): bool
    {
        return $this->getConnection()->insert(
            self::TABLE_NAME,
            array_merge(
                $report->toArray(),
                ['type' => 'csp-report']
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

    protected function prepareQueryBuilder(ReportDemand $demand, QueryBuilder $effectiveQueryBuilder = null): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        $effectiveQueryBuilder = $effectiveQueryBuilder ?? $queryBuilder;
        $queryBuilder
            ->from(self::TABLE_NAME)
            ->andWhere($effectiveQueryBuilder->expr()->eq(
                'type',
                $effectiveQueryBuilder->createNamedParameter(self::TYPE)
            ));
        $this->applyDemand($demand, $queryBuilder, $effectiveQueryBuilder);
        return $queryBuilder;
    }

    protected function applyDemand(ReportDemand $demand, QueryBuilder $queryBuilder, QueryBuilder $effectiveQueryBuilder): void
    {
        $expr = $effectiveQueryBuilder->expr();
        if ($demand->status !== null) {
            $queryBuilder->andWhere($expr->eq(
                'status',
                $effectiveQueryBuilder->createNamedParameter($demand->status->value, Connection::PARAM_INT)
            ));
        }
        if ($demand->scope !== null) {
            $queryBuilder->andWhere($expr->eq(
                'scope',
                $effectiveQueryBuilder->createNamedParameter((string)$demand->scope)
            ));
        }
        if ($demand->summaries !== null) {
            $queryBuilder->andWhere($expr->in(
                'summary',
                $effectiveQueryBuilder->createNamedParameter(
                    $demand->summaries,
                    ArrayParameterType::STRING
                ),
            ));
        }
        if ($demand->requestTime !== null) {
            $requestTimeParam = $effectiveQueryBuilder->createNamedParameter(
                $demand->requestTime,
                Connection::PARAM_INT
            );
            if ($demand->afterRequestTime) {
                $queryBuilder->andWhere($expr->gt('request_time', $requestTimeParam));
            } else {
                $queryBuilder->andWhere($expr->eq('request_time', $requestTimeParam));
            }
        }
        if ($demand->orderFieldName !== null && $demand->orderDirection !== null) {
            $queryBuilder->orderBy(
                $demand->orderFieldName,
                $demand->orderDirection
            );
        }
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
