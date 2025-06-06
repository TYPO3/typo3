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

namespace TYPO3\CMS\SysNote\Migration;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Migration wizard for updating System Note dashboard widgets to configurable variants.
 *
 * This upgrade wizard migrates existing System Note dashboard widgets from the legacy
 * implementation to the new configurable widget format introduced in TYPO3 v14.0.
 * It updates widget configurations in the database to use the new widget renderer
 * interface with per-instance settings support.
 *
 * The migration ensures that existing dashboard configurations continue to work
 * while enabling the new configuration capabilities for System Note widgets.
 *
 * @since 14.0
 * @internal This class is only meant to be used within EXT:sys_note and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('sysNoteDashboardWidgetDatabaseMigration')]
class SysNoteDashboardWidgetDatabaseMigration implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'be_dashboards';

    public function getTitle(): string
    {
        return 'Migrate System Note widgets to configurable variant';
    }

    public function getDescription(): string
    {
        return 'Migrates all existing System Note widget variants to the new unified, configurable widget. Previously, each note type (e.g., instructions, todos, templates) was registered as a separate widget instance. With this upgrade, all instances are consolidated into a single widget using the settings API, and the corresponding note type is preserved as a preconfigured setting.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        $mapping = $this->getWidgetMappig();
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);
        foreach ($this->getRecordsToUpdate() as $record) {
            $widgets = json_decode($record['widgets'], true);
            if (!is_array($widgets)) {
                continue;
            }
            $updated = false;
            foreach ($widgets as $key => &$widget) {
                $oldIdentifier = $widget['identifier'] ?? null;
                if (isset($mapping[$oldIdentifier])) {
                    $widget['identifier'] = $mapping[$oldIdentifier]['identifier'];
                    $widget['settings'] = $mapping[$oldIdentifier]['settings'];
                    $updated = true;
                }
            }
            if ($updated) {
                $encoded = json_encode($widgets, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $connection->update(
                    'be_dashboards',
                    ['widgets' => $encoded],
                    ['uid' => (int)$record['uid']]
                );
            }
        }

        return true;
    }

    protected function hasRecordsToUpdate(): bool
    {
        if (!$this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)->createSchemaManager()->tableExists(self::TABLE_NAME)) {
            return false;
        }
        return (bool)$this->getPreparedQueryBuilder()->count('uid')->executeQuery()->fetchOne();
    }

    protected function getRecordsToUpdate(): array
    {
        return $this->getPreparedQueryBuilder()->select('*')->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        $conditions = [];
        foreach (array_keys($this->getWidgetMappig()) as $identifier) {
            $conditions[] = $queryBuilder->expr()->like(
                'widgets',
                $queryBuilder->createNamedParameter('%"identifier":"' . $identifier . '"%')
            );
        }

        $queryBuilder
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->or(...$conditions));

        return $queryBuilder;
    }

    /**
     * @return array<string, array{
     *     identifier: string,
     *     settings: array{category: string}
     * }>
     */
    protected function getWidgetMappig(): array
    {
        return [
            'sys_note_all' => [
                'identifier' => 'pages_width_internal_note',
                'settings' => [
                    'category' => '',
                ],
            ],
            'sys_note_default' => [
                'identifier' => 'pages_width_internal_note',
                'settings' => [
                    'category' => '0',
                ],
            ],
            'sys_note_instructions' => [
                'identifier' => 'pages_width_internal_note',
                'settings' => [
                    'category' => '1',
                ],
            ],
            'sys_note_template' => [
                'identifier' => 'pages_width_internal_note',
                'settings' => [
                    'category' => '2',
                ],
            ],
            'sys_note_notes' => [
                'identifier' => 'pages_width_internal_note',
                'settings' => [
                    'category' => '3',
                ],
            ],
            'sys_note_todos' => [
                'identifier' => 'pages_width_internal_note',
                'settings' => [
                    'category' => '4',
                ],
            ],
        ];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
