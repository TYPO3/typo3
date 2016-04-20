<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query;

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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Builder for SQL query constraints based on TCA settings.
 * The resulting composite expressions can be added to a query
 * being built using the QueryBuilder object.
 *
 * The restrictions being built by this class are to be used for all
 * select queries done by the QueryBuilder to avoid returning data
 * that should not be available to the caller based on the current
 * TYPO3 context.
 *
 * Restrictions that will be created can be configured using the
 * QuerySettings on the main QueryBuilder object.
 *
 * WARNING: This code has cross cutting concerns as it requires access
 * to the TypoScriptFrontEndController and $GLOBALS['TCA'] to build the
 * right queries.
 */
class QueryRestrictionBuilder
{
    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected $pageRepository;

    /**
     * @var \TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * @var \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    protected $queryContext;

    /**
     * @var string[]
     */
    protected $queriedTables = [];

    /**
     * Initializes a new QueryBuilder.
     *
     * @param string[] $queriedTables
     * @param ExpressionBuilder $expressionBuilder The ExpressionBuilder with which to create restrictions
     * @param \TYPO3\CMS\Core\Database\Query\QueryContext $queryContext
     */
    public function __construct(
        array $queriedTables,
        ExpressionBuilder $expressionBuilder,
        QueryContext $queryContext = null
    ) {
        $this->queriedTables = $queriedTables;
        $this->expressionBuilder = $expressionBuilder;
        $this->queryContext = $queryContext ?? GeneralUtility::makeInstance(QueryContext::class);
    }

    /**
     * Returns a composite expression to add visibility restrictions for
     * the selected tables based on the current context (FE/BE).
     *
     * You need to check if any conditions are added to the CompositeExpression
     * before adding it to your query using `->count()`.
     *
     * @return CompositeExpression
     */
    public function getVisibilityConstraints(): CompositeExpression
    {
        switch ($this->queryContext->getContext()) {
            case QueryContextType::FRONTEND:
                return $this->getFrontendVisibilityRestrictions();
            case QueryContextType::BACKEND:
            case QueryContextType::BACKEND_NO_VERSIONING_PLACEHOLDERS:
                return $this->getBackendVisibilityConstraints();
            case QueryContextType::UNRESTRICTED:
                return $this->expressionBuilder->andX();
            default:
                throw new \RuntimeException(
                    'Unknown TYPO3 Context / Request type: "' . TYPO3_REQUESTTYPE . '".',
                    1459708283
                );
        }
    }

    /**
     * Returns a composite expression takeing into account visibility restrictions
     * imposed by enableFields, versioning/workspaces and deletion.
     *
     * @return \TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression
     * @throws \LogicException
     */
    protected function getFrontendVisibilityRestrictions(): CompositeExpression
    {
        $queryContext = $this->queryContext;
        $ignoreEnableFields = $queryContext->getIgnoreEnableFields();
        $includeDeleted = $queryContext->getIncludeDeleted();

        if (!$ignoreEnableFields && $includeDeleted) {
            throw new \LogicException(
                'The query settings "ignoreEnableFields=FALSE" and "includeDeleted=TRUE" can not be used together '
                . 'in frontend context.',
                1459690516
            );
        }

        $constraints = [];
        foreach ($this->queriedTables as $tableName => $tableAlias) {
            $tableConfig = $queryContext->getTableConfig($tableName);
            if (!$ignoreEnableFields && !$includeDeleted) {
                $constraint = $this->getEnableFieldConstraints(
                    $tableName,
                    $tableAlias,
                    $queryContext->getIncludeHiddenForTable($tableName),
                    [],
                    $queryContext->getIncludeVersionedRecords()
                );
                if ($constraint->count() !== 0) {
                    $constraints[] = $constraint;
                }
            } elseif ($ignoreEnableFields && !$includeDeleted) {
                if (!empty($queryContext->getIgnoredEnableFieldsForTable($tableName))) {
                    $constraint = $this->getEnableFieldConstraints(
                        $tableName,
                        $tableAlias,
                        $queryContext->getIncludeHiddenForTable($tableName),
                        $queryContext->getIgnoredEnableFieldsForTable($tableName),
                        $queryContext->getIncludeVersionedRecords()
                    );
                    if ($constraint->count() !== 0) {
                        $constraints[] = $constraint;
                    }
                } elseif (!empty($tableConfig['delete'])) {
                    $tablePrefix = empty($tableAlias) ? $tableName : $tableAlias;
                    $constraints[] = $this->expressionBuilder->eq(
                        $tablePrefix . '.' . $tableConfig['delete'],
                        0
                    );
                }
            }
        }

        return $this->expressionBuilder->andX(...$constraints);
    }

