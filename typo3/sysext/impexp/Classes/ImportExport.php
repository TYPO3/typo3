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

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * T3D file Import / Export library (TYPO3 Record Document)
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
abstract class ImportExport
{
    /**
     * Whether "import" or "export" mode of object.
     *
     * @var string
     */
    protected $mode = '';

    /**
     * A WHERE clause for selection records from the pages table based on read-permissions of the current backend user.
     *
     * @var string
     */
    protected $permsClause;

    /**
     * Root page of import or export page tree
     *
     * @var int
     */
    protected $pid = -1;

    /**
     * Root page record of import or of export page tree
     */
    protected ?array $pidRecord = null;

    /**
     * If set, static relations (not exported) will be shown in preview as well
     *
     * @var bool
     */
    protected $showStaticRelations = false;

    /**
     * Updates all records that has same UID instead of creating new!
     *
     * @var bool
     */
    protected $update = false;

    /**
     * Set by importData() when an import is started.
     *
     * @var bool
     */
    protected $doesImport = false;

    /**
     * Setting the import mode for specific import records.
     * Available options are: force_uid, as_new, exclude, ignore_pid, respect_pid
     *
     * @var array
     */
    protected $importMode = [];

    /**
     * If set, PID correct is ignored globally
     *
     * @var bool
     */
    protected $globalIgnorePid = false;

    /**
     * If set, all UID values are forced! (update or import)
     *
     * @var bool
     */
    protected $forceAllUids = false;

    /**
     * If set, a diff-view column is added to the preview.
     *
     * @var bool
     */
    protected $showDiff = false;

    /**
     * Array of values to substitute in editable soft references.
     *
     * @var array
     */
    protected $softrefInputValues = [];

    /**
     * Mapping between the fileID from import memory and the final filenames they are written to.
     *
     * @var array
     */
    protected $fileIdMap = [];

    /**
     * Add tables names here which should not be exported with the file.
     * (Where relations should be mapped to same UIDs in target system).
     *
     * @var array
     */
    protected $relStaticTables = [];

    /**
     * Exclude map. Keys are table:uid pairs and if set, records are not added to the export.
     *
     * @var array
     */
    protected $excludeMap = [];

    /**
     * Soft reference token ID modes.
     *
     * @var array
     */
    protected $softrefCfg = [];

    /**
     * Listing extension dependencies.
     *
     * @var array
     */
    protected $extensionDependencies = [];

    /**
     * After records are written this array is filled with [table][original_uid] = [new_uid]
     *
     * @var array
     */
    protected $importMapId = [];

    /**
     * Error log.
     *
     * @var array
     */
    protected $errorLog = [];

    /**
     * Cache for record paths
     *
     * @var array
     */
    protected $cacheGetRecordPath = [];

    /**
     * Internal import/export memory
     *
     * @var array
     */
    protected $dat = [];

    /**
     * File processing object
     *
     * @var ExtendedFileUtility
     */
    protected $fileProcObj;

    /**
     * @var DiffUtility
     */
    protected $diffUtility;

    /**
     * @var array
     */
    protected $remainHeader = [];

    /**
     * @var LanguageService
     */
    protected $lang;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Name of the "fileadmin" folder where files for export/import should be located
     *
     * @var string
     */
    protected $fileadminFolderName = '';

    protected ?string $temporaryFolderName = null;
    protected ?Folder $defaultImportExportFolder = null;

    /**
     * Flag to control whether all disabled records and their children are excluded (true) or included (false). Defaults
     * to the old behaviour of including everything.
     *
     * @var bool
     */
    protected $excludeDisabledRecords = false;

    /**
     * Array of currently registered storage objects
     *
     * @var ResourceStorage[]
     */
    protected $storages = [];

    /**
     * Array of currently registered storage objects available for importing files to
     *
     * @var ResourceStorage[]
     */
    protected $storagesAvailableForImport = [];

    /**
     * Currently registered default storage object
     */
    protected ?ResourceStorage $defaultStorage = null;

    /**
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->lang = $this->getLanguageService();
        $this->lang->includeLLFile('EXT:impexp/Resources/Private/Language/locallang.xlf');
        $this->permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        $this->fetchStorages();
    }

    /**
     * Fetch all available file storages and index by storage UID
     *
     * Note: It also creates a default storage record if the database table sys_file_storage is empty,
     * e.g. during tests.
     */
    protected function fetchStorages(): void
    {
        $this->storages = [];
        $this->storagesAvailableForImport = [];
        $this->defaultStorage = null;

        $this->getStorageRepository()->flush();

        $storages = $this->getStorageRepository()->findAll();
        // @todo: Why by reference here? Test ImagesWithStoragesTest::importMultipleImagesWithMultipleStorages fails otherwise
        foreach ($storages as &$storage) {
            $this->storages[$storage->getUid()] = $storage;
            if ($storage->isOnline() && $storage->isWritable() && $storage->getDriverType() === 'Local') {
                $this->storagesAvailableForImport[$storage->getUid()] = $storage;
            }
            if ($this->defaultStorage === null && $storage->isDefault()) {
                $this->defaultStorage = $storage;
            }
        }
    }

    /********************************************************
     * Visual rendering of import/export memory, $this->dat
     ********************************************************/

    /**
     * Displays a preview of the import or export.
     *
     * @return array The preview data
     */
    public function renderPreview(): array
    {
        // @todo: Why is this done?
        unset($this->dat['files']);

        $previewData = [
            'update' => $this->update,
            'showDiff' => $this->showDiff,
            'insidePageTree' => [],
            'outsidePageTree' => [],
        ];

        if (!isset($this->dat['header']['pagetree']) && !isset($this->dat['header']['records'])) {
            return $previewData;
        }

        // Traverse header:
        $this->remainHeader = $this->dat['header'];

        // Preview of the page tree to be exported
        if (is_array($this->dat['header']['pagetree'] ?? null)) {
            $this->traversePageTree($this->dat['header']['pagetree'], $previewData['insidePageTree']);
            foreach ($previewData['insidePageTree'] as &$line) {
                $line['controls'] = $this->renderControls($line);
                $line['message'] = (($line['msg'] ?? '') && !$this->doesImport ? '<span class="text-danger">' . htmlspecialchars($line['msg']) . '</span>' : '');
            }
        }

        // Preview the remaining records that were not included in the page tree
        if (is_array($this->remainHeader['records'] ?? null)) {
            if (is_array($this->remainHeader['records']['pages'] ?? null)) {
                $this->traversePageRecords($this->remainHeader['records']['pages'], $previewData['outsidePageTree']);
            }
            $this->traverseAllRecords($this->remainHeader['records'], $previewData['outsidePageTree']);
            foreach ($previewData['outsidePageTree'] as &$line) {
                $line['controls'] = $this->renderControls($line);
                $line['message'] = (($line['msg'] ?? '') && !$this->doesImport ? '<span class="text-danger">' . htmlspecialchars($line['msg']) . '</span>' : '');
            }
        }

        return $previewData;
    }

