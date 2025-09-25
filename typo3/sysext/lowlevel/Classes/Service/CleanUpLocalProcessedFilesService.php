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

namespace TYPO3\CMS\Lowlevel\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal not part of TYPO3's Core API
 */
class CleanUpLocalProcessedFilesService
{
    protected const TABLE_NAME = 'sys_file_processedfile';
    protected const DRIVER = 'Local';

    /**
     * Find processed files without a reference
     * @param int $limit  Limit (deprecated)
     * @param bool $fullReset  When true also removes entries that have an empty identifier or storage
     */
    public function getFilesToClean(int $limit = 0, bool $fullReset = false): array
    {
        $queryBuilder = $this->getQueryBuilderWithoutRestrictions();
        $localStorages = GeneralUtility::makeInstance(StorageRepository::class)->findByStorageType(self::DRIVER);

        $queryBuilder
            ->count('*')
            ->from(self::TABLE_NAME)
            ->where(
                // processed file identifier (placeholder pos 1)
                $queryBuilder->expr()->eq(
                    'identifier',
                    $queryBuilder->createPositionalParameter('')
                ),
                // we need to ensure to search an identifier only for the responsible storage (placeholder pos 2)
                $queryBuilder->expr()->eq(
                    'storage',
                    $queryBuilder->createPositionalParameter(0, Connection::PARAM_INT)
                )
            );
        $statement = $queryBuilder->prepare();

        $files = [];
        foreach ($localStorages as $storage) {
            $storageBasePath = PathUtility::stripPathSitePrefix($this->getAbsoluteBasePath($storage->getConfiguration()));
            foreach ($storage->getProcessingFolders() as $folder) {
                foreach ($this->getFilesOfFolderRecursive($folder) as $splFileInfo) {
                    if (!$splFileInfo->isFile()) {
                        continue;
                    }

                    // prepare identifier for proper lookup
                    $filePath = '/' . mb_substr(
                        PathUtility::stripPathSitePrefix($splFileInfo->getPathname()),
                        mb_strlen(trim($storageBasePath, '/') . '/')
                    );

                    // The full reset does not need to check the database, it clears all files.
                    if (!$fullReset) {
                        // reuse prepared statement to find processed files without any processed record entries in matching
                        // storage, using `$filePath` as equal match for field `identifier` and storage uid.
                        $statement->bindValue(1, $filePath, Connection::PARAM_STR);
                        $statement->bindValue(2, $storage->getUid(), Connection::PARAM_INT);
                        if ((int)$statement->executeQuery()->fetchOne() === 0) {
                            $files[] = $splFileInfo;
                        }
                    } else {
                        $files[] = $splFileInfo;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Find records which reference non-existing files
     * @param bool $fullReset  When true also removes entries that have an empty identifier or storage
     */
    public function getRecordsToClean(bool $fullReset = false): array
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $queryBuilder = $this->getQueryBuilderWithoutRestrictions();
        $conditions = [
            $queryBuilder->expr()->eq(
                'sfs.driver',
                $queryBuilder->createNamedParameter(self::DRIVER)
            ),
        ];

        if (!$fullReset) {
            $conditions[] = $queryBuilder->expr()->neq(
                'sfp.identifier',
                $queryBuilder->createNamedParameter('')
            );
            $storageJoinCondition = $queryBuilder->expr()->eq(
                'sfp.storage',
                $queryBuilder->quoteIdentifier('sfs.uid')
            );
        } else {
            // When removing all records, also the fallback storage needs to be cleared.
            $storageJoinCondition = $queryBuilder->expr()->gte(
                'sfp.storage',
                0
            );
        }

        $queryBuilder
            ->select('sfp.storage', 'sfp.identifier', 'sfp.uid')
            ->from(self::TABLE_NAME, 'sfp')
            ->leftJoin(
                'sfp',
                'sys_file_storage',
                'sfs',
                $storageJoinCondition
            )
            ->where(...array_values($conditions));

        $results = $queryBuilder->executeQuery();
        $processedToDelete = [];
        while ($processedFile = $results->fetchAssociative()) {
            if ($fullReset) {
                // When removing all records, it does not matter if the storage file exists or not.
                $processedToDelete[] = $processedFile;
                continue;
            }

            $storage = $storageRepository->findByUid((int)$processedFile['storage']);
            $processedPathAndFileIdentifier = (string)$processedFile['identifier'];

            // Storage does no longer have that file => delete entry
            if ($storage !== null && !$storage->hasFile($processedPathAndFileIdentifier)) {
                $processedToDelete[] = $processedFile;
            }
        }

        return $processedToDelete;
    }

    /**
     * Recursive generation of file information for files of the given folder.
     * As this could be a lot of files the generator method is used to reduce memory usage.
     *
     * @return \SplFileInfo[]
     */
    protected function getFilesOfFolderRecursive(Folder $folder): iterable
    {
        try {
            $basePath = $this->getAbsoluteBasePath($folder->getStorage()->getConfiguration());
        } catch (InvalidPathException | InvalidConfigurationException $invalidPathException) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $basePath . $folder->getIdentifier(),
                \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FilesystemIterator::CURRENT_AS_FILEINFO
            ),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        foreach ($iterator as $splFileInfo) {
            if ($splFileInfo instanceof \SplFileInfo && $splFileInfo->isFile()) {
                yield $splFileInfo;
            }
        }
    }

    protected function getAbsoluteBasePath(array $configuration): string
    {
        if (!array_key_exists('basePath', $configuration) || empty($configuration['basePath'])) {
            throw new InvalidConfigurationException(
                'Configuration must contain base path.',
                1640297535
            );
        }

        $absoluteBasePath = $configuration['basePath'];
        if (!empty($configuration['pathType']) && $configuration['pathType'] === 'relative') {
            $relativeBasePath = $configuration['basePath'];
            $absoluteBasePath = Environment::getPublicPath() . '/' . $relativeBasePath;
        }
        $absoluteBasePath = PathUtility::getCanonicalPath($absoluteBasePath);
        $absoluteBasePath = rtrim($absoluteBasePath, '/') . '/';
        if (!is_dir($absoluteBasePath)) {
            throw new InvalidConfigurationException(
                'Base path "' . $absoluteBasePath . '" does not exist or is no directory.',
                1640297526
            );
        }

        return $absoluteBasePath;
    }

    public function deleteRecord(array $recordUids): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $maxBindParameters = PlatformInformation::getMaxBindParameters($connection->getDatabasePlatform());
        $deletedRecords = 0;
        foreach (array_chunk($recordUids, $maxBindParameters) as $chunk) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $deletedRecords += $queryBuilder->delete(self::TABLE_NAME)
                ->where(
                    $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($chunk, Connection::PARAM_INT_ARRAY))
                )
                ->executeStatement();
        }
        return $deletedRecords;
    }

    protected function getQueryBuilderWithoutRestrictions(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }
}
