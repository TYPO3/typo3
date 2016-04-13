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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * TYPO3 / TCA specific query settings that deal with enable/hidden fields,
 * frontend groups and start-/endtimes.
 */
class QueryContext
{
    /**
     * The context for which the restraints are to be built.
     *
     * @var QueryContextType
     */
    protected $context;

    /**
     * @var int[]
     */
    protected $memberGroups = null;

    /**
     * @var int
     */
    protected $currentWorkspace = null;

    /**
     * @var int
     */
    protected $accessTime = null;

    /**
     * Global flag if hidden records are to be included in the query result.
     *
     * In PageRepository::enableFields() this is called showHidden
     *
     * @var bool
     */
    protected $includeHidden = null;

    /**
     * Global flag if deleted records are to be included in the query result.
     *
     * @var bool
     */
    protected $includeDeleted = false;

    /**
     * Per table flag if deleted records are to be included in the query result.
     *
     * @var array
     */
    protected $includeDeletedForTable = [];

    /**
     * Global flag if records in a non-default versioned state should be
     * included in the query results.
     *
     * In PageRepository the flag is called versioningPreview
     *
     * @var bool
     */
    protected $includePlaceholders = null;

    /**
     * Global flag if versioned records are to be included in the query result.
     * Also influences if enable fields are respected for the query.
     *
     * In PageRepository the flag is called noVersionPreview
     *
     * @var bool
     */
    protected $includeVersionedRecords = false;

    /**
     * Global flag if enable fields are going to be checked for the query.
     *
     * @var bool
     */
    protected $ignoreEnableFields = false;

    /**
     * Global list of enable columns that are not checked for the query.
     * This list is only checked if $ignoreEnableFields is enabled.
     *
     * @var string[]
     */
    protected $ignoredEnableFields = [];

    /**
     * Per table list of enable columns that are not checked for the query.
     * This list is only checked if $ignoreEnableFields is enabled.
     *
     * @var string[]
     */
    protected $ignoredEnableFieldsForTable = []; // Per Table list of ignored columns

    /**
     * Associative array of table configs to override the TCA definition. If a table
     * is not configured here the setup information from the TCA will be used.
     *
     * The array key is the table name, the value is in the format
     * [
     *   'deleted' => 'fieldName',
     *   'versioningWS' => true,
     *   'enablecolumns' => [ 'disabled' => hidden, ... ]
     * ]
     *
     * @var array
     */
    protected $tableConfigs = [];

    /**
     * QueryContext constructor.
     *
     * @param string $context A valid QueryContextType
     */
    public function __construct(string $context = QueryContextType::AUTO)
    {
        $this->context = GeneralUtility::makeInstance(QueryContextType::class, $context);
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        if ($this->context->equals(QueryContextType::AUTO)) {
            if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_FE) {
                return QueryContextType::FRONTEND;
            } elseif (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE) {
                return QueryContextType::BACKEND;
            } else {
                return QueryContextType::NONE;
            }
        }