    /**
     * Go through page tree for display
     *
     * @param array<int, array> $pageTree Page tree array with uid/subrow (from ->dat[header][pagetree])
     * @param array $lines Output lines array
     * @param int $indent Indentation level
     */
    protected function traversePageTree(array $pageTree, array &$lines, int $indent = 0): void
    {
        foreach ($pageTree as $pageUid => $page) {
            if ($this->excludeDisabledRecords === true && $this->isRecordDisabled('pages', $pageUid)) {
                $this->excludePageAndRecords($pageUid, $page);
                continue;
            }

            // Add page
            $this->addRecord('pages', $pageUid, $lines, $indent);

            // Add records
            if (is_array($this->dat['header']['pid_lookup'][$pageUid] ?? null)) {
                foreach ($this->dat['header']['pid_lookup'][$pageUid] as $table => $records) {
                    $table = (string)$table;
                    if ($table !== 'pages') {
                        foreach (array_keys($records) as $uid) {
                            $this->addRecord($table, (int)$uid, $lines, $indent + 2);
                        }
                    }
                }
                unset($this->remainHeader['pid_lookup'][$pageUid]);
            }

            // Add subtree
            if (is_array($page['subrow'] ?? null)) {
                $this->traversePageTree($page['subrow'], $lines, $indent + 2);
            }
        }
    }

    /**
     * Test whether a record is disabled (e.g. hidden)
     *
     * @param string $table Name of the records' database table
     * @param int $uid Database uid of the record
     * @return bool true if the record is disabled, false otherwise
     */
    protected function isRecordDisabled(string $table, int $uid): bool
    {
        return (bool)($this->dat['records'][$table . ':' . $uid]['data'][
            $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? ''
        ] ?? false);
    }

    /**
     * Exclude a page, its sub pages (recursively) and records placed in them from this import/export
     *
     * @param int $pageUid Uid of the page to exclude
     * @param array $page Page array with uid/subrow (from ->dat[header][pagetree])
     */
    protected function excludePageAndRecords(int $pageUid, array $page): void
    {
        // Exclude page
        unset($this->remainHeader['records']['pages'][$pageUid]);

        // Exclude records
        if (is_array($this->dat['header']['pid_lookup'][$pageUid] ?? null)) {
            foreach ($this->dat['header']['pid_lookup'][$pageUid] as $table => $records) {
                if ($table !== 'pages') {
                    foreach (array_keys($records) as $uid) {
                        unset($this->remainHeader['records'][$table][$uid]);
                    }
                }
            }
            unset($this->remainHeader['pid_lookup'][$pageUid]);
        }

        // Exclude subtree
        if (is_array($page['subrow'] ?? null)) {
            foreach ($page['subrow'] as $subPageUid => $subPage) {
                $this->excludePageAndRecords($subPageUid, $subPage);
            }
        }
    }

    /**
     * Go through remaining pages (not in tree)
     *
     * @param array<int, array> $pageTree Page tree array with uid/subrow (from ->dat[header][pagetree])
     * @param array $lines Output lines array
     */
    protected function traversePageRecords(array $pageTree, array &$lines): void
    {
        foreach ($pageTree as $pageUid => $_) {
            // Add page
            $this->addRecord('pages', (int)$pageUid, $lines, 0, true);

            // Add records
            if (is_array($this->dat['header']['pid_lookup'][$pageUid] ?? null)) {
                foreach ($this->dat['header']['pid_lookup'][$pageUid] as $table => $records) {
                    if ($table !== 'pages') {
                        foreach (array_keys($records) as $uid) {
                            $this->addRecord((string)$table, (int)$uid, $lines, 2);
                        }
                    }
                }
                unset($this->remainHeader['pid_lookup'][$pageUid]);
            }
        }
    }

    /**
     * Go through ALL records (if the pages are displayed first, those will not be among these!)
     *
     * @param array $pageTree Page tree array with uid/subrow (from ->dat[header][pagetree])
     * @param array $lines Output lines array
     */
    protected function traverseAllRecords(array $pageTree, array &$lines): void
    {
        foreach ($pageTree as $table => $records) {
            $this->addGeneralErrorsByTable($table);
            if ($table !== 'pages') {
                foreach (array_keys($records) as $uid) {
                    $this->addRecord((string)$table, (int)$uid, $lines, 0, true);
                }
            }
        }
    }

    /**
     * Log general error message for a given table
     *
     * @param string $table database table name
     */
    protected function addGeneralErrorsByTable(string $table): void
    {
        if ($this->update && $table === 'sys_file') {
            $this->addError('Updating sys_file records is not supported! They will be imported as new records!');
        }
        if ($this->forceAllUids && $table === 'sys_file') {
            $this->addError('Forcing uids of sys_file records is not supported! They will be imported as new records!');
        }
    }

