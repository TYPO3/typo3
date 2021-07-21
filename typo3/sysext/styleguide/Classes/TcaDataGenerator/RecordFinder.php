<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class contains helper methods to locate uids or pids of specific records
 * in the system.
 */
class RecordFinder
{
    /**
     * Returns a uid list of existing styleguide demo top level pages.
     * These are pages with pid=0 and tx_styleguide_containsdemo set to 'tx_styleguide'.
     * This can be multiple pages if "create" button was clicked multiple times without "delete" in between.
     *
     * @return array
     */
    public function findUidsOfStyleguideEntryPages(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tx_styleguide_containsdemo',
                    $queryBuilder->createNamedParameter('tx_styleguide', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
        $uids = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $uids[] = (int)$row['uid'];
            }
        }
        return $uids;
    }

    /**
     * "Main" tables have a single page they are located on with their possible children.
     * The methods find this page by getting the highest uid of a page where field
     * tx_styleguide_containsdemo is set to given table name.
     *
     * @param string $tableName
     * @return int
     * @throws Exception
     */
    public function findPidOfMainTableRecord(string $tableName): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_containsdemo',
                    $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                )
            )
            ->orderBy('pid', 'DESC')
            ->execute()
            ->fetch();
        if (count($row) !== 1) {
            throw new Exception(
                'Found no page for main table ' . $tableName,
                1457690656
            );
        }
        return (int)$row['uid'];
    }

    /**
     * Find uids of styleguide demo sys_language`s
     *
     * @return array List of uids
     */
    public function findUidsOfDemoLanguages(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder->select('uid')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_isdemorecord',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * Find uids of styleguide demo be_groups
     *
     * @return array List of uids
     */
    public function findUidsOfDemoBeGroups(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder->select('uid')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_isdemorecord',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * Find uids of styleguide demo be_users
     *
     * @return array List of uids
     */
    public function findUidsOfDemoBeUsers(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder->select('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_isdemorecord',
                    $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * Find uids of styleguide static data records
     *
     * @return array List of uids
     */
    public function findUidsOfStaticdata(): array
    {
        $pageUid = $this->findPidOfMainTableRecord('tx_styleguide_staticdata');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_styleguide_staticdata');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder->select('uid')
            ->from('tx_styleguide_staticdata')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
        $result = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $result[] = $row['uid'];
            }
        }
        return $result;
    }

    /**
     * Find the object representation of the demo images in fileadmin/styleguide
     *
     * @return File[]
     */
    public function findDemoFileObjects(string $path = 'styleguide'): array
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();
        $folder = $folder->getSubfolder($path);
        return $folder->getFiles();
    }

    /**
     * Find the demo folder
     *
     * @return Folder
     */
    public function findDemoFolderObject(): Folder
    {
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storage = $storageRepository->findByUid(1);
        $folder = $storage->getRootLevelFolder();
        return $folder->getSubfolder('styleguide');
    }

    /**
     * Get all styleguide frontend page UIDs
     *
     * @param array|string[] $types
     * @return array
     */
    public function findUidsOfFrontendPages(array $types = ['tx_styleguide_frontend_root', 'tx_styleguide_frontend']): array
    {
        $allowedTypes = ['tx_styleguide_frontend_root', 'tx_styleguide_frontend'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder->select('uid')
            ->from('pages');

        foreach ($types as $type) {
            if (!in_array($type, $allowedTypes)) {
                continue;
            }

            $queryBuilder->orWhere(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_containsdemo',
                    $queryBuilder->createNamedParameter((string)$type)
                )
            );
        }

        $rows = $queryBuilder->orderBy('pid', 'DESC')->execute()->fetchAll();
        $result = [];
        if (is_array($rows)) {
            $result = array_column($rows, 'uid');
            sort($result);
        }

        return $result;
    }

    /**
     * Find tt_content by ctype and identifier
     *
     * @param array|string[] $types
     * @param string $identifier
     * @return array
     */
    public function findTtContent(array $types = ['textmedia', 'textpic', 'image', 'uploads'], string $identifier = 'tx_styleguide_frontend'): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder->select('uid', 'pid', 'CType')
            ->from('tt_content')->where(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_containsdemo',
                    $queryBuilder->createNamedParameter($identifier)
                )
            );

        if (!empty($types)) {
            $orX = $queryBuilder->expr()->orX();
            foreach ($types as $type) {
                $orX->add($queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($type)));
            }
            $queryBuilder->andWhere((string)$orX);
        }

        return $queryBuilder->orderBy('uid', 'DESC')->execute()->fetchAll();
    }
}
