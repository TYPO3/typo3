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
     */
    public function getFilesToClean(int $limit = 0): array
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
                // we need to ensure to search an identifier only for the responsible storage ( placeholder pos 2)
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
                    if ($splFileInfo->getRealPath() === false) {
                        continue;
                    }

                    // prepare identifier for proper lookup
                    $filePath = '/' . mb_substr(
                        PathUtility::stripPathSitePrefix($splFileInfo->getRealPath()),
                        mb_strlen(trim($storageBasePath, '/') . '/')
                    );

                    // reuse prepared statement to find processed files without any processed record entries in matching
                    // storage, using `$filePath` as equal match for field `identifier` and storage uid.
                    $statement->bindValue(1, $filePath);
                    $statement->bindValue(2, $storage->getUid(), Connection::PARAM_INT);
                    if ((int)$statement->executeQuery()->fetchOne() === 0) {
                        $files[] = $splFileInfo;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Find records which reference non-existing files
     */
    public function getRecordsToClean(): array
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $queryBuilder = $this->getQueryBuilderWithoutRestrictions();
        $queryBuilder
            ->select('sfp.storage', 'sfp.identifier', 'sfp.uid')
            ->from(self::TABLE_NAME, 'sfp')
            ->leftJoin(
                'sfp',
                'sys_file_storage',
                'sfs',
                $queryBuilder->expr()->eq(
                    'sfp.storage',
                    $queryBuilder->quoteIdentifier('sfs.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->neq(
                    'sfp.identifier',
                    $queryBuilder->createNamedParameter('')
                ),
                $queryBuilder->expr()->eq(
                    'sfs.driver',
                    $queryBuilder->createNamedParameter(self::DRIVER)
                )
            );

        $results = $queryBuilder->executeQuery();
        $processedToDelete = [];
        while ($processedFile = $results->fetchAssociative()) {
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder->delete(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($recordUids, Connection::PARAM_INT_ARRAY))
            )
            ->executeStatement();
    }

    protected function getQueryBuilderWithoutRestrictions(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }
}
