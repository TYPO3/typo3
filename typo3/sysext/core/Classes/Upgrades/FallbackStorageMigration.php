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

namespace TYPO3\CMS\Core\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Migrates sys_file records of the removed fallback storage (uid 0) into the
 * best-matching configured file storage. Records whose files reside outside
 * all storages are flagged as missing. Processed file records of the fallback
 * storage are removed, they are regenerated on demand.
 *
 * @since 15.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 * @todo Remove in 17.0 as breaking change.
 */
#[UpgradeWizard('fallbackStorageMigration')]
final readonly class FallbackStorageMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private StorageRepository $storageRepository,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate files of the removed fallback storage (uid 0)';
    }

    public function getDescription(): string
    {
        return sprintf(
            'The virtual "fallback storage" with uid 0 has been removed. This wizard moves %d file record(s) '
            . 'into the file storage their file resides in. File records outside all configured storages are '
            . 'flagged as missing. %d processed file record(s) of the fallback storage are deleted and will be '
            . 'regenerated on demand.',
            $this->getFileRecordsToMigrateCount(),
            $this->getProcessedFileRecordsToDeleteCount()
        );
    }

    public function updateNecessary(): bool
    {
        return $this->getFileRecordsToMigrateCount() > 0
            || $this->getProcessedFileRecordsToDeleteCount() > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function executeUpdate(): bool
    {
        $this->migrateFileRecords();
        $this->connectionPool
            ->getConnectionForTable('sys_file_processedfile')
            ->delete('sys_file_processedfile', ['storage' => 0]);
        return true;
    }

    private function migrateFileRecords(): void
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_file');
        $result = $this->getQueryBuilderForStorageZeroFiles()
            ->select('uid', 'identifier')
            ->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $localPath = (string)$row['identifier'];
            if ($localPath === '') {
                $this->flagAsMissing($connection, (int)$row['uid'], '');
                continue;
            }
            $storageUid = $this->storageRepository->findBestMatchingStorageByLocalPath($localPath);
            $storage = $storageUid > 0 ? $this->storageRepository->findByUid($storageUid) : null;
            if ($storage === null) {
                $this->flagAsMissing($connection, (int)$row['uid'], (string)$row['identifier']);
                continue;
            }
            $identifierHash = $storage->hashFileIdentifier($localPath);
            $existingFileUid = $this->findExistingFileRecord($storageUid, $identifierHash, (int)$row['uid']);
            if ($existingFileUid !== null) {
                // The file is already indexed in its real storage - repoint
                // usages of the fallback storage record and drop the duplicate
                $this->mergeIntoExistingFileRecord((int)$row['uid'], $existingFileUid);
                continue;
            }
            $folderIdentifier = dirname($localPath);
            $connection->update(
                'sys_file',
                [
                    'storage' => $storageUid,
                    'identifier' => $localPath,
                    'identifier_hash' => $identifierHash,
                    'folder_hash' => $storage->hashFileIdentifier($folderIdentifier === '' ? '/' : $folderIdentifier),
                ],
                ['uid' => (int)$row['uid']]
            );
        }
    }

    private function flagAsMissing(Connection $connection, int $fileUid, string $identifier): void
    {
        $defaultStorage = $this->storageRepository->getDefaultStorage();
        $fields = ['missing' => 1];
        if ($defaultStorage !== null) {
            // Attach the record to the default storage, so the record stays
            // instantiable and is displayed with regular missing-file handling.
            // Hashing it like that storage lets its indexer find the record again
            // once the file is moved there.
            $fields['storage'] = $defaultStorage->getUid();
            if ($identifier !== '') {
                $folderIdentifier = dirname($identifier);
                $fields['identifier_hash'] = $defaultStorage->hashFileIdentifier($identifier);
                $fields['folder_hash'] = $defaultStorage->hashFileIdentifier($folderIdentifier === '' ? '/' : $folderIdentifier);
            }
        }
        $connection->update('sys_file', $fields, ['uid' => $fileUid]);
    }

    private function findExistingFileRecord(int $storageUid, string $identifierHash, int $excludeUid): ?int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        $existingUid = $queryBuilder
            ->select('uid')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('identifier_hash', $queryBuilder->createNamedParameter($identifierHash)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($excludeUid, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
        return $existingUid === false ? null : (int)$existingUid;
    }

    private function mergeIntoExistingFileRecord(int $oldFileUid, int $newFileUid): void
    {
        $this->connectionPool
            ->getConnectionForTable('sys_file_reference')
            ->update('sys_file_reference', ['uid_local' => $newFileUid], ['uid_local' => $oldFileUid]);
        $this->connectionPool
            ->getConnectionForTable('sys_file_processedfile')
            ->delete('sys_file_processedfile', ['original' => $oldFileUid]);
        $this->connectionPool
            ->getConnectionForTable('sys_file_metadata')
            ->delete('sys_file_metadata', ['file' => $oldFileUid]);
        $this->connectionPool
            ->getConnectionForTable('sys_file')
            ->delete('sys_file', ['uid' => $oldFileUid]);
    }

    private function getFileRecordsToMigrateCount(): int
    {
        return (int)$this->getQueryBuilderForStorageZeroFiles()
            ->count('uid')
            ->executeQuery()
            ->fetchOne();
    }

    private function getProcessedFileRecordsToDeleteCount(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_processedfile');
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_file_processedfile')
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getQueryBuilderForStorageZeroFiles(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('storage', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
        if ($this->storageRepository->getDefaultStorage() === null) {
            // Without a default storage, records outside all storages are only flagged as
            // missing and keep storage 0, so they must not be considered again
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
        }
        return $queryBuilder;
    }
}
