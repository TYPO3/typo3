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

namespace TYPO3\CMS\Dashboard;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * @internal
 */
class DashboardRepository
{
    private const TABLE = 'be_dashboards';

    /**
     * @var string[]
     */
    protected $allowedFields = ['title'];

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var WidgetRegistry
     */
    protected $widgetRegistry;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var WidgetInterface[]
     */
    protected $widgets = [];

    public function __construct(
        ConnectionPool $connectionPool,
        WidgetRegistry $widgetRegistry,
        ContainerInterface $container
    ) {
        $this->connectionPool = $connectionPool;
        $this->widgetRegistry = $widgetRegistry;
        $this->container = $container;
    }

    public function getDashboardsForUser(int $userId): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('cruser_id', $queryBuilder->createNamedParameter($userId))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        $results = [];
        foreach ($rows as $row) {
            $results[] = $this->createFromRow($row);
        }
        return $results;
    }

    public function create(DashboardPreset $dashboardPreset, int $userId, string $title = ''): ?Dashboard
    {
        $widgets = [];
        $title = $title ?: $dashboardPreset->getTitle();

        foreach ($dashboardPreset->getDefaultWidgets() as $widget) {
            $hash = sha1($widget . '-' . time());
            $widgets[$hash] = ['identifier' => $widget];
        }
        $identifier = sha1($dashboardPreset->getIdentifier() . '-' . time());
        $this->getQueryBuilder()
            ->insert(self::TABLE)
            ->values([
                'identifier' => $identifier,
                'title' => $title,
                'tstamp' => time(),
                'crdate' => time(),
                'cruser_id' => $userId,
                'widgets' => json_encode($widgets),
            ])
            ->executeStatement();
        return $this->getDashboardByIdentifier($identifier);
    }

    /**
     * @param string $identifier
     * @param array $values
     * @return int|null
     */
    public function updateDashboardSettings(string $identifier, array $values)
    {
        $checkedValues = $this->checkAllowedFields($values);

        if (empty($checkedValues)) {
            return null;
        }

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->update(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createNamedParameter($identifier)
                )
            );

        foreach ($checkedValues as $field => $value) {
            $queryBuilder->set($field, $value);
        }

        return $queryBuilder->executeStatement();
    }

    /**
     * @param array $values
     * @return array
     */
    protected function checkAllowedFields($values): array
    {
        $allowedFields = [];
        foreach ($values as $field => $value) {
            if (!empty($value) && in_array((string)$field, $this->allowedFields, true)) {
                $allowedFields[$field] = $value;
            }
        }

        return $allowedFields;
    }

    /**
     * @param string $identifier
     * @return Dashboard
     */
    public function getDashboardByIdentifier(string $identifier): ?Dashboard
    {
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier)))
            ->executeQuery()
            ->fetchAllAssociative();
        if (count($row)) {
            return $this->createFromRow($row[0]);
        }
        return null;
    }

    /**
     * @param Dashboard $dashboard
     * @param string[] $widgets
     */
    public function updateWidgetConfig(Dashboard $dashboard, array $widgets): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->update(self::TABLE)
            ->set('widgets', json_encode($widgets))
            ->where($queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($dashboard->getIdentifier())))
            ->executeStatement();
    }

    /**
     * @param Dashboard $dashboard
     */
    public function delete(Dashboard $dashboard): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->update(self::TABLE)
            ->set('deleted', 1)
            ->where($queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($dashboard->getIdentifier())))
            ->executeStatement();
    }

    /**
     * @param array $row
     * @return Dashboard
     */
    protected function createFromRow(array $row): Dashboard
    {
        return GeneralUtility::makeInstance(
            Dashboard::class,
            $row['identifier'],
            $row['title'],
            json_decode((string)$row['widgets'], true) ?? [],
            $this->widgetRegistry,
            $this->container
        );
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable(self::TABLE);
    }
}