    /**
     * Add a record, its relations and soft references, to the preview
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param array $lines Output lines array
     * @param int $indent Indentation level
     * @param bool $checkImportInPidRecord If you want import validation, you can set this so it checks if the import can take place on the specified page.
     */
    protected function addRecord(string $table, int $uid, array &$lines, int $indent, bool $checkImportInPidRecord = false): void
    {
        $record = $this->dat['header']['records'][$table][$uid] ?? null;
        unset($this->remainHeader['records'][$table][$uid]);
        if (!is_array($record) && !($table === 'pages' && $uid === 0)) {
            $this->addError('MISSING RECORD: ' . $table . ':' . $uid);
        }

        // Create record information for preview
        $line = [];
        $line['ref'] = $table . ':' . $uid;
        $line['type'] = 'record';
        $line['msg'] = '';
        if ($table === '_SOFTREF_') {
            // Record is a soft reference
            $line['preCode'] = $this->renderIndent($indent);
            $line['title'] = '<em>' . htmlspecialchars($this->lang->getLL('impexpcore_singlereco_softReferencesFiles')) . '</em>';
        } elseif (!isset($GLOBALS['TCA'][$table])) {
            // Record is of unknown table
            $line['preCode'] = $this->renderIndent($indent);
            $line['title'] = '<em>' . htmlspecialchars((string)$record['title']) . '</em>';
            $line['msg'] = 'UNKNOWN TABLE "' . $line['ref'] . '"';
        } else {
            $pidRecord = $this->getPidRecord();
            $icon = $this->iconFactory->getIconForRecord(
                $table,
                (array)($this->dat['records'][$table . ':' . $uid]['data'] ?? []),
                Icon::SIZE_SMALL
            )->render();
            $line['preCode'] = sprintf(
                '%s<span title="%s">%s</span>',
                $this->renderIndent($indent),
                htmlspecialchars($line['ref']),
                $icon
            );
            $line['title'] = htmlspecialchars($record['title'] ?? '');
            // Link to page view
            if ($table === 'pages') {
                $viewID = $this->mode === 'export' ? $uid : ($this->doesImport ? ($this->importMapId['pages'][$uid] ?? 0) : 0);
                if ($viewID) {
                    $attributes = PreviewUriBuilder::create($viewID)->serializeDispatcherAttributes();
                    $line['title'] = sprintf('<a href="#" %s>%s</a>', $attributes, $line['title']);
                }
            }
            $line['active'] = !$this->isRecordDisabled($table, $uid) ? 'active' : 'hidden';
            if ($this->mode === 'import' && $pidRecord !== null) {
                if ($checkImportInPidRecord) {
                    if (!$this->getBackendUser()->doesUserHaveAccess($pidRecord, ($table === 'pages' ? 8 : 16))) {
                        $line['msg'] .= '"' . $line['ref'] . '" cannot be INSERTED on this page! ';
                    }
                    if ($this->pid > 0 && !$this->checkDokType($table, $pidRecord['doktype']) && !$GLOBALS['TCA'][$table]['ctrl']['rootLevel']) {
                        $line['msg'] .= '"' . $table . '" cannot be INSERTED on this page type (change page type to "Folder".) ';
                    }
                }
                if (!$this->getBackendUser()->check('tables_modify', $table)) {
                    $line['msg'] .= 'You are not allowed to CREATE "' . $table . '" tables! ';
                }
                if ($GLOBALS['TCA'][$table]['ctrl']['readOnly'] ?? false) {
                    $line['msg'] .= 'TABLE "' . $table . '" is READ ONLY! ';
                }
                if (($GLOBALS['TCA'][$table]['ctrl']['adminOnly'] ?? false) && !$this->getBackendUser()->isAdmin()) {
                    $line['msg'] .= 'TABLE "' . $table . '" is ADMIN ONLY! ';
                }
                if ($GLOBALS['TCA'][$table]['ctrl']['is_static'] ?? false) {
                    $line['msg'] .= 'TABLE "' . $table . '" is a STATIC TABLE! ';
                }
                if ((int)($GLOBALS['TCA'][$table]['ctrl']['rootLevel'] ?? 0) === 1) {
                    $line['msg'] .= 'TABLE "' . $table . '" will be inserted on ROOT LEVEL! ';
                }
                $databaseRecord = null;
                if ($this->update) {
                    $databaseRecord = $this->getRecordFromDatabase($table, $uid, $this->showDiff ? '*' : 'uid,pid');
                    if ($databaseRecord === null) {
                        $line['updatePath'] = '<strong>NEW!</strong>';
                    } else {
                        $line['updatePath'] = htmlspecialchars($this->getRecordPath((int)($databaseRecord['pid'] ?? 0)));
                    }
                    if ($table === 'sys_file') {
                        $line['updateMode'] = '';
                    } else {
                        $line['updateMode'] = $this->renderImportModeSelector(
                            $table,
                            $uid,
                            $databaseRecord !== null
                        );
                    }
                }
                // Diff view
                if ($this->showDiff) {
                    $diffInverse = $this->update ? true : false;
                    // For imports, get new id:
                    if (isset($this->importMapId[$table][$uid]) && $newUid = $this->importMapId[$table][$uid]) {
                        $diffInverse = false;
                        $databaseRecord = $this->getRecordFromDatabase($table, $newUid, '*');
                        BackendUtility::workspaceOL($table, $databaseRecord);
                    }
                    $importRecord = $this->dat['records'][$table . ':' . $uid]['data'] ?? null;
                    if (is_array($databaseRecord) && is_array($importRecord)) {
                        $line['showDiffContent'] = $this->compareRecords($databaseRecord, $importRecord, $table, $diffInverse);
                    } else {
                        $line['showDiffContent'] = 'ERROR: One of the inputs were not an array!';
                    }
                }
            }
        }
        $lines[] = $line;

        // File relations
        if (is_array($record['filerefs'] ?? null)) {
            $this->addFiles($record['filerefs'], $lines, $indent);
        }
        // Database relations
        if (is_array($record['rels'] ?? null)) {
            $this->addRelations($record['rels'], $lines, $indent);
        }
        // Soft references
        if (is_array($record['softrefs'] ?? null)) {
            $this->addSoftRefs($record['softrefs'], $lines, $indent);
        }
    }

    /**
     * Add database relations of a record to the preview
     *
     * @param array $relations Array of relations
     * @param array $lines Output lines array
     * @param int $indent Indentation level
     * @param array $recursionCheck Recursion check stack
     *
     * @see addRecord()
     */
    protected function addRelations(array $relations, array &$lines, int $indent, array $recursionCheck = []): void
    {
        foreach ($relations as $relation) {
            $table = $relation['table'];
            $uid = $relation['id'];
            $line = [];
            $line['ref'] = $table . ':' . $uid;
            $line['type'] = 'rel';
            $line['msg'] = '';
            if (in_array($line['ref'], $recursionCheck, true)) {
                continue;
            }
            $iconName = 'status-status-checked';
            $iconClass = '';
            $staticFixed = false;
            $record = null;
            if ($uid > 0) {
                $record = $this->dat['header']['records'][$table][$uid] ?? null;
                if (!is_array($record)) {
                    if ($this->isTableStatic($table) || $this->isRecordExcluded($table, (int)$uid)
                        || ($relation['tokenID'] ?? '') && !$this->isSoftRefIncluded($relation['tokenID'] ?? '')) {
                        $line['title'] = htmlspecialchars('STATIC: ' . $line['ref']);
                        $iconClass = 'text-info';
                        $staticFixed = true;
                    } else {
                        $databaseRecord = $this->getRecordFromDatabase($table, (int)$uid);
                        $recordPath = $this->getRecordPath($databaseRecord === null ? 0 : ($table === 'pages' ? (int)$databaseRecord['uid'] : (int)$databaseRecord['pid']));
                        $line['title'] = sprintf(
                            '<span title="%s">%s</span>',
                            htmlspecialchars($recordPath),
                            htmlspecialchars($line['ref'])
                        );
                        $line['msg'] = 'LOST RELATION' . ($databaseRecord === null ? ' (Record not found!)' : ' (Path: ' . $recordPath . ')');
                        $iconClass = 'text-danger';
                        $iconName = 'status-dialog-warning';
                    }
                } else {
                    $recordPath = $this->getRecordPath($table === 'pages' ? (int)$record['uid'] : (int)$record['pid']);
                    $line['title'] = sprintf(
                        '<span title="%s">%s</span>',
                        htmlspecialchars($recordPath),
                        htmlspecialchars((string)$record['title'])
                    );
                }
            } else {
                // Negative values in relation fields. These are typically fields of sys_language, fe_users etc.
                // They are static values. They CAN theoretically be negative pointers to uids in other tables,
                // but this is so rarely used that it is not supported.
                $line['title'] = htmlspecialchars('FIXED: ' . $line['ref']);
                $staticFixed = true;
            }

            $icon = $this->iconFactory->getIcon($iconName, Icon::SIZE_SMALL)->render();
            $line['preCode'] = sprintf(
                '%s<span class="%s" title="%s">%s</span>',
                $this->renderIndent($indent + 2),
                $iconClass,
                htmlspecialchars($line['ref']),
                $icon
            );
            if (!$staticFixed || $this->showStaticRelations) {
                $lines[] = $line;
                if (is_array($record) && is_array($record['rels'] ?? null)) {
                    $this->addRelations($record['rels'], $lines, $indent + 1, array_merge($recursionCheck, [$line['ref']]));
                }
            }
        }
    }