    /**
     * Returns a composite expression to restrict access to records for the backend context.
     *
     * @return CompositeExpression
     * @todo: Lots of code duplication, check how/if this can be merged with the "getEnableFieldConstraints"
     * @todo: after the test cases are done for backend and frontend.
     */
    protected function getBackendVisibilityConstraints(): CompositeExpression
    {
        $queryContext = $this->queryContext;
        $ignoreEnableFields = $queryContext->getIgnoreEnableFields();
        $includeDeleted = $queryContext->getIncludeDeleted();

        $constraints = [];
        $expressionBuilder = $this->expressionBuilder;

        foreach ($this->queriedTables as $tableName => $tableAlias) {
            $tableConfig = $queryContext->getTableConfig($tableName);
            $tablePrefix = empty($tableAlias) ? $tableName : $tableAlias;

            if (empty($tableConfig)) {
                // No restrictions for this table, not configured by TCA
                continue;
            }

            if (!$ignoreEnableFields && is_array($tableConfig['enablecolumns'])) {
                $enableColumns = $tableConfig['enablecolumns'];

                if (isset($enableColumns['disabled'])) {
                    $constraints[] = $expressionBuilder->eq(
                        $tablePrefix . '.' . $enableColumns['disabled'],
                        0
                    );
                }
                if ($enableColumns['starttime']) {
                    $constraints[] = $expressionBuilder->lte(
                        $tablePrefix . '.' . $enableColumns['starttime'],
                        $queryContext->getAccessTime()
                    );
                }
                if ($enableColumns['endtime']) {
                    $fieldName = $tablePrefix . '.' . $enableColumns['endtime'];
                    $constraints[] = $expressionBuilder->orX(
                        $expressionBuilder->eq($fieldName, 0),
                        $expressionBuilder->gt($fieldName, $queryContext->getAccessTime())
                    );
                }
            }

            if (!$includeDeleted && !empty($tableConfig['delete'])) {
                $constraints[] = $this->expressionBuilder->eq(
                    $tablePrefix . '.' . $tableConfig['delete'],
                    0
                );
            }

            if ($queryContext->getContext() === QueryContextType::BACKEND_NO_VERSIONING_PLACEHOLDERS
                && !empty($tableConfig['versioningWS'])
            ) {
                $constraints[] = $this->expressionBuilder->orX(
                    $expressionBuilder->lte(
                        $tablePrefix . '.t3ver_state',
                        new VersionState(VersionState::DEFAULT_STATE)
                    ),
                    $expressionBuilder->eq($tablePrefix . '.t3ver_wsid', $queryContext->getCurrentWorkspace())
                );
            }
        }

        return $expressionBuilder->andX(...$constraints);
    }

