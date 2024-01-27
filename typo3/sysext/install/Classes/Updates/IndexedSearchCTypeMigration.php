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

namespace TYPO3\CMS\Install\Updates;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @since 13.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('indexedSearchCTypeMigration')]
final class IndexedSearchCTypeMigration implements UpgradeWizardInterface
{
    protected const TABLE_CONTENT = 'tt_content';
    protected const TABLE_BACKEND_USER_GROUPS = 'be_groups';

    public function __construct(
        private readonly Registry $registry,
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate "Indexed Search" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "Indexed Search" plugin is now registered as content elements. Update migrates existing records and backend user permissions.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return ($this->columnsExistInContentTable() && $this->hasContentElementsToUpdate())
            || (
                $this->columnsExistInBackendUserGroupsTable()
                && $this->backendGroupsExplicitAllowDenyMigrationHasBeenExecuted()
                && $this->hasBackendUserGroupsToUpdate()
            );
    }

    public function executeUpdate(): bool
    {
        if (($this->columnsExistInContentTable() && $this->hasContentElementsToUpdate())) {
            $this->updateContentElements();
        }
        if ($this->columnsExistInBackendUserGroupsTable()
            && $this->backendGroupsExplicitAllowDenyMigrationHasBeenExecuted()
            && $this->hasBackendUserGroupsToUpdate()
        ) {
            $this->updateBackendUserGroups();
        }

        return true;
    }

    protected function columnsExistInContentTable(): bool
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable(self::TABLE_CONTENT)
            ->createSchemaManager();

        $tableColumnNames = array_flip(
            array_map(
                static fn(Column $column) => $column->getName(),
                $schemaManager->listTableColumns(self::TABLE_CONTENT)
            )
        );

        foreach (['CType', 'list_type'] as $column) {
            if (!isset($tableColumnNames[$column])) {
                return false;
            }
        }

        return true;
    }

    protected function columnsExistInBackendUserGroupsTable(): bool
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS)
            ->createSchemaManager();

        return isset($schemaManager->listTableColumns(self::TABLE_BACKEND_USER_GROUPS)['explicit_allowdeny']);
    }

    protected function hasContentElementsToUpdate(): bool
    {
        return (bool)$this->getPreparedQueryBuilderForContentElements()->count('uid')->executeQuery()->fetchOne();
    }

    protected function hasBackendUserGroupsToUpdate(): bool
    {
        return (bool)$this->getPreparedQueryBuilderForBackendUserGroups()->count('uid')->executeQuery()->fetchOne();
    }

    protected function backendGroupsExplicitAllowDenyMigrationHasBeenExecuted(): bool
    {
        return (bool)$this->registry->get('installUpdate', BackendGroupsExplicitAllowDenyMigration::class, false);
    }

    protected function getContentElementsToUpdate(): array
    {
        return $this->getPreparedQueryBuilderForContentElements()->select('uid', 'CType', 'list_type')->executeQuery()->fetchAllAssociative();
    }

    protected function getBackendUserGroupsToUpdate(): array
    {
        return $this->getPreparedQueryBuilderForBackendUserGroups()->select('uid', 'explicit_allowdeny')->executeQuery()->fetchAllAssociative();
    }

    protected function getPreparedQueryBuilderForContentElements(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter('indexedsearch_pi2')),
            );

        return $queryBuilder;
    }

    protected function getPreparedQueryBuilderForBackendUserGroups(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLE_BACKEND_USER_GROUPS)
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter(
                        '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:indexedsearch_pi2') . '%'
                    )
                ),
            );

        return $queryBuilder;
    }

    protected function updateContentElements(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_CONTENT);

        foreach ($this->getContentElementsToUpdate() as $record) {
            $connection->update(
                self::TABLE_CONTENT,
                [
                    'CType' => 'indexedsearch_pi2',
                    'list_type' => '',
                ],
                ['uid' => (int)$record['uid']]
            );
        }
    }

    protected function updateBackendUserGroups(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS);

        foreach ($this->getBackendUserGroupsToUpdate() as $record) {
            $fields = GeneralUtility::trimExplode(',', $record['explicit_allowdeny'], true);
            foreach ($fields as $key => $field) {
                if ($field === 'tt_content:list_type:indexedsearch_pi2') {
                    unset($fields[$key]);
                    $fields[] = 'tt_content:CType:indexedsearch_pi2';
                }
            }

            $connection->update(
                self::TABLE_BACKEND_USER_GROUPS,
                [
                    'explicit_allowdeny' => implode(',', array_unique($fields)),
                ],
                ['uid' => (int)$record['uid']]
            );
        }
    }
}
