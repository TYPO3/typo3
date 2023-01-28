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
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     */
    public function __construct(array $searchFields, QueryBuilder $queryBuilder)
    {
        $this->allowedFields = $this->getAllowedFieldsForCurrentUser($searchFields);
        $this->allowedLanguages = $this->getAllowedLanguagesForCurrentUser();
        foreach ($searchFields as $table => $fields) {
            if ($table !== 'pages' && ($GLOBALS['TCA'][$table]['ctrl']['type'] ?? false)) {
                $type = $GLOBALS['TCA'][$table]['ctrl']['type'];
                $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$type]['config'];
                // Check for items
                if ($fieldConfig['type'] === 'select'
                    && is_array($fieldConfig['items'] ?? false)
                    && isset($fieldConfig['authMode'])
                ) {
                    $this->explicitAllowFields[$table][$type] = $this->getExplicitAllowTypesForCurrentUser(
                        $table,
                        $type
                    );
                }
            }
        }
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Gets all allowed language ids for current backend user
     */
    protected function getAllowedLanguagesForCurrentUser(): array
    {
        // Comma-separated list of allowed languages, e.g. "0,1". If empty, user has access to all languages.
        $allowedLanguages = trim($GLOBALS['BE_USER']->groupData['allowed_languages'] ?? '');
        if ($allowedLanguages === '') {
            return [];
        }

        return GeneralUtility::intExplode(',', $allowedLanguages);
    }

    /**
     * Returns the allowed types for the current user. Should only be called if the
     * table has a type field (defined by TCA ctrl => type) which contains 'authMode'.
     *
     * @return string[]
     */
    protected function getExplicitAllowTypesForCurrentUser(string $table, string $typeField): array
    {
        $allowDenyFieldTypes = [];
        $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$typeField]['config'];
        foreach ($fieldConfig['items'] as $iVal) {
            $itemIdentifier = (string)$iVal['value'];
            if ($itemIdentifier === '--div--') {
                continue;
            }
            if ($GLOBALS['BE_USER']->checkAuthMode($table, $typeField, $itemIdentifier)) {
                $allowDenyFieldTypes[] = $itemIdentifier;
            }
        }
        return $allowDenyFieldTypes;
    }

    /**
     * Get allowed table / fieldnames for current backend user.
     * Only consider table / fields in $searchFields
     *
     * @param array $searchFields array of 'table' => ['field1, field2', ....]
     *   in which linkvalidator searches for broken links
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
                $expressionBuilder->or(
                    // broken link is in page and page is editable
                    $expressionBuilder->and(
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->quote('pages')
                        ),
                        QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_EDIT))
                    ),
                    // OR broken link is in content and content is editable
                    $expressionBuilder->and(
                        $expressionBuilder->neq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->quote('pages')
                        ),
                        QueryHelper::stripLogicalOperatorPrefix($GLOBALS['BE_USER']->getPagePermsClause(Permission::CONTENT_EDIT))
                    )
                ),
            ];

            // check if fields are editable
            $additionalWhere = [];
            foreach ($this->allowedFields as $table => $fields) {
                foreach ($fields as $field => $value) {
                    $additionalWhere[] = $expressionBuilder->and(
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->quote($table)
                        ),
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.field',
                            $this->queryBuilder->quote($field)
                        )
                    );
                }
            }
            if ($additionalWhere) {
                $constraints[] = $expressionBuilder->or(...$additionalWhere);
            }
        } else {
            // add a constraint that will always return zero records because there are NO allowed fields
            $constraints[] = $expressionBuilder->isNull('tx_linkvalidator_link.table_name');
        }

        foreach ($this->explicitAllowFields as $table => $field) {
            $additionalWhere = [];
            $additionalWhere[] = $expressionBuilder->and(
                $expressionBuilder->eq(
                    'tx_linkvalidator_link.table_name',
                    $this->queryBuilder->quote($table)
                ),
                $expressionBuilder->in(
                    'tx_linkvalidator_link.element_type',
                    $this->queryBuilder->quoteArrayBasedValueListToStringList(array_unique(current($field) ?: []))
                )
            );
            $additionalWhere[] = $expressionBuilder->neq(
                'tx_linkvalidator_link.table_name',
                $this->queryBuilder->quote($table)
            );
            $constraints[] = $expressionBuilder->or(...$additionalWhere);
        }

        if ($this->allowedLanguages) {
            $additionalWhere = [];
            foreach ($this->allowedLanguages as $langId) {
                $additionalWhere[] = $expressionBuilder->or(
                    $expressionBuilder->eq(
                        'tx_linkvalidator_link.language',
                        $this->queryBuilder->quote($langId, Connection::PARAM_INT)
                    ),
                    $expressionBuilder->eq(
                        'tx_linkvalidator_link.language',
                        $this->queryBuilder->quote(-1, Connection::PARAM_INT)
                    )
                );
            }
            $constraints[] = $expressionBuilder->or(...$additionalWhere);
        }
        // If allowed languages is empty: all languages are allowed, so no constraint in this case

        return $expressionBuilder->and(...$constraints);
    }
}