    /**
     * Add file relations of a record to the preview.
     *
     * Public access for testing purpose only.
     *
     * @param array $relations Array of file IDs
     * @param array $lines Output lines array
     * @param int $indent Indentation level
     * @param string $tokenID Token ID if this is a soft reference (in which case it only makes sense with a single element in the $relations array!)
     *
     * @see addRecord()
     */
    public function addFiles(array $relations, array &$lines, int $indent, string $tokenID = ''): void
    {
        foreach ($relations as $ID) {
            $line = [];
            $line['msg'] = '';
            $fileInfo = $this->dat['header']['files'][$ID];
            if (!is_array($fileInfo)) {
                if ($tokenID !== '' || $this->isSoftRefIncluded($tokenID)) {
                    $line['msg'] = 'MISSING FILE: ' . $ID;
                    $this->addError('MISSING FILE: ' . $ID);
                } else {
                    return;
                }
            }
            $line['ref'] = 'FILE';
            $line['type'] = 'file';
            $line['preCode'] = sprintf(
                '%s<span title="%s">%s</span>',
                $this->renderIndent($indent + 2),
                htmlspecialchars($line['ref']),
                $this->iconFactory->getIcon('status-reference-hard', Icon::SIZE_SMALL)->render()
            );
            $line['title'] = htmlspecialchars($fileInfo['filename']);
            $line['showDiffContent'] = PathUtility::stripPathSitePrefix((string)($this->fileIdMap[$ID] ?? ''));
            // If import mode and there is a non-RTE soft reference, check the destination directory.
            if ($this->mode === 'import' && $tokenID !== '' && !($fileInfo['RTE_ORIG_ID'] ?? false)) {
                // Check folder existence
                if (isset($fileInfo['parentRelFileName'])) {
                    $line['msg'] = 'Seems like this file is already referenced from within an HTML/CSS file. That takes precedence. ';
                } else {
                    $origDirPrefix = PathUtility::dirname($fileInfo['relFileName']) . '/';
                    $dirPrefix = $this->resolveStoragePath($origDirPrefix);
                    if ($dirPrefix === null) {
                        $line['msg'] = 'ERROR: There are no available file mounts to write file in! ';
                    } elseif ($origDirPrefix !== $dirPrefix) {
                        $line['msg'] = 'File will be attempted written to "' . $dirPrefix . '". ';
                    }
                }
                // Check file existence
                if (file_exists(Environment::getPublicPath() . '/' . $fileInfo['relFileName'])) {
                    if ($this->update) {
                        $line['updatePath'] .= 'File exists.';
                    } else {
                        $line['msg'] .= 'File already exists! ';
                    }
                }
                // Check file extension
                $fileProcObj = $this->getFileProcObj();
                if ($fileProcObj->actionPerms['addFile']) {
                    $pathInfo = GeneralUtility::split_fileref(Environment::getPublicPath() . '/' . $fileInfo['relFileName']);
                    if (!GeneralUtility::makeInstance(FileNameValidator::class)->isValid($pathInfo['file'])) {
                        $line['msg'] .= 'File extension was not allowed!';
                    }
                } else {
                    $line['msg'] = 'Your user profile does not allow you to create files on the server!';
                }
            }
            $lines[] = $line;
            unset($this->remainHeader['files'][$ID]);

            // RTE originals
            if ($fileInfo['RTE_ORIG_ID'] ?? false) {
                $ID = $fileInfo['RTE_ORIG_ID'];
                $line = [];
                $fileInfo = $this->dat['header']['files'][$ID];
                if (!is_array($fileInfo)) {
                    $line['msg'] = 'MISSING RTE original FILE: ' . $ID;
                    $this->addError('MISSING RTE original FILE: ' . $ID);
                }
                $line['ref'] = 'FILE';
                $line['type'] = 'file';
                $line['preCode'] = sprintf(
                    '%s<span title="%s">%s</span>',
                    $this->renderIndent($indent + 4),
                    htmlspecialchars($line['ref']),
                    $this->iconFactory->getIcon('status-reference-hard', Icon::SIZE_SMALL)->render()
                );
                $line['title'] = htmlspecialchars($fileInfo['filename']) . ' <em>(Original)</em>';
                $line['showDiffContent'] = PathUtility::stripPathSitePrefix($this->fileIdMap[$ID]);
                $lines[] = $line;
                unset($this->remainHeader['files'][$ID]);
            }

            // External resources
            if (is_array($fileInfo['EXT_RES_ID'] ?? null)) {
                foreach ($fileInfo['EXT_RES_ID'] as $extID) {
                    $line = [];
                    $fileInfo = $this->dat['header']['files'][$extID];
                    if (!is_array($fileInfo)) {
                        $line['msg'] = 'MISSING External Resource FILE: ' . $extID;
                        $this->addError('MISSING External Resource FILE: ' . $extID);
                    } else {
                        $line['updatePath'] = $fileInfo['parentRelFileName'];
                    }
                    $line['ref'] = 'FILE';
                    $line['type'] = 'file';
                    $line['preCode'] = sprintf(
                        '%s<span title="%s">%s</span>',
                        $this->renderIndent($indent + 4),
                        htmlspecialchars($line['ref']),
                        $this->iconFactory->getIcon('actions-insert-reference', Icon::SIZE_SMALL)->render()
                    );
                    $line['title'] = htmlspecialchars($fileInfo['filename']) . ' <em>(Resource)</em>';
                    $line['showDiffContent'] = PathUtility::stripPathSitePrefix($this->fileIdMap[$extID]);
                    $lines[] = $line;
                    unset($this->remainHeader['files'][$extID]);
                }
            }
        }
    }

