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

namespace TYPO3\CMS\Core\Resource\Search\QueryRestrictions;

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Filters result by a given search term, respecting search fields defined in search demand or in TCA.
 */
class SearchTermRestriction implements QueryRestrictionInterface
{
    /**
     * @var FileSearchDemand
     */
    private $searchDemand;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function __construct(FileSearchDemand $searchDemand, QueryBuilder $queryBuilder)
    {
        $this->searchDemand = $searchDemand;
        $this->queryBuilder = $queryBuilder;
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if (!in_array($tableName, ['sys_file', 'sys_file_metadata'])) {
                continue;
            }
            $constraints[] = $this->makeQuerySearchByTable($tableName, $tableAlias);
        }

        return $expressionBuilder->orX(...$constraints);
    }

    /**
     * Build the MySql where clause by table.
     *
     * @param string $tableName Record table name
     * @param string $tableAlias
     * @return CompositeExpression
     */
    private function makeQuerySearchByTable(string $tableName, string $tableAlias): CompositeExpression
    {
        $fieldsToSearchWithin = $this->extractSearchableFieldsFromTable($tableName);
        $searchTerm = (string)$this->searchDemand->getSearchTerm();
        $constraints = [];

        $searchTermParts = str_getcsv($searchTerm, ' ');
        foreach ($searchTermParts as $searchTermPart) {
            $searchTermPart = trim($searchTermPart);
            if ($searchTermPart === '') {
                continue;
            }
            $constraintsForParts = [];
            $like = '%' . $this->queryBuilder->escapeLikeWildcards($searchTermPart) . '%';
            foreach ($fieldsToSearchWithin as $fieldName) {
                if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = $fieldConfig['eval'] ?? '';

                // Check whether search should be case-sensitive or not
                if (in_array('case', (array)($fieldConfig['search'] ?? []), true)) {
                    // case sensitive
                    $searchConstraint = $this->queryBuilder->expr()->andX(
                        $this->queryBuilder->expr()->like(
                            $tableAlias . '.' . $fieldName,
                            $this->queryBuilder->createNamedParameter($like, \PDO::PARAM_STR)
                        )
                    );
                } else {
                    $searchConstraint = $this->queryBuilder->expr()->andX(
                    // case insensitive
                        $this->queryBuilder->expr()->comparison(
                            'LOWER(' . $this->queryBuilder->quoteIdentifier($tableAlias . '.' . $fieldName) . ')',
                            'LIKE',
                            $this->queryBuilder->createNamedParameter(mb_strtolower($like), \PDO::PARAM_STR)
                        )
                    );
                }

                // Assemble the search condition only if the field makes sense to be searched
                if ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || ($fieldType === 'input' && (!$evalRules || !preg_match('/\b(?:date|time|int)\b/', $evalRules)))
                ) {
                    $constraintsForParts[] = $searchConstraint;
                }
            }
            $constraints[] = $this->queryBuilder->expr()->orX(...$constraintsForParts);
        }

        return $this->queryBuilder->expr()->andX(...$constraints);
    }

    /**
     * Get all fields from given table where we can search for.
     *
     * @param string $tableName Name of the table for which to get the searchable fields
     * @return array
     */
    private function extractSearchableFieldsFromTable(string $tableName): array
    {
        if ($searchFields = $this->searchDemand->getSearchFields()) {
            if (empty($searchFields[$tableName])) {
                return [];
            }
            foreach ($searchFields[$tableName] as $searchField) {
                if (!isset($GLOBALS['TCA'][$tableName]['columns'][$searchField])) {
                    throw new \RuntimeException(sprintf('Cannot use search field "%s" because it is not defined in TCA.', $searchField), 1556367071);
                }
            }

            return $searchFields;
        }
        $fieldListArray = [];
        // Get the list of fields to search in from the TCA, if any
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['searchFields'])) {
            $fieldListArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'], true);
        }

        return $fieldListArray;
    }
}
