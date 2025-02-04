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
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Filters result by a given search term, respecting search fields defined in search demand or in TCA.
 */
class SearchTermRestriction implements QueryRestrictionInterface
{
    public function __construct(
        private readonly FileSearchDemand $searchDemand,
        private readonly QueryBuilder $queryBuilder,
    ) {}

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if (!in_array($tableName, ['sys_file', 'sys_file_metadata'])) {
                continue;
            }
            $constraints[] = $this->makeQuerySearchByTable($tableName, $tableAlias);
        }

        return $expressionBuilder->or(...$constraints);
    }

    /**
     * Build the MySql where clause by table.
     *
     * @param string $tableName Record table name
     */
    private function makeQuerySearchByTable(string $tableName, string $tableAlias): CompositeExpression
    {
        $constraints = [];
        $fieldsToSearchWithin = GeneralUtility::makeInstance(SearchableSchemaFieldsCollector::class)->getFields(
            $tableName,
            $this->searchDemand->getSearchFields()[$tableName] ?? []
        );
        if ($fieldsToSearchWithin->count() > 0) {
            $searchTerm = (string)$this->searchDemand->getSearchTerm();
            $searchTermParts = str_getcsv($searchTerm, ' ', '"', '\\');
            foreach ($searchTermParts as $searchTermPart) {
                $searchTermPart = trim($searchTermPart);
                if ($searchTermPart === '') {
                    continue;
                }
                $constraintsForParts = [];
                $like = '%' . $this->queryBuilder->escapeLikeWildcards($searchTermPart) . '%';
                foreach ($fieldsToSearchWithin as $fieldName => $field) {
                    // Assemble the search condition only if the field makes sense to be searched
                    // Check whether search should be case-sensitive or not
                    if (in_array('case', (array)($field->getConfiguration()['search'] ?? []), true)) {
                        // case sensitive
                        $searchConstraint = $this->queryBuilder->expr()->and(
                            $this->queryBuilder->expr()->like(
                                $tableAlias . '.' . $fieldName,
                                $this->queryBuilder->createNamedParameter($like)
                            )
                        );
                    } else {
                        $searchConstraint = $this->queryBuilder->expr()->and(
                            // case insensitive
                            $this->queryBuilder->expr()->comparison(
                                'LOWER(' . $this->queryBuilder->quoteIdentifier($tableAlias . '.' . $fieldName) . ')',
                                'LIKE',
                                $this->queryBuilder->createNamedParameter(mb_strtolower($like))
                            )
                        );
                    }
                    $constraintsForParts[] = $searchConstraint;
                }
                $constraints[] = $this->queryBuilder->expr()->or(...$constraintsForParts);
            }
        }

        return $this->queryBuilder->expr()->and(...$constraints);
    }
}
