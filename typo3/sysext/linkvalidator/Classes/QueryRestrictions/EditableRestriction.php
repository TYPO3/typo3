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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EditableRestriction implements QueryRestrictionInterface
{
    /**
     * Specify which database fields the current user is allowed to edit
     */
    protected array $allowedFields = [];

    /**
     * Specify which languages the current user is allowed to edit
     */
    protected array $allowedLanguages = [];

    /**
     * Explicit allow fields
     */
    protected array $explicitAllowFields = [];

    protected QueryBuilder $queryBuilder;

    /**
     * @param array<string, string> $searchFields array of 'table' => 'field1, field2'
     *   in which linkvalidator searches for broken links.
     */
    public function __construct(array $searchFields, QueryBuilder $queryBuilder)
    {
        $this->allowedFields = $this->getAllowedFieldsForCurrentUser($searchFields);
        $this->allowedLanguages = $this->getAllowedLanguagesForCurrentUser();
        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        foreach ($searchFields as $table => $fields) {
            if ($table === 'pages') {
                continue;
            }
            if (!$tcaSchemaFactory->has($table)) {
                continue;
            }
            $schema = $tcaSchemaFactory->get($table);
            $typeField = $schema->getSubSchemaDivisorField();
            if ($typeField === null) {
                continue;
            }
            $fieldConfig = $typeField->getConfiguration();
            if ($fieldConfig === []) {
                // @todo: Benni notes... this needs to be checked within the Schema API!!!
                // $type might be "uid_local:type" for table "sys_file_reference" and then $fieldConfig will be empty
                // in this case we skip because we do not join with the other table and will not have this value
                continue;
            }
            // Check for items
            if ($typeField->getType() === 'select'
                && is_array($fieldConfig['items'] ?? false)
                && isset($fieldConfig['authMode'])
            ) {
                $this->explicitAllowFields[$table][$typeField->getName()] = $this->getExplicitAllowTypesForCurrentUser(
                    $table,
                    $typeField
                );
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
        $allowedLanguages = trim($this->getBackendUser()->groupData['allowed_languages'] ?? '');
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
    protected function getExplicitAllowTypesForCurrentUser(string $table, FieldTypeInterface $typeField): array
    {
        $allowDenyFieldTypes = [];
        $fieldConfig = $typeField->getConfiguration();
        foreach ($fieldConfig['items'] as $iVal) {
            $itemIdentifier = (string)$iVal['value'];
            if ($itemIdentifier === '--div--') {
                continue;
            }
            if ($this->getBackendUser()->checkAuthMode($table, $typeField->getName(), $itemIdentifier)) {
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

        $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        foreach ($searchFields as $table => $fieldList) {
            if (!$this->getBackendUser()->isAdmin() && !$this->getBackendUser()->check('tables_modify', $table)) {
                // table not allowed
                continue;
            }
            if (!$tcaSchemaFactory->has($table)) {
                continue;
            }
            $schema = $tcaSchemaFactory->get($table);
            foreach ($fieldList as $field) {
                if (!$schema->hasField($field)) {
                    continue;
                }
                $field = $schema->getField($field);
                if (!$this->getBackendUser()->isAdmin()
                    && $field->supportsAccessControl()
                    && !$this->getBackendUser()->check('non_exclude_fields', $table . ':' . $field->getName())) {
                    continue;
                }
                $allowedFields[$table][$field->getName()] = true;
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
                        QueryHelper::stripLogicalOperatorPrefix($this->getBackendUser()->getPagePermsClause(Permission::PAGE_EDIT))
                    ),
                    // OR broken link is in content and content is editable
                    $expressionBuilder->and(
                        $expressionBuilder->neq(
                            'tx_linkvalidator_link.table_name',
                            $this->queryBuilder->quote('pages')
                        ),
                        QueryHelper::stripLogicalOperatorPrefix($this->getBackendUser()->getPagePermsClause(Permission::CONTENT_EDIT))
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
                            $this->queryBuilder->quote((string)$table)
                        ),
                        $expressionBuilder->eq(
                            'tx_linkvalidator_link.field',
                            $this->queryBuilder->quote((string)$field)
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
                    $this->queryBuilder->quote((string)$table)
                ),
                $expressionBuilder->in(
                    'tx_linkvalidator_link.element_type',
                    $this->queryBuilder->quoteArrayBasedValueListToStringList(array_unique(current($field) ?: []))
                )
            );
            $additionalWhere[] = $expressionBuilder->neq(
                'tx_linkvalidator_link.table_name',
                $this->queryBuilder->quote((string)$table)
            );
            $constraints[] = $expressionBuilder->or(...$additionalWhere);
        }

        if ($this->allowedLanguages) {
            $additionalWhere = [];
            foreach ($this->allowedLanguages as $langId) {
                $additionalWhere[] = $expressionBuilder->or(
                    $expressionBuilder->eq(
                        'tx_linkvalidator_link.language',
                        $this->queryBuilder->quote((string)$langId)
                    ),
                    $expressionBuilder->eq(
                        'tx_linkvalidator_link.language',
                        $this->queryBuilder->quote('-1')
                    )
                );
            }
            $constraints[] = $expressionBuilder->or(...$additionalWhere);
        }
        // If allowed languages is empty: all languages are allowed, so no constraint in this case

        return $expressionBuilder->and(...$constraints);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
