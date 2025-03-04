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

namespace TYPO3\CMS\Impexp;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Result;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Serializer\Typo3XmlParserOptions;
use TYPO3\CMS\Core\Serializer\Typo3XmlSerializer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Impexp\View\ExportPageTreeView;

/**
 * T3D file Export library (TYPO3 Record Document)
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class Export extends ImportExport
{
    public const LEVELS_RECORDS_ON_THIS_PAGE = -2;
    public const LEVELS_INFINITE = 999;

    public const FILETYPE_XML = 'xml';
    public const FILETYPE_T3D = 't3d';
    public const FILETYPE_T3DZ = 't3d_compressed';

    protected string $mode = 'export';
    protected string $title = '';
    protected string $description = '';
    protected string $notes = '';
    protected array $record = [];
    protected array $list = [];
    protected int $levels = 0;
    protected array $tables = [];

    /**
     * Add table names here which are THE ONLY ones which will be included
     * into export if found as relations. '_ALL' will allow all tables.
     */
    protected array $relOnlyTables = [];
    protected string $treeHTML = '';

    /**
     * If set, HTML file resources are included.
     */
    protected bool $includeExtFileResources = true;

    /**
     * The key is the record type (e.g. 'be_users'),
     * the value is an array of fields to be included in the export.
     *
     * Used in tests only.
     */
    protected array $recordTypesIncludeFields = [];

    /**
     * Default array of fields to be included in the export
     */
    protected array $defaultRecordIncludeFields = ['uid', 'pid'];
    protected bool $saveFilesOutsideExportFile = false;
    protected string $exportFileName = '';
    protected string $exportFileType = self::FILETYPE_XML;
    protected array $supportedFileTypes = [];
    protected bool $compressionAvailable = false;

    /**
     * Cache for checks if page is in user web mounts.
     */
    protected array $pageInWebMountCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->compressionAvailable = function_exists('gzcompress');
    }

    /**************************
     * Export / Init + Meta Data
     *************************/

    /**
     * Process configuration
     */
    public function process(): void
    {
        $this->initializeExport();
        $this->setHeaderBasics();
        $this->setMetaData();

        // Configure which records to export
        foreach ($this->record as $ref) {
            $rParts = explode(':', $ref);
            $table = $rParts[0];
            $record = BackendUtility::getRecord($rParts[0], (int)$rParts[1]);
            if (is_array($record)) {
                $this->exportAddRecord($table, $record);
            }
        }

        // Configure which tables to export
        foreach ($this->list as $ref) {
            $rParts = explode(':', $ref);
            $table = $rParts[0];
            $pid = (int)$rParts[1];
            if ($this->getBackendUser()->check('tables_select', $table)) {
                $statement = $this->execListQueryPid($pid, $table);
                while ($record = $statement->fetchAssociative()) {
                    if (is_array($record)) {
                        $this->exportAddRecord($table, $record);
                    }
                }
            }
        }

        // Configure which page tree to export
        if ($this->pid !== -1) {
            $pageTree = null;
            if ($this->levels === self::LEVELS_RECORDS_ON_THIS_PAGE) {
                $this->addRecordsForPid($this->pid, $this->tables);
            } else {
                $pageTreeView = GeneralUtility::makeInstance(ExportPageTreeView::class);
                $initClause = $this->getExcludePagesClause();
                if ($this->excludeDisabledRecords) {
                    $initClause .= BackendUtility::BEenableFields('pages');
                }
                $pageTreeView->init($initClause);
                $pageTreeView->buildTreeByLevels($this->pid, $this->levels);
                $this->treeHTML = $pageTreeView->printTree();
                $pageTree = $pageTreeView->buffer_idH;
            }
            // In most cases, we should have a multi-level array, $pageTree, with the page tree
            // structure here (and the HTML code loaded into memory for a nice display...)
            if (is_array($pageTree)) {
                $pageList = [];
                $this->removeExcludedPagesFromPageTree($pageTree);
                $this->setPageTree($pageTree);
                $this->flatInversePageTree($pageTree, $pageList);
                foreach ($pageList as $pageUid => $_) {
                    $record = BackendUtility::getRecord('pages', $pageUid);
                    if (is_array($record)) {
                        $this->exportAddRecord('pages', $record);
                        foreach ($this->getTranslationForPage((int)$record['uid'], $this->excludeDisabledRecords) as $pageTranslation) {
                            // Export l10n translations
                            // All exported records need to be considered within "insidePageTree", not "outsidePageTree",
                            // because they actually ARE part of the page tree. To achieve this, their UID index is
                            // added into $this->dat['header']['pagetree'].
                            $this->exportAddRecord('pages', $pageTranslation);
                            // Be sure to not overwrite existing parts of the pagetree
                            // Integrate the extra record into the internal pagetree array
                            $this->dat['header']['pagetree'][(int)$pageTranslation['uid']]['uid'] = (int)$pageTranslation['uid'];
                        }

                        // Translated pages can also be directly exported; in that case the pageList may
                        // point to the UID of a translated page, and not the root page. Since tt_content
                        // records are bound to the default page UID, those records would be missing.
                        // So we use the page ID of the default language, and then attach all records
                        // for that page ID, which also match the selected page's language.
                        if (($record[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null] ?? 0) > 0
                            && !empty($GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? '')
                        ) {
                            $this->addRecordsForPid(
                                (int)$record[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']],
                                $this->tables,
                                [$record[$GLOBALS['TCA']['pages']['ctrl']['languageField']]]
                            );
                        }
                    }
                    $this->addRecordsForPid((int)$pageUid, $this->tables);
                }
            }
        }

        // After adding ALL records we add records from database relations
        for ($l = 0; $l < 10; $l++) {
            if ($this->exportAddRecordsFromRelations($l) === 0) {
                break;
            }
        }

        // Files must be added after the database relations are added,
        // so that files from ALL added records are included!
        $this->exportAddFilesFromRelations();
        $this->exportAddFilesFromSysFilesRecords();
    }

    /**
     * Add page translations to list of pages
     */
    protected function getTranslationForPage(
        int $defaultLanguagePageUid,
        bool $considerHiddenPages,
        array $limitToLanguageIds = []
    ): array {
        if (empty($GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? '')
            || empty($GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? '')
        ) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if (!$considerHiddenPages) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }
        $constraints = [
            $queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                $queryBuilder->createNamedParameter($defaultLanguagePageUid, Connection::PARAM_INT)
            ),
        ];
        if (!empty($limitToLanguageIds)) {
            $constraints[] = $queryBuilder->expr()->in(
                $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                $queryBuilder->createNamedParameter($limitToLanguageIds, ArrayParameterType::INTEGER)
            );
        } else {
            // Ensure consistency by only fetching pages where not only l10n_parent matches, but also a
            // sys_language_uid > 0 exists.
            $constraints[] = $queryBuilder->expr()->gt($GLOBALS['TCA']['pages']['ctrl']['languageField'], 0);
        }
        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(...$constraints)
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Initialize all settings for the export
     */
    protected function initializeExport(): void
    {
        $this->dat = [
            'header' => [],
            'records' => [],
        ];
    }

    /**
     * Set header basics
     */
    protected function setHeaderBasics(): void
    {
        // Initializing:
        foreach ($this->softrefCfg as $key => $value) {
            if (!($value['mode'] ?? false)) {
                unset($this->softrefCfg[$key]);
            }
        }
        // Setting in header memory:
        // Version of file format
        $this->dat['header']['XMLversion'] = '1.0';
        // Initialize meta data array (to put it in top of file)
        $this->dat['header']['meta'] = [];
        // Add list of tables to consider static
        $this->dat['header']['relStaticTables'] = $this->relStaticTables;
        // The list of excluded records
        $this->dat['header']['excludeMap'] = $this->excludeMap;
        // Soft reference mode for elements
        $this->dat['header']['softrefCfg'] = $this->softrefCfg;
        // List of extensions the import depends on.
        $this->dat['header']['extensionDependencies'] = $this->extensionDependencies;
        $this->dat['header']['charset'] = 'utf-8';
    }

    /**
     * Sets meta data
     */
    protected function setMetaData(): void
    {
        $user = $this->getBackendUser();
        if ($user->user['lang'] ?? false) {
            $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($user->user['lang']);
        } else {
            $locale = new Locale();
        }
        $this->dat['header']['meta'] = [
            'title' => $this->title,
            'description' => $this->description,
            'notes' => $this->notes,
            'packager_username' => $this->getBackendUser()->user['username'],
            'packager_name' => $this->getBackendUser()->user['realName'],
            'packager_email' => $this->getBackendUser()->user['email'],
            'TYPO3_version' => (string)GeneralUtility::makeInstance(Typo3Version::class),
            'created' => (new DateFormatter())->format($GLOBALS['EXEC_TIME'], 'EEE d. MMMM y', $locale),
        ];
    }

    /**************************
     * Export / Init Page tree
     *************************/

    /**
     * Sets the page-tree array in the export header
     *
     * @param array $pageTree Hierarchy of ids, the page tree: array([uid] => array("uid" => [uid], "subrow" => array(.....)), [uid] => ....)
     */
    public function setPageTree(array $pageTree): void
    {
        $this->dat['header']['pagetree'] = $pageTree;
    }

    /**
     * Removes entries in the page tree which are found in ->excludeMap[]
     *
     * @param array $pageTree Hierarchy of ids, the page tree
     */
    protected function removeExcludedPagesFromPageTree(array &$pageTree): void
    {
        foreach ($pageTree as $pid => $value) {
            if ($this->isRecordExcluded('pages', (int)($pageTree[$pid]['uid'] ?? 0))) {
                unset($pageTree[$pid]);
            } elseif (is_array($pageTree[$pid]['subrow'] ?? null)) {
                $this->removeExcludedPagesFromPageTree($pageTree[$pid]['subrow']);
            }
        }
    }

    /**************************
     * Export
     *************************/

    /**
     * Sets the fields of record types to be included in the export.
     * Used in tests only.
     *
     * @param array $recordTypesIncludeFields The key is the record type,
     *                                          the value is an array of fields to be included in the export.
     * @throws Exception if an array value is not type of array
     */
    public function setRecordTypesIncludeFields(array $recordTypesIncludeFields): void
    {
        foreach ($recordTypesIncludeFields as $table => $fields) {
            if (!is_array($fields)) {
                throw new Exception('The include fields for record type ' . htmlspecialchars($table) . ' are not defined by an array.', 1391440658);
            }
            $this->setRecordTypeIncludeFields($table, $fields);
        }
    }

    /**
     * Sets the fields of a record type to be included in the export.
     * Used in tests only.
     *
     * @param string $table The record type
     * @param array $fields The fields to be included
     */
    protected function setRecordTypeIncludeFields(string $table, array $fields): void
    {
        $this->recordTypesIncludeFields[$table] = $fields;
    }

    /**
     * Filter page IDs by traversing the exclude map, finding all
     * excluded pages (if any) and making an AND NOT IN statement for the select clause.
     *
     * @return string AND where clause part to filter out page uids.
     */
    protected function getExcludePagesClause(): string
    {
        $pageIds = [];

        foreach ($this->excludeMap as $tableAndUid => $isExcluded) {
            [$table, $uid] = explode(':', $tableAndUid);
            if ($table === 'pages') {
                $pageIds[] = (int)$uid;
            }
        }
        if (!empty($pageIds)) {
            return ' AND uid NOT IN (' . implode(',', $pageIds) . ')';
        }
        return '';
    }

    /**
     * Adds records to the export object for a specific page id.
     *
     * @param int $pid Page id for which to select records to add
     * @param array $tables Array of table names to select from
     * @param array $restrictToLanguageIds Array of sys_language_uid IDs to allow records for.
     */
    protected function addRecordsForPid(int $pid, array $tables, array $restrictToLanguageIds = []): void
    {
        $isRestrictToLanguageIds = $restrictToLanguageIds !== [];
        foreach ($GLOBALS['TCA'] as $table => $value) {
            if ($table !== 'pages'
                && (in_array($table, $tables, true) || in_array('_ALL', $tables, true))
                && $this->getBackendUser()->check('tables_select', $table)
                && !($GLOBALS['TCA'][$table]['ctrl']['is_static'] ?? false)
            ) {
                $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
                $statement = $this->execListQueryPid($pid, $table);
                while ($record = $statement->fetchAssociative()) {
                    if (is_array($record)) {
                        // Skip the record, when languageId restrictions are enabled, and the record's language is not requested
                        if ($isRestrictToLanguageIds && $languageField && isset($record[$languageField]) && !in_array($record[$languageField], $restrictToLanguageIds, true)) {
                            continue;
                        }
                        $this->exportAddRecord($table, $record);
                    }
                }
            }
        }
    }

    /**
     * Selects records from table / pid
     *
     * @param int $pid Page ID to select from
     * @param string $table Table to select from
     * @return Result Query statement
     */
    protected function execListQueryPid(int $pid, string $table): Result
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? $GLOBALS['TCA'][$table]['ctrl']['default_sortby'] ?? '';

        if ($this->excludeDisabledRecords === false) {
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        } else {
            $queryBuilder->getRestrictions()
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        }

        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                )
            );

        $orderBys = QueryHelper::parseOrderBy((string)$orderBy);
        foreach ($orderBys as $orderPair) {
            [$field, $order] = $orderPair;
            $queryBuilder->addOrderBy($field, $order);
        }
        // Ensure deterministic sorting
        if (!in_array('uid', array_column($orderBys, 0))) {
            $queryBuilder->addOrderBy('uid', 'ASC');
        }

        return $queryBuilder->executeQuery();
    }

    /**
     * Adds the record $row from $table.
     * No checking for relations done here. Pure data.
     *
     * @param string $table Table name
     * @param array $row Record row.
     * @param int $relationLevel (Internal) if the record is added as a relation, this is set to the "level" it was on.
     */
    public function exportAddRecord(string $table, array $row, int $relationLevel = 0): void
    {
        BackendUtility::workspaceOL($table, $row);

        if ($table === '' || (int)$row['uid'] === 0
            || $this->isRecordExcluded($table, (int)$row['uid'])
            || $this->excludeDisabledRecords && $this->isRecordDisabled($table, (int)$row['uid'])) {
            return;
        }

        if ($this->isPageInWebMount($table === 'pages' ? (int)$row['uid'] : (int)$row['pid'])) {
            if (!isset($this->dat['records'][$table . ':' . $row['uid']])) {
                // Prepare header info:
                $row = $this->filterRecordFields($table, $row);
                $headerInfo = [];
                $headerInfo['uid'] = $row['uid'];
                $headerInfo['pid'] = $row['pid'];
                $headerInfo['title'] = GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $row), 40);
                if ($relationLevel) {
                    $headerInfo['relationLevel'] = $relationLevel;
                }
                // Set the header summary:
                $this->dat['header']['records'][$table][$row['uid']] = $headerInfo;
                // Create entry in the PID lookup:
                $this->dat['header']['pid_lookup'][$row['pid']][$table][$row['uid']] = 1;
                // Initialize reference index object:
                $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                // @todo: Using getRelations() from Refindex for this operation is a misuse, the method should
                //        be protected. It would be better to use softref parser and RelationHandler here directly,
                //        or fetch the relations using a sys_refindex query. Note with recent changes, 'itemArray'
                //        with MM contain 'sorting', 'sorting_foreign', 'fieldname' as well, which could be removed
                //        from export again if needed, since they are currently irrelevant during import.
                //        Note 'fieldname' could be handy during import, though: When a category is for instance bound
                //        to two different fields in a target table (e.g. 'pages'), that field indicates to which
                //        of those a relation is bound. This is currently most likely not handled during import and
                //        should have more test coverage.
                $relations = $refIndexObj->getRelations($table, $row, 0);
                $relations = $this->removeRedundantSoftRefsInRelations($relations);
                // Data:
                $this->dat['records'][$table . ':' . $row['uid']] = [];
                $this->dat['records'][$table . ':' . $row['uid']]['data'] = $row;
                $this->dat['records'][$table . ':' . $row['uid']]['rels'] = $relations;
                // There are no refindex entries for l10n_source of pages and tt_content, so we have to add them here manually for now.
                // @todo can be removed, when this can come from ReferenceIndex.
                if (($table === 'pages' || $table === 'tt_content')) {
                    $fieldNameTranslationSource = ($GLOBALS['TCA'][$table]['ctrl']['translationSource'] ?? '');
                    if (!empty($fieldNameTranslationSource) && ((int)($row[$fieldNameTranslationSource] ?? 0)) > 0) {
                        $this->dat['records'][$table . ':' . $row['uid']]['rels'][$fieldNameTranslationSource]['type'] = 'db';
                        $this->dat['records'][$table . ':' . $row['uid']]['rels'][$fieldNameTranslationSource]['itemArray'][0] = [
                            'id' => $row[$fieldNameTranslationSource],
                            'table' => $table,
                        ];
                    }
                }
                // Add information about the relations in the record in the header:
                $this->dat['header']['records'][$table][$row['uid']]['rels'] = $this->flatDbRelations($this->dat['records'][$table . ':' . $row['uid']]['rels']);
                // Add information about the softrefs to header:
                $this->dat['header']['records'][$table][$row['uid']]['softrefs'] = $this->flatSoftRefs($this->dat['records'][$table . ':' . $row['uid']]['rels']);
            } else {
                $this->addError('Record ' . $table . ':' . $row['uid'] . ' already added.');
            }
        } else {
            $this->addError('Record ' . $table . ':' . $row['uid'] . ' was outside your database mounts!');
        }
    }

    /**
     * Checking if a page is in the web mounts of the user
     *
     * @param int $pid Page ID to check
     * @return bool TRUE if OK
     */
    protected function isPageInWebMount(int $pid): bool
    {
        if (!isset($this->pageInWebMountCache[$pid])) {
            $this->pageInWebMountCache[$pid] = (bool)$this->getBackendUser()->isInWebMount($pid);
        }
        return $this->pageInWebMountCache[$pid];
    }

    /**
     * If include fields for a specific record type are set, the data
     * are filtered out with fields are not included in the fields.
     * Used in tests only.
     *
     * @param string $table The record type to be filtered
     * @param array $row The data to be filtered
     * @return array The filtered record row
     */
    protected function filterRecordFields(string $table, array $row): array
    {
        if (isset($this->recordTypesIncludeFields[$table])) {
            $includeFields = array_unique(array_merge(
                $this->recordTypesIncludeFields[$table],
                $this->defaultRecordIncludeFields
            ));
            $newRow = [];
            foreach ($row as $key => $value) {
                if (in_array($key, $includeFields, true)) {
                    $newRow[$key] = $value;
                }
            }
        } else {
            $newRow = $row;
        }
        return $newRow;
    }

    /**
     * Relations could contain db relations to sys_file records. Some configuration combinations of TCA and
     * SoftReferenceIndex create also soft reference relation entries for the identical file. This results
     * in double included files, one in array "files" and one in array "file_fal".
     * This function checks the relations for this double inclusions and removes the redundant soft reference
     * relation.
     */
    protected function removeRedundantSoftRefsInRelations(array $relations): array
    {
        foreach ($relations as &$relation) {
            if (isset($relation['type']) && $relation['type'] === 'db') {
                foreach ($relation['itemArray'] as $dbRelationData) {
                    if ($dbRelationData['table'] === 'sys_file') {
                        if (isset($relation['softrefs']['keys']['typolink'])) {
                            foreach ($relation['softrefs']['keys']['typolink'] as $tokenID => $softref) {
                                if ($softref['subst']['type'] === 'file') {
                                    $file = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($softref['subst']['relFileName']);
                                    if ($file instanceof File) {
                                        if ($file->getUid() == $dbRelationData['id']) {
                                            unset($relation['softrefs']['keys']['typolink'][$tokenID]);
                                        }
                                    }
                                }
                            }
                            if (empty($relation['softrefs']['keys']['typolink'])) {
                                unset($relation['softrefs']);
                            }
                        }
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * Database relations flattened to 1-dimensional array.
     * The list will be unique, no table/uid combination will appear twice.
     *
     * @param array $relations 2-dimensional array of database relations organized by table key
     * @return array 1-dimensional array where entries are table:uid and keys are array with table/id
     */
    protected function flatDbRelations(array $relations): array
    {
        $list = [];
        foreach ($relations as $relation) {
            if (isset($relation['type'])) {
                if ($relation['type'] === 'db') {
                    foreach ($relation['itemArray'] as $dbRelationData) {
                        $list[$dbRelationData['table'] . ':' . $dbRelationData['id']] = $dbRelationData;
                    }
                } elseif ($relation['type'] === 'flex' && is_array($relation['flexFormRels']['db'] ?? null)) {
                    foreach ($relation['flexFormRels']['db'] as $subList) {
                        foreach ($subList as $dbRelationData) {
                            $list[$dbRelationData['table'] . ':' . $dbRelationData['id']] = $dbRelationData;
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * Soft references flattened to 1-dimensional array.
     *
     * @param array $relations 2-dimensional array of database relations organized by table key
     * @return array 1-dimensional array where entries are arrays with properties of the soft link found and
     *                  keys are a unique combination of field, spKey, structure path if applicable and token ID
     */
    protected function flatSoftRefs(array $relations): array
    {
        $list = [];
        foreach ($relations as $field => $relation) {
            if (is_array($relation['softrefs']['keys'] ?? null)) {
                foreach ($relation['softrefs']['keys'] as $spKey => $elements) {
                    foreach ($elements as $subKey => $el) {
                        $lKey = $field . ':' . $spKey . ':' . $subKey;
                        $list[$lKey] = array_merge(['field' => $field, 'spKey' => $spKey], $el);
                        // Add file_ID key to header - slightly "risky" way of doing this because if the calculation
                        // changes for the same value in $this->records[...] this will not work anymore!
                        if ($el['subst']['relFileName'] ?? false) {
                            $list[$lKey]['file_ID'] = md5(Environment::getPublicPath() . '/' . $el['subst']['relFileName']);
                        }
                    }
                }
            }
            if (isset($relation['type'])) {
                if ($relation['type'] === 'flex' && is_array($relation['flexFormRels']['softrefs'] ?? null)) {
                    foreach ($relation['flexFormRels']['softrefs'] as $structurePath => &$subList) {
                        if (isset($subList['keys'])) {
                            foreach ($subList['keys'] as $spKey => $elements) {
                                foreach ($elements as $subKey => $el) {
                                    $lKey = $field . ':' . $structurePath . ':' . $spKey . ':' . $subKey;
                                    $list[$lKey] = array_merge([
                                        'field' => $field,
                                        'spKey' => $spKey,
                                        'structurePath' => $structurePath,
                                    ], $el);
                                    // Add file_ID key to header - slightly "risky" way of doing this because if the calculation
                                    // changes for the same value in $this->records[...] this will not work anymore!
                                    if ($el['subst']['relFileName'] ?? false) {
                                        $list[$lKey]['file_ID'] = md5(Environment::getPublicPath() . '/' . $el['subst']['relFileName']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $list;
    }

    /**
     * This analyzes the existing added records, finds all database relations to records and adds these records to the
     * export file.
     * This function can be called repeatedly until it returns zero added records.
     * In principle it should not allow to infinite recursion, but you better set a limit...
     * Call this BEFORE the exportAddFilesFromRelations (so files from added relations are also included of course)
     *
     * @param int $relationLevel Recursion level
     * @return int number of records from relations found and added
     * @see exportAddFilesFromRelations()
     */
    protected function exportAddRecordsFromRelations(int $relationLevel = 0): int
    {
        if (!isset($this->dat['records'])) {
            $this->addError('There were no records available.');
            return 0;
        }

        $addRecords = [];

        foreach ($this->dat['records'] as $record) {
            if (!is_array($record)) {
                continue;
            }
            foreach ($record['rels'] as $relation) {
                if (isset($relation['type'])) {
                    if ($relation['type'] === 'db') {
                        foreach ($relation['itemArray'] as $dbRelationData) {
                            $this->exportAddRecordsFromRelationsPushRelation($dbRelationData, $addRecords);
                        }
                    }
                    if ($relation['type'] === 'flex') {
                        // Database relations in flex form fields:
                        if (is_array($relation['flexFormRels']['db'] ?? null)) {
                            foreach ($relation['flexFormRels']['db'] as $subList) {
                                foreach ($subList as $dbRelationData) {
                                    $this->exportAddRecordsFromRelationsPushRelation($dbRelationData, $addRecords);
                                }
                            }
                        }

                        // Database oriented soft references in flex form fields:
                        if (is_array($relation['flexFormRels']['softrefs'] ?? null)) {
                            foreach ($relation['flexFormRels']['softrefs'] as $subList) {
                                foreach ($subList['keys'] as $elements) {
                                    foreach ($elements as $el) {
                                        if ($el['subst']['type'] === 'db' && $this->isSoftRefIncluded($el['subst']['tokenID'])) {
                                            [$referencedTable, $referencedUid] = explode(':', $el['subst']['recordRef']);
                                            $dbRelationData = [
                                                'table' => $referencedTable,
                                                'id' => $referencedUid,
                                            ];
                                            $this->exportAddRecordsFromRelationsPushRelation($dbRelationData, $addRecords, $el['subst']['tokenID']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // In any case, if there are soft refs:
                if (is_array($relation['softrefs']['keys'] ?? null)) {
                    foreach ($relation['softrefs']['keys'] as $elements) {
                        foreach ($elements as $el) {
                            if (($el['subst']['type'] ?? '') === 'db' && $this->isSoftRefIncluded($el['subst']['tokenID'])) {
                                [$referencedTable, $referencedUid] = explode(':', $el['subst']['recordRef']);
                                $dbRelationData = [
                                    'table' => $referencedTable,
                                    'id' => $referencedUid,
                                ];
                                $this->exportAddRecordsFromRelationsPushRelation($dbRelationData, $addRecords, $el['subst']['tokenID']);
                            }
                        }
                    }
                }
            }
        }

        if (!empty($addRecords)) {
            foreach ($addRecords as $recordData) {
                $record = BackendUtility::getRecord($recordData['table'], $recordData['id']);

                if (is_array($record)) {
                    // Depending on db driver, int fields may or may not be returned as integer or as string. The
                    // loop aligns that detail and forces strings for everything to have exports more db agnostic.
                    foreach ($record as $fieldName => $fieldValue) {
                        $record[$fieldName] = $fieldValue === null ? $fieldValue : (string)$fieldValue;
                    }
                    $this->exportAddRecord($recordData['table'], $record, $relationLevel + 1);
                }
                // Set status message
                // Relation pointers always larger than zero except certain "select" types with
                // negative values pointing to uids - but that is not supported here.
                if ($recordData['id'] > 0) {
                    $recordRef = $recordData['table'] . ':' . $recordData['id'];
                    if (!isset($this->dat['records'][$recordRef])) {
                        $this->dat['records'][$recordRef] = 'NOT_FOUND';
                        $this->addError('Relation record ' . $recordRef . ' was not found!');
                    }
                }
            }
        }

        return count($addRecords);
    }

    /**
     * Helper function for exportAddRecordsFromRelations()
     *
     * @param array $recordData Record of relation with table/id key to add to $addRecords
     * @param array $addRecords Records of relations which are already marked as to be added to the export
     * @param string $tokenID Soft reference token ID, if applicable.
     * @see exportAddRecordsFromRelations()
     */
    protected function exportAddRecordsFromRelationsPushRelation(array $recordData, array &$addRecords, string $tokenID = ''): void
    {
        // @todo: Remove by-reference and return final array
        $recordRef = $recordData['table'] . ':' . $recordData['id'];
        if (
            isset($GLOBALS['TCA'][$recordData['table']]) && !$this->isTableStatic($recordData['table'])
            && !$this->isRecordExcluded($recordData['table'], (int)$recordData['id'])
            && (!$tokenID || $this->isSoftRefIncluded($tokenID)) && $this->inclRelation($recordData['table'])
            && !isset($this->dat['records'][$recordRef])
        ) {
            $addRecords[$recordRef] = $recordData;
        }
    }

    /**
     * Returns TRUE if the input table name is to be included as relation
     *
     * @param string $table Table name
     * @return bool TRUE, if table is marked static
     */
    protected function inclRelation(string $table): bool
    {
        return is_array($GLOBALS['TCA'][$table] ?? null)
            && (in_array($table, $this->relOnlyTables, true) || in_array('_ALL', $this->relOnlyTables, true))
            && $this->getBackendUser()->check('tables_select', $table);
    }

    /**
     * This adds all files in relations.
     * Call this method AFTER adding all records including relations.
     *
     * @see exportAddRecordsFromRelations()
     */
    protected function exportAddFilesFromRelations(): void
    {
        // @todo: Consider NOT using by-reference but writing final $this->dat at end of method.
        if (!isset($this->dat['records'])) {
            $this->addError('There were no records available.');
            return;
        }

        foreach ($this->dat['records'] as &$record) {
            if (!is_array($record)) {
                continue;
            }
            foreach ($record['rels'] as &$relation) {
                // For all flex type relations:
                if (isset($relation['type']) && $relation['type'] === 'flex') {
                    // Database oriented soft references in flex form fields:
                    if (isset($relation['flexFormRels']['softrefs'])) {
                        foreach ($relation['flexFormRels']['softrefs'] as &$subList) {
                            foreach ($subList['keys'] as &$elements) {
                                foreach ($elements as &$el) {
                                    if ($el['subst']['type'] === 'file' && $this->isSoftRefIncluded($el['subst']['tokenID'])) {
                                        // Create abs path and ID for file:
                                        $ID_absFile = GeneralUtility::getFileAbsFileName(Environment::getPublicPath() . '/' . $el['subst']['relFileName']);
                                        $ID = md5($el['subst']['relFileName']);
                                        if ($ID_absFile) {
                                            if (!$this->dat['files'][$ID]) {
                                                $fileRelationData = [
                                                    'filename' => PathUtility::basename($ID_absFile),
                                                    'ID_absFile' => $ID_absFile,
                                                    'ID' => $ID,
                                                    'relFileName' => $el['subst']['relFileName'],
                                                ];
                                                $this->exportAddFile($fileRelationData);
                                            }
                                            $el['file_ID'] = $ID;
                                        }
                                    }
                                }
                            }
                        }
                        unset($subList, $elements, $el);
                    }
                }
                // In any case, if there are soft refs:
                foreach ($relation['softrefs']['keys'] ?? [] as &$elements) {
                    foreach ($elements as &$el) {
                        if (($el['subst']['type'] ?? '') === 'file' && $this->isSoftRefIncluded($el['subst']['tokenID'])) {
                            // Create abs path and ID for file:
                            $ID_absFile = GeneralUtility::getFileAbsFileName(Environment::getPublicPath() . '/' . $el['subst']['relFileName']);
                            $ID = md5($el['subst']['relFileName']);
                            if ($ID_absFile) {
                                if (!$this->dat['files'][$ID]) {
                                    $fileRelationData = [
                                        'filename' => PathUtility::basename($ID_absFile),
                                        'ID_absFile' => $ID_absFile,
                                        'ID' => $ID,
                                        'relFileName' => $el['subst']['relFileName'],
                                    ];
                                    $this->exportAddFile($fileRelationData);
                                }
                                $el['file_ID'] = $ID;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * This adds the file to the export
     *
     * @param array $fileData File information with three keys: "filename" = filename without path, "ID_absFile" = absolute filepath to the file (including the filename), "ID" = md5 hash of "ID_absFile". "relFileName" is optional for files attached to records, but mandatory for soft referenced files (since the relFileName determines where such a file should be stored!)
     */
    protected function exportAddFile(array $fileData): void
    {
        if (!@is_file($fileData['ID_absFile'])) {
            $this->addError($fileData['ID_absFile'] . ' was not a file! Skipping.');
            return;
        }

        $fileStat = stat($fileData['ID_absFile']);
        $fileMd5 = md5_file($fileData['ID_absFile']);

        $fileInfo = [];
        $fileInfo['filename'] = PathUtility::basename($fileData['ID_absFile']);
        $fileInfo['filemtime'] = $fileStat['mtime'];
        $fileInfo['relFileRef'] = PathUtility::stripPathSitePrefix($fileData['ID_absFile']);
        if ($fileData['relFileName']) {
            $fileInfo['relFileName'] = $fileData['relFileName'];
        }

        // Setting this data in the header
        $this->dat['header']['files'][$fileData['ID']] = $fileInfo;

        if (!$this->saveFilesOutsideExportFile) {
            $fileInfo['content'] = (string)file_get_contents($fileData['ID_absFile']);
        } else {
            GeneralUtility::upload_copy_move(
                $fileData['ID_absFile'],
                $this->getOrCreateTemporaryFolderName() . '/' . $fileMd5
            );
        }
        $fileInfo['content_md5'] = $fileMd5;
        $this->dat['files'][$fileData['ID']] = $fileInfo;
    }

    /**
     * This adds all files from sys_file records
     */
    protected function exportAddFilesFromSysFilesRecords(): void
    {
        foreach ($this->dat['header']['records']['sys_file'] ?? [] as $sysFileUid => $_) {
            $fileData = $this->dat['records']['sys_file:' . $sysFileUid]['data'];
            $this->exportAddSysFile($fileData);
        }
    }

    /**
     * This adds the file from a sys_file record to the export
     * - either as content or external file
     */
    protected function exportAddSysFile(array $fileData): void
    {
        try {
            $file = GeneralUtility::makeInstance(ResourceFactory::class)->createFileObject($fileData);
            $file->checkActionPermission('read');
        } catch (\Exception $e) {
            $this->addError('Error when trying to add file ' . $fileData['title'] . ': ' . $e->getMessage());
            return;
        }

        $fileUid = $file->getUid();
        $fileSha1 = $file->getStorage()->hashFile($file, 'sha1');
        if ($fileSha1 !== $file->getProperty('sha1')) {
            $this->dat['records']['sys_file:' . $fileUid]['data']['sha1'] = $fileSha1;
            $this->addError(
                'The SHA-1 file hash of ' . $file->getCombinedIdentifier() . ' is not up-to-date in the index! ' .
                'The file was added based on the current file hash.'
            );
        }
        // Build unique id based on the storage and the file identifier
        $fileId = md5($file->getStorage()->getUid() . ':' . $file->getProperty('identifier_hash'));

        $fileInfo = [];
        $fileInfo['filename'] = $file->getProperty('name');
        $fileInfo['filemtime'] = $file->getProperty('modification_date');

        // Setting this data in the header
        $this->dat['header']['files_fal'][$fileId] = $fileInfo;

        if (!$this->saveFilesOutsideExportFile) {
            $fileInfo['content'] = $file->getContents();
        } else {
            GeneralUtility::upload_copy_move(
                $file->getForLocalProcessing(false),
                $this->getOrCreateTemporaryFolderName() . '/' . $fileSha1
            );
        }
        $fileInfo['content_sha1'] = $fileSha1;
        $this->dat['files_fal'][$fileId] = $fileInfo;
    }

    /**************************
     * File Output
     *************************/

    /**
     * This compiles and returns the data content for an exported file
     * - "xml" gives xml
     * - "t3d" and "t3d_compressed" gives serialized array, possibly compressed
     *
     * @return string The output file stream
     */
    public function render(): string
    {
        if ($this->exportFileType === self::FILETYPE_XML) {
            $out = $this->createXML();
        } else {
            $out = '';
            // adding header:
            $out .= $this->addFilePart(serialize($this->dat['header']));
            // adding records:
            $out .= $this->addFilePart(serialize($this->dat['records']));
            // adding files:
            $out .= $this->addFilePart(serialize($this->dat['files'] ?? null));
            // adding files_fal:
            $out .= $this->addFilePart(serialize($this->dat['files_fal'] ?? null));
        }
        return $out;
    }

    /**
     * Creates XML string from input array
     *
     * @return string XML content
     */
    protected function createXML(): string
    {
        // Options:
        $options = [
            'alt_options' => [
                '/header' => [
                    'disableTypeAttrib' => true,
                    'clearStackPath' => true,
                    'parentTagMap' => [
                        'files' => 'file',
                        'files_fal' => 'file',
                        'records' => 'table',
                        'table' => 'rec',
                        'rec:rels' => 'relations',
                        'relations' => 'element',
                        'filerefs' => 'file',
                        'pid_lookup' => 'page_contents',
                        'header:relStaticTables' => 'static_tables',
                        'static_tables' => 'tablename',
                        'excludeMap' => 'item',
                        'softrefCfg' => 'softrefExportMode',
                        'extensionDependencies' => 'extkey',
                        'softrefs' => 'softref_element',
                    ],
                    'alt_options' => [
                        '/pagetree' => [
                            'disableTypeAttrib' => true,
                            'useIndexTagForNum' => 'node',
                            'parentTagMap' => [
                                'node:subrow' => 'node',
                            ],
                        ],
                        '/pid_lookup/page_contents' => [
                            'disableTypeAttrib' => true,
                            'parentTagMap' => [
                                'page_contents' => 'table',
                            ],
                            'grandParentTagMap' => [
                                'page_contents/table' => 'item',
                            ],
                        ],
                    ],
                ],
                '/records' => [
                    'disableTypeAttrib' => true,
                    'parentTagMap' => [
                        'records' => 'tablerow',
                        'tablerow:data' => 'fieldlist',
                        'tablerow:rels' => 'related',
                        'related' => 'field',
                        'field:itemArray' => 'relations',
                        'field:flexFormRels' => 'flexform',
                        'relations' => 'element',
                        'filerefs' => 'file',
                        'flexform:db' => 'db_relations',
                        'flexform:softrefs' => 'softref_relations',
                        'softref_relations' => 'structurePath',
                        'db_relations' => 'path',
                        'path' => 'element',
                        'keys' => 'softref_key',
                        'softref_key' => 'softref_element',
                    ],
                    'alt_options' => [
                        '/records/tablerow/fieldlist' => [
                            'useIndexTagForAssoc' => 'field',
                        ],
                    ],
                ],
                '/files' => [
                    'disableTypeAttrib' => true,
                    'parentTagMap' => [
                        'files' => 'file',
                    ],
                ],
                '/files_fal' => [
                    'disableTypeAttrib' => true,
                    'parentTagMap' => [
                        'files_fal' => 'file',
                    ],
                ],
            ],
        ];
        // Creating XML file from $outputArray:
        $charset = $this->dat['header']['charset'] ?: 'utf-8';
        $XML = '<?xml version="1.0" encoding="' . $charset . '" standalone="yes" ?>' . LF;
        $XML .= (new Typo3XmlSerializer())->encodeWithReturningExceptionAsString(
            $this->dat,
            new Typo3XmlParserOptions([Typo3XmlParserOptions::ROOT_NODE_NAME => 'T3RecordDocument']),
            $options
        );
        return $XML;
    }

    /**
     * Returns a content part for a filename being build.
     *
     * @param string $data Data to store in part
     * @return string Content stream.
     */
    protected function addFilePart(string $data): string
    {
        $compress = $this->exportFileType === self::FILETYPE_T3DZ;
        if ($compress) {
            $data = (string)gzcompress($data);
        }
        return md5($data) . ':' . ($compress ? '1' : '0') . ':' . str_pad((string)strlen($data), 10, '0', STR_PAD_LEFT) . ':' . $data . ':';
    }

    public function saveToFile(): File
    {
        $saveFolder = $this->getOrCreateDefaultImportExportFolder();
        $fileName = $this->getOrGenerateExportFileNameWithFileExtension();
        $filesFolderName = $fileName . '.files';
        $fileContent = $this->render();

        if (!($saveFolder?->checkActionPermission('write'))) {
            throw new InsufficientFolderWritePermissionsException(
                'You are not allowed to write to the target folder "' . $saveFolder->getPublicUrl() . '"',
                1602432207
            );
        }

        if ($saveFolder->hasFolder($filesFolderName)) {
            $saveFolder->getSubfolder($filesFolderName)->delete();
        }

        $temporaryFileName = GeneralUtility::tempnam('export');
        GeneralUtility::writeFile($temporaryFileName, $fileContent, true);
        $file = $saveFolder->addFile($temporaryFileName, $fileName, DuplicationBehavior::REPLACE);

        if ($this->saveFilesOutsideExportFile) {
            $filesFolder = $saveFolder->createFolder($filesFolderName);
            $temporaryFilesForExport = GeneralUtility::getFilesInDir($this->getOrCreateTemporaryFolderName(), '', true);
            foreach ($temporaryFilesForExport as $temporaryFileForExport) {
                $filesFolder->addFile($temporaryFileForExport);
            }
            $this->removeTemporaryFolderName();
        }

        return $file;
    }

    public function getExportFileName(): string
    {
        return $this->exportFileName;
    }

    public function setExportFileName(string $exportFileName): void
    {
        $exportFileName = trim((string)preg_replace('/[^[:alnum:]._-]*/', '', $exportFileName));
        $this->exportFileName = $exportFileName;
    }

    public function getOrGenerateExportFileNameWithFileExtension(): string
    {
        if (!empty($this->exportFileName)) {
            $exportFileName = $this->exportFileName;
        } else {
            $exportFileName = $this->generateExportFileName();
        }
        $exportFileName .= $this->getFileExtensionByFileType();

        return $exportFileName;
    }

    protected function generateExportFileName(): string
    {
        if ($this->pid !== -1) {
            $exportFileName = 'tree_PID' . $this->pid . '_L' . $this->levels;
        } elseif (!empty($this->getRecord())) {
            $exportFileName = 'recs_' . implode('-', $this->getRecord());
            $exportFileName = str_replace(':', '_', $exportFileName);
        } elseif (!empty($this->getList())) {
            $exportFileName = 'list_' . implode('-', $this->getList());
            $exportFileName = str_replace(':', '_', $exportFileName);
        } else {
            $exportFileName = 'export';
        }

        $exportFileName = substr(trim((string)preg_replace('/[^[:alnum:]_-]/', '-', $exportFileName)), 0, 20);

        return 'T3D_' . $exportFileName . '_' . date('Y-m-d_H-i');
    }

    public function getExportFileType(): string
    {
        return $this->exportFileType;
    }

    public function setExportFileType(string $exportFileType): void
    {
        $supportedFileTypes = $this->getSupportedFileTypes();
        if (!in_array($exportFileType, $supportedFileTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'File type "%s" is not valid. Supported file types are %s.',
                    $exportFileType,
                    implode(', ', array_map(static function ($fileType) {
                        return '"' . $fileType . '"';
                    }, $supportedFileTypes))
                ),
                1602505264
            );
        }
        $this->exportFileType = $exportFileType;
    }

    public function getSupportedFileTypes(): array
    {
        if (empty($this->supportedFileTypes)) {
            $supportedFileTypes = [];
            $supportedFileTypes[] = self::FILETYPE_XML;
            $supportedFileTypes[] = self::FILETYPE_T3D;
            if ($this->compressionAvailable) {
                $supportedFileTypes[] = self::FILETYPE_T3DZ;
            }
            $this->supportedFileTypes = $supportedFileTypes;
        }
        return $this->supportedFileTypes;
    }

    protected function getFileExtensionByFileType(): string
    {
        return match ($this->exportFileType) {
            self::FILETYPE_XML => '.xml',
            self::FILETYPE_T3D => '.t3d',
            default => '-z.t3d',
        };
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function setList(array $list): void
    {
        $this->list = $list;
    }

    public function getLevels(): int
    {
        return $this->levels;
    }

    public function setLevels(int $levels): void
    {
        $this->levels = $levels;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables(array $tables): void
    {
        $this->tables = $tables;
    }

    public function getRelOnlyTables(): array
    {
        return $this->relOnlyTables;
    }

    public function setRelOnlyTables(array $relOnlyTables): void
    {
        $this->relOnlyTables = $relOnlyTables;
    }

    public function getTreeHTML(): string
    {
        return $this->treeHTML;
    }

    public function isIncludeExtFileResources(): bool
    {
        return $this->includeExtFileResources;
    }

    public function setIncludeExtFileResources(bool $includeExtFileResources): void
    {
        $this->includeExtFileResources = $includeExtFileResources;
    }

    /**
     * Option to enable having the files not included in the export file.
     * The files are saved to a temporary folder instead.
     *
     * @see ImportExport::getOrCreateTemporaryFolderName()
     */
    public function setSaveFilesOutsideExportFile(bool $saveFilesOutsideExportFile): void
    {
        $this->saveFilesOutsideExportFile = $saveFilesOutsideExportFile;
    }

    public function isSaveFilesOutsideExportFile(): bool
    {
        return $this->saveFilesOutsideExportFile;
    }
}
