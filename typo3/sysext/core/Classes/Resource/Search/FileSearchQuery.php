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

namespace TYPO3\CMS\Core\Resource\Search;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\ConsistencyRestriction;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\FolderMountsRestriction;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\FolderRestriction;
use TYPO3\CMS\Core\Resource\Search\QueryRestrictions\SearchTermRestriction;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Represents an SQL query to search for files.
 * Acts as facade to a QueryBuilder and comes with factory methods
 * to preconfigure the query for a search demand.
 */
class FileSearchQuery
{
    private const FILES_TABLE = 'sys_file';

    private const FILES_META_TABLE = 'sys_file_metadata';

    private QueryBuilder $queryBuilder;

    /**
     * @var QueryRestrictionInterface[]
     */
    private array $additionalRestrictions = [];

    private ?Result $result = null;

    private TcaSchemaFactory $tcaSchemaFactory;

    public function __construct(?QueryBuilder $queryBuilder = null)
    {
        $this->queryBuilder = $queryBuilder ?? GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::FILES_TABLE);
        $this->tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
    }

    /**
     * Prepares a query based on a search demand to be used to fetch rows.
     */
    public static function createForSearchDemand(FileSearchDemand $searchDemand, ?QueryBuilder $queryBuilder = null): self
    {
        $query = new self($queryBuilder);
        $query->additionalRestriction(
            new SearchTermRestriction($searchDemand, $query->queryBuilder)
        );
        $folder = $searchDemand->getFolder();
        if ($folder !== null) {
            $query->additionalRestriction(
                new FolderRestriction($folder, $searchDemand->isRecursive())
            );
        } else {
            $query->additionalRestriction(
                new FolderMountsRestriction($GLOBALS['BE_USER'])
            );
        }

        $query->queryBuilder->getConcreteQueryBuilder()->select(
            'DISTINCT ' . $query->queryBuilder->quoteIdentifier(self::FILES_TABLE . '.identifier'),
            $query->queryBuilder->quoteIdentifier(self::FILES_TABLE) . '.*',
        );

        if ($searchDemand->getFirstResult() !== null) {
            $query->queryBuilder->setFirstResult($searchDemand->getFirstResult());
        }
        if ($searchDemand->getMaxResults() !== null) {
            $query->queryBuilder->setMaxResults($searchDemand->getMaxResults());
        }

        if ($searchDemand->getOrderings() === null) {
            $schema = $query->tcaSchemaFactory->get(self::FILES_TABLE);
            if ($schema->hasCapability(TcaSchemaCapability::SortByField)) {
                $orderBy = $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName();
            } elseif ($schema->hasCapability(TcaSchemaCapability::DefaultSorting)) {
                $orderBy = $schema->getCapability(TcaSchemaCapability::DefaultSorting)->getValue();
            } else {
                $orderBy = '';
            }
            foreach (QueryHelper::parseOrderBy($orderBy) as [$fieldName, $order]) {
                if (is_string($fieldName) && $fieldName !== '') {
                    // Call add ordering only for valid field names
                    $searchDemand = $searchDemand->addOrdering(self::FILES_TABLE, $fieldName, $order ?? 'ASC');
                }
            }
        }
        foreach ($searchDemand->getOrderings() as [$tableName, $fieldName, $direction]) {
            if (!$query->tcaSchemaFactory->has($tableName)
                || !$query->tcaSchemaFactory->get($tableName)->hasField($fieldName)
                || !in_array($direction, ['ASC', 'DESC'], true)) {
                // This exception is essential to avoid SQL injections based on ordering field names, which could be input controlled by an attacker.
                throw new \RuntimeException(sprintf('Invalid file search ordering given table: "%s", field: "%s", direction: "%s".', $tableName, $fieldName, $direction), 1555850106);
            }
            // Add order by fields to select, to make postgres happy and use random names to make sure to not interfere with file fields
            $query->queryBuilder->getConcreteQueryBuilder()->addSelect(
                ...$query->queryBuilder->quoteIdentifiersForSelect([
                    $tableName . '.' . $fieldName
                    . ' AS '
                    . preg_replace(
                        '/[^a-z0-9]/',
                        '',
                        StringUtility::getUniqueId($tableName . $fieldName)
                    ),
                ])
            );
            $query->queryBuilder->addOrderBy($tableName . '.' . $fieldName, $direction);
        }

        return $query;
    }

    /**
     * Prepares a query based on a search demand to be used to count rows.
     */
    public static function createCountForSearchDemand(FileSearchDemand $searchDemand, ?QueryBuilder $queryBuilder = null): self
    {
        $query = new self($queryBuilder);
        $query->additionalRestriction(
            new SearchTermRestriction($searchDemand, $query->queryBuilder)
        );
        $folder = $searchDemand->getFolder();
        if ($folder !== null) {
            $query->additionalRestriction(
                new FolderRestriction($folder, $searchDemand->isRecursive())
            );
        }

        $query->queryBuilder->getConcreteQueryBuilder()->select(
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
     * @throws \RuntimeException
     */
    public function additionalRestriction(QueryRestrictionInterface $additionalRestriction): void
    {
        $this->ensureQueryNotExecuted();
        $this->additionalRestrictions[get_class($additionalRestriction)] = $additionalRestriction;
    }

    /**
     * @return Result
     */
    public function execute()
    {
        if ($this->result === null) {
            $this->initializeQueryBuilder();
            $this->result = $this->queryBuilder->executeQuery();
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
        if ($this->result !== null) {
            throw new \RuntimeException('Cannot modify file query once it was executed. Create a new query instead.', 1555944032);
        }
    }
}