    /**
     * Add soft references of a record to the preview
     *
     * @param array $softrefs Soft references
     * @param array $lines Output lines array
     * @param int $indent Indentation level
     *
     * @see addRecord()
     */
    protected function addSoftRefs(array $softrefs, array &$lines, int $indent): void
    {
        foreach ($softrefs as $softref) {
            $line = [];
            $line['ref'] = 'SOFTREF';
            $line['type'] = 'softref';
            $line['msg'] = '';
            $line['preCode'] = sprintf(
                '%s<span title="%s">%s</span>',
                $this->renderIndent($indent + 2),
                htmlspecialchars($line['ref']),
                $this->iconFactory->getIcon('status-reference-soft', Icon::SIZE_SMALL)->render()
            );
            $line['title'] = sprintf(
                '<em>%s, "%s"</em> : <span title="%s">%s</span>',
                $softref['field'],
                $softref['spKey'],
                htmlspecialchars($softref['matchString'] ?? ''),
                htmlspecialchars(GeneralUtility::fixed_lgd_cs($softref['matchString'] ?? '', 60))
            );
            if ($softref['subst']['type'] ?? false) {
                if ($softref['subst']['title'] ?? false) {
                    $line['title'] .= sprintf(
                        '<br/>%s<strong>%s</strong> %s',
                        $this->renderIndent($indent + 4),
                        htmlspecialchars($this->lang->getLL('impexpcore_singlereco_title')),
                        htmlspecialchars(GeneralUtility::fixed_lgd_cs($softref['subst']['title'], 60))
                    );
                }
                if ($softref['subst']['description'] ?? false) {
                    $line['title'] .= sprintf(
                        '<br/>%s<strong>%s</strong> %s',
                        $this->renderIndent($indent + 4),
                        htmlspecialchars($this->lang->getLL('impexpcore_singlereco_descr')),
                        htmlspecialchars(GeneralUtility::fixed_lgd_cs($softref['subst']['description'], 60))
                    );
                }
                if ($softref['subst']['type'] === 'db') {
                    $line['title'] .= sprintf(
                        '<br/>%s%s <strong>%s</strong>',
                        $this->renderIndent($indent + 4),
                        htmlspecialchars($this->lang->getLL('impexpcore_softrefsel_record')),
                        $softref['subst']['recordRef']
                    );
                } elseif ($softref['subst']['type'] === 'file') {
                    $line['title'] .= sprintf(
                        '<br/>%s%s <strong>%s</strong>',
                        $this->renderIndent($indent + 4),
                        htmlspecialchars($this->lang->getLL('impexpcore_singlereco_filename')),
                        $softref['subst']['relFileName']
                    );
                } elseif ($softref['subst']['type'] === 'string') {
                    $line['title'] .= sprintf(
                        '<br/>%s%s <strong>%s</strong>',
                        $this->renderIndent($indent + 4),
                        htmlspecialchars($this->lang->getLL('impexpcore_singlereco_value')),
                        $softref['subst']['tokenValue']
                    );
                }
            }
            $line['_softRefInfo'] = $softref;
            $mode = $this->softrefCfg[$softref['subst']['tokenID'] ?? null]['mode'] ?? '';
            if (isset($softref['error']) && $mode !== Import::SOFTREF_IMPORT_MODE_EDITABLE && $mode !== Import::SOFTREF_IMPORT_MODE_EXCLUDE) {
                $line['msg'] .= $softref['error'];
            }
            $lines[] = $line;

            // Add database relations
            if (($softref['subst']['type'] ?? '') === 'db') {
                [$referencedTable, $referencedUid] = explode(':', $softref['subst']['recordRef']);
                $relations = [['table' => $referencedTable, 'id' => $referencedUid, 'tokenID' => $softref['subst']['tokenID']]];
                $this->addRelations($relations, $lines, $indent + 4);
            }
            // Add files relations
            if (($softref['subst']['type'] ?? '') === 'file') {
                $relations = [$softref['file_ID']];
                $this->addFiles($relations, $lines, $indent + 4, $softref['subst']['tokenID']);
            }
        }
    }

    protected function renderIndent(int $indent): string
    {
        return str_repeat('&nbsp;&nbsp;', $indent);
    }

    /**
     * Verifies that a table is allowed on a certain doktype of a page.
     *
     * @param string $table Table name to check
     * @param int $dokType Page doktype
     * @return bool TRUE if OK
     */
    protected function checkDokType(string $table, int $dokType): bool
    {
        $allowedTableList = $GLOBALS['PAGES_TYPES'][$dokType]['allowedTables'] ?? $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
        $allowedArray = GeneralUtility::trimExplode(',', $allowedTableList, true);
        if (str_contains($allowedTableList, '*') || in_array($table, $allowedArray, true)) {
            return true;
        }
        return false;
    }

    /**
     * Render input controls for import or export
     *
     * @param array $line Output line array
     * @return string HTML
     */
    protected function renderControls(array $line): string
    {
        if ($this->mode === 'export') {
            if ($line['type'] === 'record') {
                return $this->renderRecordExcludeCheckbox($line['ref']);
            }
            if ($line['type'] === 'softref') {
                return $this->renderSoftRefExportSelector($line['_softRefInfo']);
            }
        } elseif ($this->mode === 'import') {
            if ($line['type'] === 'softref') {
                return $this->renderSoftRefImportTextField($line['_softRefInfo']);
            }
        }
        return '';
    }

    /**
     * Render check box for exclusion of a record from export.
     *
     * @param string $recordRef The record ID of the form [table]:[id].
     * @return string HTML
     */
    protected function renderRecordExcludeCheckbox(string $recordRef): string
    {
        return sprintf(
            '
            <input type="checkbox" class="t3js-exclude-checkbox" name="tx_impexp[exclude][%1$s]" id="checkExclude%1$s" value="1" />
            <label for="checkExclude%1$s">%2$s</label>',
            $recordRef,
            htmlspecialchars($this->lang->getLL('impexpcore_singlereco_exclude'))
        );
    }

    /**
     * Render text field when importing a soft reference.
     *
     * @param array $softref Soft reference
     * @return string HTML
     */
    protected function renderSoftRefImportTextField(array $softref): string
    {
        if (isset($softref['subst']['tokenID'])) {
            $tokenID = $softref['subst']['tokenID'];
            $cfg = $this->softrefCfg[$tokenID] ?? [];
            if (($cfg['mode'] ?? '') === Import::SOFTREF_IMPORT_MODE_EDITABLE) {
                $html = '';
                if ($cfg['title'] ?? false) {
                    $html .= '<strong>' . htmlspecialchars((string)$cfg['title']) . '</strong><br/>';
                }
                $html .= htmlspecialchars((string)$cfg['description']) . '<br/>';
                $html .= sprintf(
                    '<input type="text" name="tx_impexp[softrefInputValues][%s]" value="%s" />',
                    $tokenID,
                    htmlspecialchars($this->softrefInputValues[$tokenID] ?? $cfg['defValue'])
                );
                return $html;
            }
        }

        return '';
    }