    /**
     * @param string $tableName The table name to query
     * @param string|null $tableAlias The table alias to use for constraints. $tableName used when empty.
     * @param bool $showHidden Select hidden records
     * @param string[] $ignoreFields Names of enable columns to be ignored
     * @param bool $noVersionPreview If set, enableFields will be applied regardless of any versioning preview
     *                               settings which might otherwise disable enableFields
     * @return \TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression
     */
    protected function getEnableFieldConstraints(
        string $tableName,
        string $tableAlias = null,
        bool $showHidden = false,
        array $ignoreFields = [],
        bool $noVersionPreview = false
    ): CompositeExpression {
        $queryContext = $this->queryContext;
        $tableConfig = $queryContext->getTableConfig($tableName);

        if (empty($tableConfig)) {
            // No restrictions for this table, not configured by TCA
            return $this->expressionBuilder->andX();
        }

        $tablePrefix = empty($tableAlias) ? $tableName : $tableAlias;

        $constraints = [];
        $expressionBuilder = $this->expressionBuilder;

        // Restrict based on deleted flag of records
        if (!empty($tableConfig['delete'])) {
            $constraints[] = $expressionBuilder->eq($tablePrefix . '.deleted', 0);
        }

        // Restrict based on Workspaces / Versioning
        if (!empty($tableConfig['versioningWS'])) {
            if (!$queryContext->getIncludePlaceholders()) {
                // Filter out placeholder records (new/moved/deleted items) in case we are NOT in a versioning preview
                // (This means that means we are online!)
                $constraints[] = $expressionBuilder->lte(
                    $tablePrefix . '.t3ver_state',
                    new VersionState(VersionState::DEFAULT_STATE)
                );
            } elseif ($tableName !== 'pages') {
                // Show only records of the live and current workspace in case we are in a versioning preview
                $constraints[] = $expressionBuilder->orX(
                    $expressionBuilder->eq($tablePrefix . '.t3ver_wsid', 0),
                    $expressionBuilder->eq($tablePrefix . '.t3ver_wsid', $queryContext->getCurrentWorkspace())
                );
            }

            // Filter out versioned records
            if (!$noVersionPreview && !in_array('pid', $ignoreFields)) {
                $constraints[] = $expressionBuilder->neq($tablePrefix . '.pid', -1);
            }
        }

        // Restrict based on enable fields. In case of versioning-preview, enableFields are ignored
        // and later checked in versionOL().
        if (is_array($tableConfig['enablecolumns'])
            && (!$queryContext->getIncludePlaceholders() || empty($tableConfig['versioningWS']) || $noVersionPreview)
        ) {
            $enableColumns = $tableConfig['enablecolumns'];

            // Filter out disabled records
            if (isset($enableColumns['disabled']) && !$showHidden && !in_array('disabled', $ignoreFields)) {
                $constraints[] = $expressionBuilder->eq(
                    $tablePrefix . '.' . $enableColumns['disabled'],
                    0
                );
            }

            // Filter out records where the starttime has not yet been reached.
            if (isset($enableColumns['starttime']) && !in_array('starttime', $ignoreFields)) {
                $constraints[] = $expressionBuilder->lte(
                    $tablePrefix . '.' . $enableColumns['starttime'],
                    $queryContext->getAccessTime()
                );
            }

            // Filter out records with a set endtime where the time is in the past.
            if (isset($enableColumns['endtime']) && !in_array('endtime', $ignoreFields)) {
                $constraints[] = $expressionBuilder->orX(
                    $expressionBuilder->eq($tablePrefix . '.' . $enableColumns['endtime'], 0),
                    $expressionBuilder->gt(
                        $tablePrefix . '.' . $enableColumns['endtime'],
                        $queryContext->getAccessTime()
                    )
                );
            }

            // Filter out records based on the frondend user groups
            if ($enableColumns['fe_group'] && !in_array('fe_group', $ignoreFields)) {
                $constraints[] = $this->getFrontendUserGroupConstraints(
                    $tablePrefix,
                    $enableColumns['fe_group']
                );
            }

            // Call hook functions for additional enableColumns
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'])) {
                $_params = [
                    'table' => $tableName,
                    'tableAlias' => $tableAlias,
                    'tablePrefix' => $tablePrefix,
                    'show_hidden' => $showHidden,
                    'ignore_array' => $ignoreFields,
                    'ctrl' => $tableConfig
                ];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'] as $_funcRef) {
                    $constraint = GeneralUtility::callUserFunction($_funcRef, $_params, $this);

                    $constraints[] = preg_replace('/^(?:AND[[:space:]]*)+/i', '', trim($constraint));
                }
            }
        }

        return $expressionBuilder->andX(...$constraints);
    }

    /**
     * @param string $tableName The table name to build constraints for
     * @param string $fieldName The field name to build constraints for
     *
     * @return CompositeExpression
     */
    protected function getFrontendUserGroupConstraints(string $tableName, string $fieldName): CompositeExpression
    {
        $expressionBuilder = $this->expressionBuilder;
        // Allow records where no group access has been configured (field values NULL, 0 or empty string)
        $constraints = [
            $expressionBuilder->isNull($tableName . '.' . $fieldName),
            $expressionBuilder->eq($tableName . '.' . $fieldName, $expressionBuilder->literal('')),
            $expressionBuilder->eq($tableName . '.' . $fieldName, $expressionBuilder->literal('0')),
        ];

        foreach ($this->queryContext->getMemberGroups() as $value) {
            $constraints[] = $expressionBuilder->inSet(
                $tableName . '.' . $fieldName,
                $expressionBuilder->literal((string)$value)
            );
        }

        return $expressionBuilder->orX(...$constraints);
    }
}
