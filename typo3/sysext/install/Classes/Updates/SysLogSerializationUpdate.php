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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('sysLogSerialization')]
class SysLogSerializationUpdate implements UpgradeWizardInterface
{
    use LogDataTrait;
    private const TABLE_NAME = 'sys_log';

    public function getTitle(): string
    {
        return 'Migrate sys_log entries to a JSON formatted value.';
    }

    public function getDescription(): string
    {
        return 'All sys_log_entries are now updated to contain JSON values in the "log_data" field.';
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
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);

        // Perform fast update of a:0:{}, since it evaluates to []
        $connection->update(
            self::TABLE_NAME,
            ['log_data' => '[]'],
            ['log_data' => 'a:0:{}']
        );

        // Perform fast update of a:1:{i:0;s:0:"";}, since it evaluates to [""]
        $connection->update(
            self::TABLE_NAME,
            ['log_data' => '[""]'],
            ['log_data' => 'a:1:{i:0;s:0:"";}']
        );

        $queryBuilder = $this->getPreparedQueryBuilder();
        $result = $queryBuilder
            ->select('uid', 'log_data')
            ->where(
                $queryBuilder->expr()->like('log_data', $queryBuilder->createNamedParameter('a:%'))
            )
            ->executeQuery();
        while ($record = $result->fetchAssociative()) {
            $logData = $this->unserializeLogData($record['log_data'] ?? '');
            $connection->update(
                self::TABLE_NAME,
                ['log_data' => json_encode($logData)],
                ['uid' => (int)$record['uid']]
            );
        }

        return true;
    }

    protected function hasRecordsToUpdate(): bool
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        return (bool)$queryBuilder
            ->count('uid')
            ->where(
                $queryBuilder->expr()->like('log_data', $queryBuilder->createNamedParameter('a:%'))
            )
            ->executeQuery()
            ->fetchOne();
    }

    protected function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->from(self::TABLE_NAME);
        return $queryBuilder;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