    /**
     * Render select box with export options for soft references.
     * An export box is shown only if a substitution scheme is found for the soft reference.
     *
     * @param array $softref Soft reference
     * @return string HTML
     */
    protected function renderSoftRefExportSelector(array $softref): string
    {
        $fileInfo = isset($softref['file_ID']) ? $this->dat['header']['files'][$softref['file_ID']] : [];
        // Substitution scheme has to be around and RTE images MUST be exported.
        if (isset($softref['subst']['tokenID']) && !isset($fileInfo['RTE_ORIG_ID'])) {
            $options = [];
            $options[''] = '';
            $options[Import::SOFTREF_IMPORT_MODE_EDITABLE] = $this->lang->getLL('impexpcore_softrefsel_editable');
            $options[Import::SOFTREF_IMPORT_MODE_EXCLUDE] = $this->lang->getLL('impexpcore_softrefsel_exclude');
            $value = $this->softrefCfg[$softref['subst']['tokenID']]['mode'] ?? '';
            $selectHtml = $this->renderSelectBox(
                'tx_impexp[softrefCfg][' . $softref['subst']['tokenID'] . '][mode]',
                $value,
                $options
            ) . '<br/>';
            $textFieldHtml = '';
            if ($value === Import::SOFTREF_IMPORT_MODE_EDITABLE) {
                if ($softref['subst']['title'] ?? false) {
                    $textFieldHtml .= sprintf(
                        '
                        <input type="hidden" name="tx_impexp[softrefCfg][%1$s][title]" value="%2$s" />
                        <strong>%2$s</strong><br/>',
                        $softref['subst']['tokenID'],
                        htmlspecialchars($softref['subst']['title'])
                    );
                }
                if (!($softref['subst']['description'] ?? false)) {
                    $textFieldHtml .= sprintf(
                        '
                        %s<br/>
                        <input type="text" name="tx_impexp[softrefCfg][%s][description]" value="%s" />',
                        htmlspecialchars($this->lang->getLL('impexpcore_printerror_description')),
                        $softref['subst']['tokenID'],
                        htmlspecialchars($this->softrefCfg[$softref['subst']['tokenID']]['description'] ?? '')
                    );
                } else {
                    $textFieldHtml .= sprintf(
                        '
                        <input type="hidden" name="tx_impexp[softrefCfg][%1$s][description]" value="%2$s" />%2$s',
                        $softref['subst']['tokenID'],
                        htmlspecialchars($softref['subst']['description'])
                    );
                }
                $textFieldHtml .= sprintf(
                    '
                    <input type="hidden" name="tx_impexp[softrefCfg][%s][defValue]" value="%s" />',
                    $softref['subst']['tokenID'],
                    htmlspecialchars($softref['subst']['tokenValue'])
                );
            }
            return $selectHtml . $textFieldHtml;
        }
        return '';
    }

    /**
     * Render select box with import options for the record.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @param bool $doesRecordExist Is there already a record with this UID in the database?
     * @return string HTML
     */
    protected function renderImportModeSelector(string $table, int $uid, bool $doesRecordExist): string
    {
        $options = [];
        if (!$doesRecordExist) {
            $options[] = $this->lang->getLL('impexpcore_singlereco_insert');
            if ($this->getBackendUser()->isAdmin()) {
                $options[Import::IMPORT_MODE_FORCE_UID] = sprintf($this->lang->getLL('impexpcore_singlereco_forceUidSAdmin'), $uid);
            }
        } else {
            $options[] = $this->lang->getLL('impexpcore_singlereco_update');
            $options[Import::IMPORT_MODE_AS_NEW] = $this->lang->getLL('impexpcore_singlereco_importAsNew');
            if (!$this->globalIgnorePid) {
                $options[Import::IMPORT_MODE_IGNORE_PID] = $this->lang->getLL('impexpcore_singlereco_ignorePid');
            } else {
                $options[Import::IMPORT_MODE_RESPECT_PID] = $this->lang->getLL('impexpcore_singlereco_respectPid');
            }
        }
        $options[Import::IMPORT_MODE_EXCLUDE] = $this->lang->getLL('impexpcore_singlereco_exclude');
        return $this->renderSelectBox(
            'tx_impexp[import_mode][' . $table . ':' . $uid . ']',
            (string)($this->importMode[$table . ':' . $uid] ?? ''),
            $options
        );
    }

    /**
     * Renders a select box from option values.
     *
     * @param string $name Form element name
     * @param string $value Current value
     * @param array $options Options to display (key/value pairs)
     * @return string HTML
     */
    protected function renderSelectBox(string $name, string $value, array $options): string
    {
        $optionsHtml = '';
        $isValueInOptions = false;

        foreach ($options as $k => $v) {
            if ((string)$k === $value) {
                $isValueInOptions = true;
                $selectedHtml = ' selected="selected"';
            } else {
                $selectedHtml = '';
            }
            $optionsHtml .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars((string)$k),
                $selectedHtml,
                htmlspecialchars((string)$v)
            );
        }

        // Append and select the current value as an option of the form "[value]"
        // if it is not available in the options.
        if (!$isValueInOptions && $value !== '') {
            $optionsHtml .= sprintf(
                '<option value="%s" selected="selected">%s</option>',
                htmlspecialchars($value),
                htmlspecialchars('[\'' . $value . '\']')
            );
        }

