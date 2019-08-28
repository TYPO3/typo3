<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Resource\Search;

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
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\ConsistencyRestriction;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\FolderMountsRestriction;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\FolderRestriction;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\SearchTermRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents an SQL query to search for files.
 * Acts as facade to a QueryBuilder and comes with factory methods
 * to preconfigure the query for a search demand.
 */
class FileSearchQuery
{
    private const FILES_TABLE = 'sys_file';

    private const FILES_META_TABLE = 'sys_file_metadata';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var QueryRestrictionInterface[]
     */
    private $additionalRestrictions = [];

    /**
     * @var \Doctrine\DBAL\Driver\Statement|int
     */
    private $result;

    public function __construct(QueryBuilder $queryBuilder = null)
    {
        $this->queryBuilder = $queryBuilder ?? GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::FILES_TABLE);
    }

    /**
     * Prepares a query based on a search demand to be used to fetch rows.
     *
     * @param FileSearchDemand $searchDemand
     * @param QueryBuilder|null $queryBuilder
     * @return FileSearchQuery
     */
    public static function createForSearchDemand(FileSearchDemand $searchDemand, QueryBuilder $queryBuilder = null): self
    {
        $query = new self($queryBuilder);
        $query->additionalRestriction(
            new SearchTermRestriction($searchDemand, $query->queryBuilder)
        );
        if ($searchDemand->getFolder()) {
            $query->additionalRestriction(
                new FolderRestriction($searchDemand->getFolder(), $searchDemand->isRecursive())
            );
        } else {
            $query->additionalRestriction(
                new FolderMountsRestriction($GLOBALS['BE_USER'])
            );
        }

        $query->queryBuilder->add(
            'select',
            [
                'DISTINCT ' . $query->queryBuilder->quoteIdentifier(self::FILES_TABLE . '.identifier'),
                $query->queryBuilder->quoteIdentifier(self::FILES_TABLE) . '.*',
            ]
        );

        if ($searchDemand->getFirstResult() !== null) {
            $query->queryBuilder->setFirstResult($searchDemand->getFirstResult());
        }
        if ($searchDemand->getMaxResults() !== null) {
            $query->queryBuilder->setMaxResults($searchDemand->getMaxResults());
        }

        if ($searchDemand->getOrderings() === null) {
            $orderBy = $GLOBALS['TCA'][self::FILES_TABLE]['ctrl']['sortby'] ?: $GLOBALS['TCA'][self::FILES_TABLE]['ctrl']['default_sortby'];
            foreach (QueryHelper::parseOrderBy((string)$orderBy) as [$fieldName, $order]) {
                $searchDemand = $searchDemand->addOrdering(self::FILES_TABLE, $fieldName, $order);
            }
        }
        foreach ($searchDemand->getOrderings() as [$tableName, $fieldName, $direction]) {
            if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]) || !in_array($direction, ['ASC', 'DESC'], true)) {
                // This exception is essential to avoid SQL injections based on ordering field names, which could be input controlled by an attacker.
                throw new \RuntimeException(sprintf('Invalid file search ordering given table: "%s", field: "%s", direction: "%s".', $tableName, $fieldName, $direction), 1555850106);
            }
            // Add order by fields to select, to make postgres happy and use random names to make sure to not interfere with file fields
            $query->queryBuilder->add(
                'select',
                $query->queryBuilder->quoteIdentifiersForSelect([
                    $tableName . '.' . $fieldName
                    . ' AS '
                    . preg_replace(
                        '/[^a-z0-9]/',
                        '',
                        uniqid($tableName . $fieldName, true)
                    )
                ]),
                true
            );
            $query->queryBuilder->addOrderBy($tableName . '.' . $fieldName, $direction);
        }

        return $query;
    }

    /**
     * Prepares a query based on a search demand to be used to count rows.
     *
     * @param FileSearchDemand $searchDemand
     * @param QueryBuilder|null $queryBuilder
     * @return FileSearchQuery
     */
    public static function createCountForSearchDemand(FileSearchDemand $searchDemand, QueryBuilder $queryBuilder = null): self
    {
        $query = new self($queryBuilder);
        $query->additionalRestriction(
            new SearchTermRestriction($searchDemand, $query->queryBuilder)
        );
        if ($searchDemand->getFolder()) {
            $query->additionalRestriction(
                new FolderRestriction($searchDemand->getFolder(), $searchDemand->isRecursive())
            );
        }

        $query->queryBuilder->add(
            'select',
            'COUNT(DISTINCT ' . $query->queryBuilder->quoteIdentifier(self::FILES_TABLE . '.identifier') . ')'
        );

        return $query;
    }

    /**
     * Limit the result set of identifiers, by adding further SQL restrictions.
     * Note that no further restrictions can be added once result is initialized,
     * by starting the iteration over the result.
     * Can be accessed by subclasses to add further restrictions to the query.
     *
     * @param QueryRestrictionInterface $additionalRestriction
     * @throws |RuntimeException
     */
    public function additionalRestriction(QueryRestrictionInterface $additionalRestriction): void
    {
        $this->ensureQueryNotExecuted();
        $this->additionalRestrictions[get_class($additionalRestriction)] = $additionalRestriction;
    }

    public function execute()
    {
        if ($this->result === null) {
            $this->initializeQueryBuilder();
            $this->result = $this->queryBuilder->execute();
        }

        return $this->result;
    }

    /**
     * Create and initialize QueryBuilder for SQL based file search.
     * Can be accessed by subclasses for example to add further joins to the query.
     */
    private function initializeQueryBuilder(): void
    {
        $this->queryBuilder->from(self::FILES_TABLE);
        $this->queryBuilder->join(
            self::FILES_TABLE,
            self::FILES_META_TABLE,
            self::FILES_META_TABLE,
            $this->queryBuilder->expr()->eq(self::FILES_META_TABLE . '.file', $this->queryBuilder->quoteIdentifier(self::FILES_TABLE . '.uid'))
        );

        $restrictionContainer = $this->queryBuilder->getRestrictions()
            ->add(new ConsistencyRestriction($this->queryBuilder));
        foreach ($this->additionalRestrictions as $additionalRestriction) {
            $restrictionContainer->add($additionalRestriction);
        }
    }

    private function ensureQueryNotExecuted(): void
    {
        if ($this->result) {
            throw new \RuntimeException('Cannot modify file query once it was executed. Create a new query instead.', 1555944032);
        }
    }
}
