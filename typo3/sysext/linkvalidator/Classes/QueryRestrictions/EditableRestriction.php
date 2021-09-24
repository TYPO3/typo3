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

namespace TYPO3\CMS\Linkvalidator\QueryRestrictions;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

class EditableRestriction implements QueryRestrictionInterface
{
    /**
     * Specify which database fields the current user is allowed to edit
     *
     * @var array
     */
    protected $allowedFields = [];

    /**
     * Specify which languages the current user is allowed to edit
     *
     * @var array
     */
    protected $allowedLanguages = [];

    /**
     * Explicit allow fields
     *
     * @var array
     */
    protected $explicitAllowFields = [];

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @param array<string, string> $searchFields array of 'table' => 'field1, field2'
     *   in which linkvalidator searches for broken links.
     * @param QueryBuilder $queryBuilder
     */
    public function __construct(array $searchFields, QueryBuilder $queryBuilder)
    {
        $this->allowedFields = $this->getAllowedFieldsForCurrentUser($searchFields);
        $this->allowedLanguages = $this->getAllowedLanguagesForCurrentUser();
        foreach ($searchFields as $table => $fields) {
            if ($table !== 'pages' && ($GLOBALS['TCA'][$table]['ctrl']['type'] ?? false)) {
                $type = $GLOBALS['TCA'][$table]['ctrl']['type'];
                $this->explicitAllowFields[$table][$type] = $this->getExplicitAllowFieldsForCurrentUser($table, $type);
            }
        }
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Gets all allowed language ids for current backend user
     *
     * @return array
     */
    protected function getAllowedLanguagesForCurrentUser(): array
    {
        if (!(is_string($GLOBALS['BE_USER']->groupData['allowed_languages'] ?? false))) {
            return [];
        }

        return array_map('intval', explode(',', $GLOBALS['BE_USER']->groupData['allowed_languages']));
    }

    protected function getExplicitAllowFieldsForCurrentUser(string $table, string $field): array
    {
        $allowDenyOptions = [];
        $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        // Check for items
        if ($fieldConfig['type'] === 'select' && is_array($fieldConfig['items'] ?? false)) {
            foreach ($fieldConfig['items'] as $iVal) {
                $itemIdentifier = (string)$iVal[1];
                if ($GLOBALS['BE_USER']->checkAuthMode($table, $field, $itemIdentifier, $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'])) {
                    $allowDenyOptions[] = $itemIdentifier;
                }
            }
        }
        return $allowDenyOptions;
    }

    /**
     * Get allowed table / fieldnames for current backend user.
     * Only consider table / fields in $searchFields
     *
     * @param array $searchFields array of 'table' => ['field1, field2', ....]
     *   in which linkvalidator searches for broken links
     * @return array
     */
    protected function getAllowedFieldsForCurrentUser(array $searchFields = []): array
    {
        if (!$searchFields) {
            return [];
        }

        $allowedFields = [];

        foreach ($searchFields as $table => $fieldList) {
            if (!$GLOBALS['BE_USER']->isAdmin() && !$GLOBALS['BE_USER']->check('tables_modify', $table)) {
                // table not allowed
                continue;
            }
            foreach ($fieldList as $field) {
                $isExcludeField = $GLOBALS['TCA'][$table]['columns'][$field]['exclude'] ?? false;
                if (!$GLOBALS['BE_USER']->isAdmin()
                    && $isExcludeField
                    && !$GLOBALS['BE_USER']->check('non_exclude_fields', $table . ':' . $field)) {
                    continue;
                }
                $allowedFields[$table][$field] = true;
            }
        }
        return $allowedFields;
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];

        if ($this->allowedFields) {
            $constraints = [
                $expressionBuilder->orX(
                // broken link is in page and page is editable
                    $expressionBuilder->andX(
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->createNamedParameter('pages')
                        ),
                        QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_EDIT))
                    ),
                    // OR broken link is in content and content is editable
                    $expressionBuilder->andX(
                        $expressionBuilder->neq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->createNamedParameter('pages')
                        ),
                        QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(Permission::CONTENT_EDIT))
                    )
                ),
            ];

            // check if fields are editable
            $additionalWhere = [];
            foreach ($this->allowedFields as $table => $fields) {
                foreach ($fields as $field => $value) {
                    $additionalWhere[] = $expressionBuilder->andX(
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->createNamedParameter($table)
                        ),
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.field',
                            $this->queryBuilder->createNamedParameter($field)
                        )
                    );
                }
            }
            if ($additionalWhere) {
                $constraints[] = $expressionBuilder->orX(...$additionalWhere);
            }
        } else {
            // add a constraint that will always return zero records because there are NO allowed fields
            $constraints[] = $expressionBuilder->isNull('tx_linkvalidator_link.table_name');
        }

        foreach ($this->explicitAllowFields as $table => $field) {
            $additionalWhere = [];
            $additionalWhere[] = $expressionBuilder->andX(
                $expressionBuilder->eq(
                    'tx_linkvalidator_link.table_name',
                    $this->queryBuilder->createNamedParameter($table)
                ),
                $expressionBuilder->in(
                    'tx_linkvalidator_link.element_type',
                    $this->queryBuilder->createNamedParameter(
                        array_unique(current($field)),
                        Connection::PARAM_STR_ARRAY
                    )
                )
            );
            $additionalWhere[] = $expressionBuilder->neq(
                'tx_linkvalidator_link.table_name',
                $this->queryBuilder->createNamedParameter($table)
            );
            if ($additionalWhere) {
                $constraints[] = $expressionBuilder->orX(...$additionalWhere);
            }
        }

        if ($this->allowedLanguages) {
            $additionalWhere = [];
            foreach ($this->allowedLanguages as $langId) {
                $additionalWhere[] = $expressionBuilder->orX(
                    $expressionBuilder->eq(
                        'tx_linkvalidator_link.language',
                        $this->queryBuilder->createNamedParameter($langId, \PDO::PARAM_INT)
                    ),
                    $expressionBuilder->eq(
                        'tx_linkvalidator_link.language',
                        $this->queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                    )
                );
            }
            $constraints[] = $expressionBuilder->orX(...$additionalWhere);
        }
        // If allowed languages is empty: all languages are allowed, so no constraint in this case

        return $expressionBuilder->andX(...$constraints);
    }
}
