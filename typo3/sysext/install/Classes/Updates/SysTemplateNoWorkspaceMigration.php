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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('sysTemplateNoWorkspaceMigration')]
final class SysTemplateNoWorkspaceMigration implements UpgradeWizardInterface
{
    private const TABLE_NAME = 'sys_template';

    public function getTitle(): string
    {
        return 'Set workspace records in table "sys_template" to deleted.';
    }

    public function getDescription(): string
    {
        return 'Table "sys_template" is no longer workspace aware.' .
            ' Existing database rows having field "t3ver_wsid" > 0 are set to "deleted" = 1 to not' .
            ' leak into live when the workspace related columns are deleted.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        if (!$this->sysTemplateTableExists() || !$this->sysTemplateT3verWsidFieldExists()) {
            return false;
        }
        $queryBuilder = $this->getPreparedQueryBuilder();
        $numberOfNotDeletedWorkspaceRows = (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
        if ($numberOfNotDeletedWorkspaceRows > 0) {
            return true;
        }
        return false;
    }

    public function executeUpdate(): bool
    {
        if (!$this->sysTemplateTableExists() || !$this->sysTemplateT3verWsidFieldExists()) {
            return true;
        }
        $queryBuilder = $this->getPreparedQueryBuilder();
        $queryBuilder->update(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->set('deleted', 1, true, Connection::PARAM_INT)
            ->executeStatement();
        return true;
    }

    private function sysTemplateTableExists(): bool
    {
        $schemaManager = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)->createSchemaManager();
        return $schemaManager->tablesExist(self::TABLE_NAME);
    }

    private function sysTemplateT3verWsidFieldExists(): bool
    {
        $schemaManager = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)->createSchemaManager();
        $tableColumns = $schemaManager->listTableColumns(self::TABLE_NAME);
        $fieldExists = false;
        foreach ($tableColumns as $column) {
            if ($column->getName() === 't3ver_wsid') {
                $fieldExists = true;
                break;
            }
        }
        return $fieldExists;
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder;
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
