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

namespace TYPO3\CMS\Linkvalidator\Repository;

use Doctrine\DBAL\Exception\TableNotFoundException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\QueryRestrictions\EditableRestriction;

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
     * @return bool is the link target a broken link
     */
    public function isLinkTargetBrokenLink(string $linkTarget, string $linkType = ''): bool
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(static::TABLE);
            $constraints = [
                $queryBuilder->expr()->eq('url', $queryBuilder->createNamedParameter($linkTarget)),
            ];
            if ($linkType !== '') {
                $constraints[] = $queryBuilder->expr()->eq('link_type', $queryBuilder->createNamedParameter($linkType));
            }

            $queryBuilder
                ->count('uid')
                ->from(static::TABLE)
                ->where(...$constraints);

            return (bool)$queryBuilder
                    ->executeQuery()
                    ->fetchOne();
        } catch (TableNotFoundException $e) {
            return false;
        }
    }

    /**
     * Returns all broken links found on the page record and all records on a page (or multiple pages)
     * grouped by the link_type.
     *
     * @param int[] $pageIds
     * @param array $searchFields [ table => [field1, field2, ...], ...]
     */
    public function getNumberOfBrokenLinksForRecordsOnPages(array $pageIds, array $searchFields): array
    {
        $result = [
            'total' => 0,
        ];

        // We need to do the work in chunks, as it may be quite huge and would hit the one
        // or other limit depending on the used dbms - and we also avoid placeholder usage
        // as they are hard to calculate beforehand because of some magic handling of dbal.
        $maxChunk = PlatformInformation::getMaxBindParameters(
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(static::TABLE)
                ->getDatabasePlatform()
        );
        foreach (array_chunk($pageIds, (int)floor($maxChunk / 3)) as $pageIdsChunk) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(static::TABLE);
            $queryBuilder->getRestrictions()->removeAll();
            if (!$GLOBALS['BE_USER']->isAdmin()) {
                $queryBuilder->getRestrictions()
                    ->add(GeneralUtility::makeInstance(EditableRestriction::class, $searchFields, $queryBuilder));
            }
            $statement = $queryBuilder->select('link_type')
                ->addSelectLiteral($queryBuilder->expr()->count(static::TABLE . '.uid', 'amount'))
                ->from(static::TABLE)
                ->join(
                    static::TABLE,
                    'pages',
                    'pages',
                    $queryBuilder->expr()->eq('record_pid', $queryBuilder->quoteIdentifier('pages.uid'))
                )
                ->where(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->in(
                                'record_uid',
                                $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                            ),
                            $queryBuilder->expr()->eq('table_name', $queryBuilder->quote('pages'))
                        ),
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->in(
                                'record_pid',
                                $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                            ),
                            $queryBuilder->expr()->neq('table_name', $queryBuilder->quote('pages'))
                        )
                    )
                )
                ->groupBy('link_type')
                ->executeQuery();

            while ($row = $statement->fetchAssociative()) {
                if (!isset($result[$row['link_type']])) {
                    $result[$row['link_type']] = 0;
                }
                $result[$row['link_type']] += $row['amount'];
                $result['total'] += $row['amount'];
            }
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
                    $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'table_name',
                    $queryBuilder->createNamedParameter($tableName)
                )
            )
            ->set('needs_recheck', 1)
            ->executeStatement();
    }

    public function removeBrokenLinksForRecord(string $tableName, int $recordUid): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::TABLE);

        $queryBuilder->delete(static::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'record_uid',
                    $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'table_name',
                    $queryBuilder->createNamedParameter($tableName)
                )
            )
            ->executeStatement();
    }

    /**
     * @param int[] $pageIds
     * @param array<int,string> $linkTypes
     */
    public function removeAllBrokenLinksOfRecordsOnPageIds(array $pageIds, array $linkTypes): void
    {
        // We need to do the work in chunks, as it may be quite huge and would hit the one
        // or other limit depending on the used dbms - and we also avoid placeholder usage
        // as they are hard to calculate beforehand because of some magic handling of dbal.
        $maxChunk = PlatformInformation::getMaxBindParameters(
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(static::TABLE)
                ->getDatabasePlatform()
        );
        foreach (array_chunk($pageIds, (int)floor($maxChunk / 3)) as $pageIdsChunk) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(static::TABLE);

            $queryBuilder->delete(static::TABLE)
                ->where(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->in(
                                'record_uid',
                                $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                            ),
                            $queryBuilder->expr()->eq('table_name', $queryBuilder->quote('pages'))
                        ),
                        $queryBuilder->expr()->and(
                            $queryBuilder->expr()->in(
                                'record_pid',
                                $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                            ),
                            $queryBuilder->expr()->neq(
                                'table_name',
                                $queryBuilder->quote('pages')
                            )
                        )
                    ),
                    $queryBuilder->expr()->in(
                        'link_type',
                        $queryBuilder->quoteArrayBasedValueListToStringList($linkTypes)
                    )
                )
                ->executeStatement();
        }
    }

    /**
     * Prepare database query with pageList and keyOpt data.
     *
     * This takes permissions of current BE user into account
     *
     * @param int[] $pageIds Pages to check for broken links
     * @param string[] $linkTypes Link types to validate
     * @param string[] $searchFields table => [fields1, field2, ...], ... : fields in which linkvalidator should
     *   search for broken links
     * @param int[] $languages Allowed languages
     */
    public function getAllBrokenLinksForPages(
        array $pageIds,
        array $linkTypes,
        array $searchFields = [],
        array $languages = []
    ): array {
        $results = [];

        // We need to do the work in chunks, as it may be quite huge and would hit the one
        // or other limit depending on the used dbms - and we also avoid placeholder usage
        // as they are hard to calculate beforehand because of some magic handling of dbal.
        $maxChunk = PlatformInformation::getMaxBindParameters(
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(static::TABLE)
                ->getDatabasePlatform()
        );
        foreach (array_chunk($pageIds, (int)floor($maxChunk / 2)) as $pageIdsChunk) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable(self::TABLE);

            // remove hidden restriction here because we join with pages and checkhidden=1 might be set
            // we already correctly check for hidden / extendToSubpages when checking the links
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

            if (!$GLOBALS['BE_USER']->isAdmin()) {
                $queryBuilder->getRestrictions()
                    ->add(GeneralUtility::makeInstance(EditableRestriction::class, $searchFields, $queryBuilder));
            }

            $constraints = [
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->in(
                            'record_uid',
                            $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                        ),
                        $queryBuilder->expr()->eq('table_name', $queryBuilder->quote('pages'))
                    ),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->in(
                            'record_pid',
                            $queryBuilder->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                        ),
                        $queryBuilder->expr()->neq('table_name', $queryBuilder->quote('pages'))
                    )
                ),
                $queryBuilder->expr()->in(
                    'link_type',
                    $queryBuilder->quoteArrayBasedValueListToStringList($linkTypes)
                ),
            ];

            if ($languages !== []) {
                $constraints[] = $queryBuilder->expr()->in(
                    'language',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList($languages)
                );
            }

            $records = $queryBuilder
                ->select(self::TABLE . '.*')
                ->from(self::TABLE)
                ->join(
                    'tx_linkvalidator_link',
                    'pages',
                    'pages',
                    $queryBuilder->expr()->eq(
                        'tx_linkvalidator_link.record_pid',
                        $queryBuilder->quoteIdentifier('pages.uid')
                    )
                )
                ->where(...$constraints)
                ->orderBy('tx_linkvalidator_link.record_uid')
                ->addOrderBy('tx_linkvalidator_link.uid')
                ->executeQuery()
                ->fetchAllAssociative();
            foreach ($records as &$record) {
                $response = json_decode($record['url_response'], true);
                // Fallback mechanism to still support the old serialized data, could be removed in TYPO3 v12 or later
                if ($response === null) {
                    $response = unserialize($record['url_response'], ['allowed_classes' => false]);
                }
                $record['url_response'] = $response;
                $results[] = $record;
            }
        }
        return $results;
    }

    /**
     * Add broken link to table tx_linkvalidator_link
     *
     * @param array $record
     * @param bool $isValid
     * @param array|null $errorParams
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @todo Make default value of $errorParams [] instead of null and add strict typing in v13
     */
    public function addBrokenLink($record, bool $isValid, array $errorParams = null): void
    {
        $response = ['valid' => $isValid];
        $response['errorParams'] = $errorParams ?? [];
        $record['url_response'] = json_encode($response);
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE)
            ->insert(self::TABLE, $record);
    }
}