        return (string)$this->context;
    }

    /**
     * Set the context in which the query is going to be run.
     * Used by the QueryRestrictionBuilder to determine the restrictions to be placed.
     *
     * @param string $context
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setContext(string $context): QueryContext
    {
        $this->context = GeneralUtility::makeInstance(QueryContextType::class, $context);

        return $this;
    }

    /**
     * Get a list of member groups (fe_groups) that will be used in when building
     * query restrictions in FE context.
     *
     * @return int[]
     */
    public function getMemberGroups(): array
    {
        // If the member groups have not been explicitly set
        // the group list from the frontend controller context
        // will be inherited
        if ($this->memberGroups === null) {
            $this->memberGroups = GeneralUtility::intExplode(
                ',',
                $this->getTypoScriptFrontendController()->gr_list,
                true
            );
        }

        return (array)$this->memberGroups;
    }

    /**
     * Set the member groups that will be checked in frontend context.
     *
     * @param int[] $memberGroups
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setMemberGroups(array $memberGroups): QueryContext
    {
        $this->memberGroups = $memberGroups;

        return $this;
    }

    /**
     * Get the current workspace. If not actively defined it will fall back
     * to the current workspace set in the PageRepository.
     *
     * @return int
     */
    public function getCurrentWorkspace(): int
    {
        return $this->currentWorkspace ?? (int)$this->getPageRepository()->versioningWorkspaceId;
    }

    /**
     * Set the current workspace id.
     *
     * @param int $currentWorkspace
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setCurrentWorkspace(int $currentWorkspace): QueryContext
    {
        $this->currentWorkspace = $currentWorkspace;

        return $this;
    }

    /**
     * Return the current accesstime. If not explictly set fall back to the
     * value of $GLOBALS['SIM_ACCESS_TIME']
     *
     * @return int
     */
    public function getAccessTime(): int
    {
        if ($this->accessTime === null) {
            return empty($GLOBALS['SIM_ACCESS_TIME']) ? 0 : (int)$GLOBALS['SIM_ACCESS_TIME'];
        }

        return $this->accessTime;
    }

    /**
     * Set the current access time.
     *
     * @param int $accessTime
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setAccessTime(int $accessTime): QueryContext
    {
        $this->accessTime = $accessTime;

        return $this;
    }

    /**
     * Returns the global setting wether hidden records should be included
     * in the query result. Preferrably getIncludeHiddenForTable() should
     * be used as the proper information from TSFE can be inherited by
     * using the table name information.
     *
     * Defaults to false in case the flag has not been explictly set.
     *
     * @return bool
     * @internal
     */
    public function getIncludeHidden(): bool
    {
        // Casting to bool to accomodate for the legacy fallback:
        // When showHidden has not been explicitly set it's going to
        // be determined by the settings in the TyposcriptFrontendController.
        // As we don't now the table being queried here it's better to use
        // getIncludeHiddenForTable()
        return (bool)$this->includeHidden;
    }

    /**
     * Flag if hidden records for the given table should be included in the query result.
     * If $includeHidden has not been explictly set the information from TSFE will be
     * used to determine the setting.
     *
     * @param string $table
     * @return bool
     */
    public function getIncludeHiddenForTable(string $table): bool
    {
        if ($this->includeHidden === null && is_object($this->getTypoScriptFrontendController())) {
            $showHidden = $table === 'pages' || $table === 'pages_language_overlay'
                ? $this->getTypoScriptFrontendController()->showHiddenPage
                : $this->getTypoScriptFrontendController()->showHiddenRecords;

            if ($showHidden === -1) {
                $showHidden = false;
            }

            $this->includeHidden = (bool)$showHidden;
        }

        return (bool)$this->includeHidden;
    }

    /**
     * Set if hidden records should be part of the query result set.
     *
     * @param bool $includeHidden
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIncludeHidden(bool $includeHidden): QueryContext
    {
        $this->includeHidden = $includeHidden;

        return $this;
    }

    /**
     * Get if deleted records should be part of the query result set at all.
     *
     * @return bool
     */
    public function getIncludeDeleted(): bool
    {
        return $this->includeDeleted;
    }

    /**
     * Set wether deleted records shoult be part of the query result.
     *
     * @param bool $includeDeleted
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIncludeDeleted(bool $includeDeleted): QueryContext
    {
        $this->includeDeleted = $includeDeleted;

        return $this;
    }

    /**
     * Get if records in a non-default versioning state should be part of the query result set.
     *
     * @return bool
     */
    public function getIncludePlaceholders(): bool
    {
        if ($this->includePlaceholders === null) {
            $this->includePlaceholders = $this->getPageRepository()->versioningPreview;
        }

        return (bool)$this->includePlaceholders;
    }

    /**
     * Set if records in a non-default versioning state should be part of the query result set.
     *
     * @param bool $includePlaceholders
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIncludePlaceholders(bool $includePlaceholders): QueryContext
    {
        $this->includePlaceholders = $includePlaceholders;

        return $this;
    }

    /**
     * Get if versioned records shoult be part of the query result set.
     *
     * @return bool
     */
    public function getIncludeVersionedRecords(): bool
    {
        return $this->includeVersionedRecords;
    }

    /**
     * Set if versioned records should be part of the query result set.
     *
     * @param bool $includeVersionedRecords
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIncludeVersionedRecords(bool $includeVersionedRecords): QueryContext
    {
        $this->includeVersionedRecords = $includeVersionedRecords;

        return $this;
    }

    /**
     * Get if enable fields should be ignored for this query.
     *
     * @return bool
     */
    public function getIgnoreEnableFields(): bool
    {
        return $this->ignoreEnableFields;
    }

    /**
     * Set if enable fields should be ignored for this query.
     *
     * @param bool $ignoreEnableFields
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIgnoreEnableFields(bool $ignoreEnableFields): QueryContext
    {
        $this->ignoreEnableFields = $ignoreEnableFields;

        return $this;
    }

    /**
     * Return global list of ignored enable columns for the query.
     * Can be overridden per table. Only checked if $ignoreEnableFields is enabled.
     *
     * @return string[]
     */
    public function getIgnoredEnableFields(): array
    {
        return $this->ignoredEnableFields;
    }

    /**
     * Set the global list of ignored enable columns.
     *
     * @param string[] $ignoredEnableFields
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIgnoredEnableFields(array $ignoredEnableFields): QueryContext
    {
        $this->ignoredEnableFields = $ignoredEnableFields;

        return $this;
    }

    /**
     * Get the ignored enable columns for this table.
     * If no specific list has been defined the global list will be returned.
     *
     * @param string $table
     * @return string[]
     */
    public function getIgnoredEnableFieldsForTable(string $table): array
    {
        if (isset($this->ignoredEnableFieldsForTable[$table])) {
            return $this->ignoredEnableFieldsForTable[$table];
        } elseif (!empty($this->ignoredEnableFields)) {
            return $this->ignoredEnableFields;
        }

        return [];
    }

    /**
     * @param string $table
     * @param string[] $ignoredEnableFieldsForTable
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIgnoredEnableFieldsForTable(string $table, array $ignoredEnableFieldsForTable): QueryContext
    {
        $this->ignoredEnableFieldsForTable[$table] = $ignoredEnableFieldsForTable;

        return $this;
    }

    /**
     * Get if deleted records for this table should be included in the query result set.
     *
     * @param string $table
     * @return bool
     */
    public function getIncludeDeletedForTable(string $table): bool
    {
        return $this->includeDeletedForTable[$table] ?? false;
    }

    /**
     * Set if deleted records for this table should be included in the query result.
     *
     * @param string $table
     * @param bool $includeDeletedForTable
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setIncludeDeletedForTable(string $table, bool $includeDeletedForTable): QueryContext
    {
        $this->includeDeletedForTable[$table] = $includeDeletedForTable;

        return $this;
    }

    /**
     * Get the table configuration information for all tables.
     *
     * @return array
     */
    public function getTableConfigs(): array
    {
        return $this->tableConfigs;
    }

    /**
     * Set the table configuration for all tables.
     *
     * @param array $tableConfigs
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function setTableConfigs(array $tableConfigs): QueryContext
    {
        $this->tableConfigs = $tableConfigs;

        return $this;
    }

    /**
     * Get the table configuration for a single table.
     *
     * @param string $table
     * @return array
     */
    public function getTableConfig(string $table): array
    {
        return $this->tableConfigs[$table] ?? $this->getTcaDefiniton($table);
    }

    /**
     * Get the TCA definition for a tables and extract the relevant parts
     * of the table configuration.
     *
     * @param string $table
     * @return array
     */
    protected function getTcaDefiniton(string $table): array
    {
        $ctrlDefiniton = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        return array_intersect_key(
            $ctrlDefiniton,
            ['delete' => true, 'versioningWS' => true, 'enablecolumns' => true]
        );
    }

    /**
     * Add a table configuration entry to the table config array.
     *
     * @param string $table
     * @param string $deletedField
     * @param bool $versioningSupport
     * @param array $enableColumns
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function addTableConfig(
        string $table,
        string $deletedField = null,
        bool $versioningSupport = false,
        array $enableColumns = []
    ): QueryContext {
        $this->tableConfigs[$table] = [
            'deleted' => $deletedField,
            'versioningWS' => $versioningSupport,
            'enablecolumns' => $enableColumns
        ];
    }

    /**
     * Remove a table override from the config array.
     *
     * @param string $table
     * @return \TYPO3\CMS\Core\Database\Query\QueryContext
     */
    public function removeTableConfig(string $table): QueryContext
    {
        unset($this->tableConfigs[$table]);

        return $this;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepository(): PageRepository
    {
        if ($this->getContext() === QueryContextType::FRONTEND && is_object($this->getTypoScriptFrontendController())) {
            return $this->getTypoScriptFrontendController()->sys_page;
        } else {
            return GeneralUtility::makeInstance(PageRepository::class);
        }
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
