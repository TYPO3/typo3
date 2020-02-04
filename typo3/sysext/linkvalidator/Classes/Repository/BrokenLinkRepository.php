<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Linkvalidator\Repository;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for finding broken links that were detected previously.
 */
class BrokenLinkRepository
{
    protected const TABLE = 'tx_linkvalidator_link';

    /**
     * Check if linkTarget is in list of broken links.
     *
     * @param string $linkTarget Url to check for. Can be a URL (for external links)
     *   a page uid (for db links), a file reference (for file links), etc.
     * @return int the amount of usages this broken link is used in this installation
     */
    public function getNumberOfBrokenLinks(string $linkTarget): int
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(static::TABLE);
            $queryBuilder
                ->count('uid')
                ->from(static::TABLE)
                ->where(
                    $queryBuilder->expr()->eq('url', $queryBuilder->createNamedParameter($linkTarget))
                );
            return (int)$queryBuilder
                    ->execute()
                    ->fetchColumn(0);
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $e) {
            return 0;
        }
    }

    /**
     * Returns all broken links found on the page record and all records on a page (or multiple pages)
     * grouped by the link_type.
     *
     * @param array $pageIds
     * @return array
     */
    public function getNumberOfBrokenLinksForRecordsOnPages(array $pageIds): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        $statement = $queryBuilder->select('link_type')
            ->addSelectLiteral($queryBuilder->expr()->count('uid', 'amount'))
            ->from(static::TABLE)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'record_uid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->eq('table_name', $queryBuilder->createNamedParameter('pages'))
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'record_pid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->neq('table_name', $queryBuilder->createNamedParameter('pages'))
                    )
                )
            )
            ->groupBy('link_type')
            ->execute();

        $result = [];
        while ($row = $statement->fetch()) {
            $result[$row['link_type']] = $row['amount'];
        }
        return $result;
    }

    public function setNeedsRecheckForRecord(int $recordUid, string $tableName): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE);

        $queryBuilder->update(static::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'table_name',
                    $queryBuilder->createNamedParameter($tableName)
                )
            )
            ->set('needs_recheck', 1)
            ->execute();
    }

    public function removeBrokenLinksForRecord(string $tableName, int $recordUid): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE);

        $queryBuilder->delete(static::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_uid',
                    $queryBuilder->createNamedParameter($recordUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'table_name',
                    $queryBuilder->createNamedParameter($tableName)
                )
            )
            ->execute();
    }

    public function removeAllBrokenLinksOfRecordsOnPageIds(array $pageIds, array $linkTypes): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE);

        $queryBuilder->delete(static::TABLE)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'record_uid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->eq('table_name', $queryBuilder->createNamedParameter('pages'))
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'record_pid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->neq(
                            'table_name',
                            $queryBuilder->createNamedParameter('pages')
                        )
                    )
                ),
                $queryBuilder->expr()->in(
                    'link_type',
                    $queryBuilder->createNamedParameter($linkTypes, Connection::PARAM_STR_ARRAY)
                )
            )
            ->execute();
    }

    /**
     * Prepare database query with pageList and keyOpt data.
     *
     * @param int[] $pageIds Pages to check for broken links
     * @param string[] $linkTypes Link types to validate
     * @return array
     */
    public function getAllBrokenLinksForPages(array $pageIds, array $linkTypes): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::TABLE);
        $records = $queryBuilder
            ->select('*')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'record_uid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->eq('table_name', $queryBuilder->createNamedParameter('pages'))
                    ),
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->in(
                            'record_pid',
                            $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                        ),
                        $queryBuilder->expr()->neq('table_name', $queryBuilder->createNamedParameter('pages'))
                    )
                ),
                $queryBuilder->expr()->in(
                    'link_type',
                    $queryBuilder->createNamedParameter($linkTypes, Connection::PARAM_STR_ARRAY)
                )
            )
            ->orderBy('record_uid')
            ->addOrderBy('uid')
            ->execute()
            ->fetchAll();
        foreach ($records as &$record) {
            $response = json_decode($record['url_response'], true);
            // Fallback mechansim to still support the old serialized data, could be removed in TYPO3 v12 or later
            if ($response === null) {
                $response = unserialize($record['url_response'], ['allowed_classes' => false]);
            }
            $record['url_response'] = $response;
        }
        return $records;
    }

    public function addBrokenLink($record, bool $isValid, array $errorParams = null): void
    {
        $response = ['valid' => $isValid];
        if ($errorParams) {
            $response['errorParams'] = $errorParams;
        }
        $record['url_response'] = json_encode($response);
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->insert(self::TABLE, $record);
    }
}