        return '<select name="' . $name . '">' . $optionsHtml . '</select>';
    }

    /*****************************
     * Helper functions of kinds
     *****************************/

    /**
     * @return string
     */
    public function getFileadminFolderName(): string
    {
        if (empty($this->fileadminFolderName)) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])) {
                $this->fileadminFolderName = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/');
            } else {
                $this->fileadminFolderName = 'fileadmin';
            }
        }
        return $this->fileadminFolderName;
    }

    /**
     * @return string
     */
    public function getOrCreateTemporaryFolderName(): string
    {
        if (empty($this->temporaryFolderName)) {
            $this->temporaryFolderName = $this->createTemporaryFolderName();
        }
        return $this->temporaryFolderName;
    }

    protected function createTemporaryFolderName(): string
    {
        $temporaryPath = Environment::getVarPath() . '/transient';
        do {
            $temporaryFolderName = sprintf(
                '%s/impexp_%s_files_%d',
                $temporaryPath,
                $this->mode,
                random_int(1, PHP_INT_MAX)
            );
        } while (is_dir($temporaryFolderName));
        GeneralUtility::mkdir_deep($temporaryFolderName);
        return $temporaryFolderName;
    }

    public function removeTemporaryFolderName(): void
    {
        if (!empty($this->temporaryFolderName)) {
            GeneralUtility::rmdir($this->temporaryFolderName, true);
            $this->temporaryFolderName = null;
        }
    }

    /**
     * Returns a \TYPO3\CMS\Core\Resource\Folder object for saving export files
     * to the server and is also used for uploading import files.
     *
     * @return Folder|null
     */
    public function getOrCreateDefaultImportExportFolder(): ?Folder
    {
        if (empty($this->defaultImportExportFolder)) {
            $this->createDefaultImportExportFolder();
        }
        return $this->defaultImportExportFolder;
    }

    /**
     * Creates a \TYPO3\CMS\Core\Resource\Folder object for saving export files
     * to the server and is also used for uploading import files.
     */
    protected function createDefaultImportExportFolder(): void
    {
        $defaultTemporaryFolder = $this->getBackendUser()->getDefaultUploadTemporaryFolder();
        $defaultImportExportFolder = null;
        $importExportFolderName = 'importexport';

        if ($defaultTemporaryFolder !== null) {
            if ($defaultTemporaryFolder->hasFolder($importExportFolderName) === false) {
                $defaultImportExportFolder = $defaultTemporaryFolder->createFolder($importExportFolderName);
            } else {
                $defaultImportExportFolder = $defaultTemporaryFolder->getSubfolder($importExportFolderName);
            }
        }
        $this->defaultImportExportFolder = $defaultImportExportFolder;
    }

    public function removeDefaultImportExportFolder(): void
    {
        if (!empty($this->defaultImportExportFolder)) {
            $this->defaultImportExportFolder->delete(true);
            $this->defaultImportExportFolder = null;
        }
    }

    /**
     * Checks if the input path relative to the public web path can be found in the file mounts of the backend user.
     * If not, it checks all file mounts of the user for the relative path and returns it if found.
     *
     * @param string $dirPrefix Path relative to public web path.
     * @param bool $checkAlternatives If set to false, do not look for an alternative path.
     * @return string If a path is available, it will be returned, otherwise NULL.
     * @throws \Exception
     */
    protected function resolveStoragePath(string $dirPrefix, bool $checkAlternatives = true): ?string
    {
        try {
            GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier($dirPrefix);
            return $dirPrefix;
        } catch (InsufficientFolderAccessPermissionsException $e) {
            if ($checkAlternatives) {
                $storagesByUser = $this->getBackendUser()->getFileStorages();
                foreach ($storagesByUser as $storage) {
                    try {
                        $folder = $storage->getFolder(rtrim($dirPrefix, '/'));
                        return $folder->getPublicUrl();
                    } catch (InsufficientFolderAccessPermissionsException $e) {
                    }
                }
            }
        }
        return null;
    }

    /**
     * Recursively flattening the $pageTree array to a one-dimensional array with uid-pid pairs.
     *
     * @param array $pageTree Page tree array
     * @param array $list List with uid-pid pairs
     * @param int $pid PID value (internal, don't set from outside)
     */
    protected function flatInversePageTree(array $pageTree, array &$list, int $pid = -1): void
    {
        // @todo: return $list instead of by-reference?!
        $pageTreeInverse = array_reverse($pageTree);
        foreach ($pageTreeInverse as $page) {
            $list[$page['uid']] = $pid;
            if (is_array($page['subrow'] ?? null)) {
                $this->flatInversePageTree($page['subrow'], $list, (int)$page['uid']);
            }
        }
    }

    /**
     * Returns TRUE if the input table name is to be regarded as a static relation (that is, not exported etc).
     *
     * @param string $table Table name
     * @return bool TRUE, if table is marked static
     */
    protected function isTableStatic(string $table): bool
    {
        if (is_array($GLOBALS['TCA'][$table] ?? null)) {
            return ($GLOBALS['TCA'][$table]['ctrl']['is_static'] ?? false)
                || in_array($table, $this->relStaticTables, true)
                || in_array('_ALL', $this->relStaticTables, true);
        }
        return false;
    }

    /**
     * Returns TRUE if the element should be excluded from import and export.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     * @return bool TRUE, if the record should be excluded
     */
    protected function isRecordExcluded(string $table, int $uid): bool
    {
        return (bool)($this->excludeMap[$table . ':' . $uid] ?? false);
    }

    /**
     * Returns TRUE if the soft reference should be included in export.
     *
     * @param string $tokenID Token ID for soft reference
     * @return bool TRUE, if soft reference should be included
     */
    protected function isSoftRefIncluded(string $tokenID): bool
    {
        $mode = $this->softrefCfg[$tokenID]['mode'] ?? '';
        return $tokenID && $mode !== Import::SOFTREF_IMPORT_MODE_EXCLUDE && $mode !== Import::SOFTREF_IMPORT_MODE_EDITABLE;
    }

    /**
     * Returns given fields of record if it exists.
     *
     * @param string $table Table name
     * @param int $uid UID of record
     * @param string $fields Field list to select. Default is "uid,pid"
     * @return array|null Result of \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord() which means the record if found, otherwise NULL
     */
    protected function getRecordFromDatabase(string $table, int $uid, string $fields = 'uid,pid'): ?array
    {
        return BackendUtility::getRecord($table, $uid, $fields);
    }

    /**
     * Returns the page title path of a PID value. Results are cached internally
     *
     * @param int $pid Record PID to check
     * @return string The path for the input PID
     */
    protected function getRecordPath(int $pid): string
    {
        if (!isset($this->cacheGetRecordPath[$pid])) {
            $this->cacheGetRecordPath[$pid] = (string)BackendUtility::getRecordPath($pid, $this->permsClause, 20);
        }
        return $this->cacheGetRecordPath[$pid];
    }

    /**
     * Compares two records, the current database record and the one from the import memory.
     * Will return HTML code to show any differences between them!
     *
     * @param array $databaseRecord Database record, all fields (old values)
     * @param array $importRecord Import memory record for the same table/uid, all fields (new values)
     * @param string $table The table name of the record
     * @param bool $inverse Inverse the diff view (switch red/green, needed for pre-update difference view)
     * @return string HTML
     */
    protected function compareRecords(array $databaseRecord, array $importRecord, string $table, bool $inverse = false): string
    {
        $diffHtml = '';

        // Updated fields
        foreach ($databaseRecord as $fieldName => $_) {
            if (is_array($GLOBALS['TCA'][$table]['columns'][$fieldName] ?? null)
                && $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['type'] !== 'passthrough'
            ) {
                if (isset($importRecord[$fieldName])) {
                    if (trim((string)$databaseRecord[$fieldName]) !== trim((string)$importRecord[$fieldName])) {
                        $diffFieldHtml = $this->getDiffUtility()->makeDiffDisplay(
                            BackendUtility::getProcessedValue(
                                $table,
                                $fieldName,
                                !$inverse ? $importRecord[$fieldName] : $databaseRecord[$fieldName],
                                0,
                                true,
                                true
                            ),
                            BackendUtility::getProcessedValue(
                                $table,
                                $fieldName,
                                !$inverse ? $databaseRecord[$fieldName] : $importRecord[$fieldName],
                                0,
                                true,
                                true
                            )
                        );
                        $diffHtml .= sprintf(
                            '<tr><td>%s (%s)</td><td>%s</td></tr>' . PHP_EOL,
                            htmlspecialchars($this->lang->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'])),
                            htmlspecialchars((string)$fieldName),
                            $diffFieldHtml
                        );
                    }
                    unset($importRecord[$fieldName]);
                }
            }
        }

        // New fields
        foreach ($importRecord as $fieldName => $_) {
            if (is_array($GLOBALS['TCA'][$table]['columns'][$fieldName] ?? null)
                && $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['type'] !== 'passthrough'
            ) {
                $diffFieldHtml = '<strong>Field missing</strong> in database';
                $diffHtml .= sprintf(
                    '<tr><td>%s (%s)</td><td>%s</td></tr>' . PHP_EOL,
                    htmlspecialchars($this->lang->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'])),
                    htmlspecialchars((string)$fieldName),
                    $diffFieldHtml
                );
            }
        }

        if ($diffHtml !== '') {
            $diffHtml = '<table class="table table-striped table-hover">' . PHP_EOL . $diffHtml . '</table>';
        } else {
            $diffHtml = 'Match';
        }

        return sprintf(
            '<strong class="text-nowrap">[%s]:</strong>' . PHP_EOL . '%s',
            htmlspecialchars($table . ':' . $importRecord['uid'] . ' => ' . $databaseRecord['uid']),
            $diffHtml
        );
    }

    /**
     * Returns string comparing object, initialized only once.
     *
     * @return DiffUtility String comparing object
     */
    protected function getDiffUtility(): DiffUtility
    {
        if ($this->diffUtility === null) {
            $this->diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
        }
        return $this->diffUtility;
    }

    /**
     * Returns file processing object, initialized only once.
     *
     * @return ExtendedFileUtility File processor object
     */
    protected function getFileProcObj(): ExtendedFileUtility
    {
        if ($this->fileProcObj === null) {
            $this->fileProcObj = GeneralUtility::makeInstance(ExtendedFileUtility::class);
            $this->fileProcObj->setActionPermissions();
        }
        return $this->fileProcObj;
    }

    /**
     * Returns storage repository object, initialized only once.
     *
     * @return StorageRepository Storage repository object
     */
    protected function getStorageRepository(): StorageRepository
    {
        if ($this->storageRepository === null) {
            $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        }
        return $this->storageRepository;
    }

    /*****************************
     * Error handling
     *****************************/

    /**
     * Sets error message in the internal error log
     *
     * @param string $message Error message
     */
    protected function addError(string $message): void
    {
        $this->errorLog[] = $message;
    }

    public function hasErrors(): bool
    {
        return empty($this->errorLog) === false;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**************************
     * Getters and Setters
     *************************/

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     */
    public function setPid(int $pid): void
    {
        $this->pid = $pid;
        $this->pidRecord = null;
    }

    /**
     * Return record of root page of import or of export page tree
     * - or null if access denied to that page.
     *
     * If the page is the root of the page tree,
     * add some basic but missing information.
     *
     * @return array|null
     */
    protected function getPidRecord(): ?array
    {
        if ($this->pidRecord === null && $this->pid >= 0) {
            $pidRecord = BackendUtility::readPageAccess($this->pid, $this->permsClause);

            if (is_array($pidRecord)) {
                if ($this->pid === 0) {
                    $pidRecord += ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
                }
                $this->pidRecord = $pidRecord;
            }
        }

        return $this->pidRecord;
    }

    /**
     * Set flag to control whether disabled records and their children are excluded (true) or included (false). Defaults
     * to the old behaviour of including everything.
     *
     * @param bool $excludeDisabledRecords Set to true if if all disabled records should be excluded, false otherwise
     */
    public function setExcludeDisabledRecords(bool $excludeDisabledRecords): void
    {
        $this->excludeDisabledRecords = $excludeDisabledRecords;
    }

    /**
     * @return bool
     */
    public function isExcludeDisabledRecords(): bool
    {
        return $this->excludeDisabledRecords;
    }

    /**
     * @return array
     */
    public function getExcludeMap(): array
    {
        return $this->excludeMap;
    }

    /**
     * @param array $excludeMap
     */
    public function setExcludeMap(array $excludeMap): void
    {
        $this->excludeMap = $excludeMap;
    }

    /**
     * @return array
     */
    public function getSoftrefCfg(): array
    {
        return $this->softrefCfg;
    }

    /**
     * @param array $softrefCfg
     */
    public function setSoftrefCfg(array $softrefCfg): void
    {
        $this->softrefCfg = $softrefCfg;
    }

    /**
     * @return array
     */
    public function getExtensionDependencies(): array
    {
        return $this->extensionDependencies;
    }

    /**
     * @param array $extensionDependencies
     */
    public function setExtensionDependencies(array $extensionDependencies): void
    {
        $this->extensionDependencies = $extensionDependencies;
    }

    /**
     * @return bool
     */
    public function isShowStaticRelations(): bool
    {
        return $this->showStaticRelations;
    }

    /**
     * @param bool $showStaticRelations
     */
    public function setShowStaticRelations(bool $showStaticRelations): void
    {
        $this->showStaticRelations = $showStaticRelations;
    }

    /**
     * @return array
     */
    public function getRelStaticTables(): array
    {
        return $this->relStaticTables;
    }

    /**
     * @param array $relStaticTables
     */
    public function setRelStaticTables(array $relStaticTables): void
    {
        $this->relStaticTables = $relStaticTables;
    }

    /**
     * @return array
     */
    public function getErrorLog(): array
    {
        return $this->errorLog;
    }

    /**
     * @param array $errorLog
     */
    public function setErrorLog(array $errorLog): void
    {
        $this->errorLog = $errorLog;
    }

    /**
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->update;
    }

    /**
     * @param bool $update
     */
    public function setUpdate(bool $update): void
    {
        $this->update = $update;
    }

    /**
     * @return array
     */
    public function getImportMode(): array
    {
        return $this->importMode;
    }

    /**
     * @param array $importMode
     */
    public function setImportMode(array $importMode): void
    {
        $this->importMode = $importMode;
    }

    /**
     * @return bool
     */
    public function isGlobalIgnorePid(): bool
    {
        return $this->globalIgnorePid;
    }

    /**
     * @param bool $globalIgnorePid
     */
    public function setGlobalIgnorePid(bool $globalIgnorePid): void
    {
        $this->globalIgnorePid = $globalIgnorePid;
    }

    /**
     * @return bool
     */
    public function isForceAllUids(): bool
    {
        return $this->forceAllUids;
    }

    /**
     * @param bool $forceAllUids
     */
    public function setForceAllUids(bool $forceAllUids): void
    {
        $this->forceAllUids = $forceAllUids;
    }

    /**
     * @return bool
     */
    public function isShowDiff(): bool
    {
        return $this->showDiff;
    }

    /**
     * @param bool $showDiff
     */
    public function setShowDiff(bool $showDiff): void
    {
        $this->showDiff = $showDiff;
    }

    /**
     * @return array
     */
    public function getSoftrefInputValues(): array
    {
        return $this->softrefInputValues;
    }

    /**
     * @param array $softrefInputValues
     */
    public function setSoftrefInputValues(array $softrefInputValues): void
    {
        $this->softrefInputValues = $softrefInputValues;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return array
     */
    public function getImportMapId(): array
    {
        return $this->importMapId;
    }

    /**
     * @param array $importMapId
     */
    public function setImportMapId(array $importMapId): void
    {
        $this->importMapId = $importMapId;
    }

    /**
     * @return array
     */
    public function getDat(): array
    {
        return $this->dat;
    }
}
