<?php
namespace TYPO3\CMS\Impexp;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\DiffUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * EXAMPLE for using the impexp-class for exporting stuff:
 *
 * Create and initialize:
 * $this->export = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Impexp\ImportExport::class);
 * $this->export->init();
 * Set which tables relations we will allow:
 * $this->export->relOnlyTables[]="tt_news";	// exclusively includes. See comment in the class
 *
 * Adding records:
 * $this->export->export_addRecord("pages", $this->pageinfo);
 * $this->export->export_addRecord("pages", \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord("pages", 38));
 * $this->export->export_addRecord("pages", \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord("pages", 39));
 * $this->export->export_addRecord("tt_content", \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord("tt_content", 12));
 * $this->export->export_addRecord("tt_content", \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord("tt_content", 74));
 * $this->export->export_addRecord("sys_template", \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord("sys_template", 20));
 *
 * Adding all the relations (recursively in 5 levels so relations has THEIR relations registered as well)
 * for($a=0;$a<5;$a++) {
 * $addR = $this->export->export_addDBRelations($a);
 * if (empty($addR)) break;
 * }
 *
 * Finally load all the files.
 * $this->export->export_addFilesFromRelations();	// MUST be after the DBrelations are set so that file from ALL added records are included!
 *
 * Write export
 * $out = $this->export->compileMemoryToFileContent();
 */

/**
 * T3D file Import/Export library (TYPO3 Record Document)
 */
abstract class ImportExport
{
    /**
     * If set, static relations (not exported) will be shown in overview as well
     *
     * @var bool
     */
    public $showStaticRelations = false;

    /**
     * Name of the "fileadmin" folder where files for export/import should be located
     *
     * @var string
     */
    public $fileadminFolderName = '';

    /**
     * Whether "import" or "export" mode of object. Set through init() function
     *
     * @var string
     */
    public $mode = '';

    /**
     * Updates all records that has same UID instead of creating new!
     *
     * @var bool
     */
    public $update = false;

    /**
     * Is set by importData() when an import has been done.
     *
     * @var bool
     */
    public $doesImport = false;

    /**
     * If set to a page-record, then the preview display of the content will expect this page-record to be the target
     * for the import and accordingly display validation information. This triggers the visual view of the
     * import/export memory to validate if import is possible
     *
     * @var array
     */
    public $display_import_pid_record = [];

    /**
     * Setting import modes during update state: as_new, exclude, force_uid
     *
     * @var array
     */
    public $import_mode = [];

    /**
     * If set, PID correct is ignored globally
     *
     * @var bool
     */
    public $global_ignore_pid = false;

    /**
     * If set, all UID values are forced! (update or import)
     *
     * @var bool
     */
    public $force_all_UIDS = false;

    /**
     * If set, a diff-view column is added to the overview.
     *
     * @var bool
     */
    public $showDiff = false;

    /**
     * If set, and if the user is admin, allow the writing of PHP scripts to fileadmin/ area.
     *
     * @var bool
     */
    public $allowPHPScripts = false;

    /**
     * Array of values to substitute in editable softreferences.
     *
     * @var array
     */
    public $softrefInputValues = [];

    /**
     * Mapping between the fileID from import memory and the final filenames they are written to.
     *
     * @var array
     */
    public $fileIDMap = [];

    /**
     * Migrate legacy import records
     *
     * @var array
     */
    public $relOnlyTables = [];

    /**
     * Add tables names here which should not be exported with the file.
     * (Where relations should be mapped to same UIDs in target system).
     *
     * @var array
     */
    public $relStaticTables = [];

    /**
     * Exclude map. Keys are table:uid  pairs and if set, records are not added to the export.
     *
     * @var array
     */
    public $excludeMap = [];

    /**
     * Soft Reference Token ID modes.
     *
     * @var array
     */
    public $softrefCfg = [];

    /**
     * Listing extension dependencies.
     *
     * @var array
     */
    public $extensionDependencies = [];

    /**
     * After records are written this array is filled with [table][original_uid] = [new_uid]
     *
     * @var array
     */
    public $import_mapId = [];

    /**
     * Error log.
     *
     * @var array
     */
    public $errorLog = [];

    /**
     * Cache for record paths
     *
     * @var array
     */
    public $cache_getRecordPath = [];

    /**
     * Cache of checkPID values.
     *
     * @var array
     */
    public $checkPID_cache = [];

    /**
     * Set internally if the gzcompress function exists
     * Used by ImportExportController
     *
     * @var bool
     */
    public $compress = false;

    /**
     * Internal import/export memory
     *
     * @var array
     */
    public $dat = [];

    /**
     * File processing object
     *
     * @var ExtendedFileUtility
     */
    protected $fileProcObj = null;

    /**
     * @var array
     */
    protected $remainHeader = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**************************
     * Initialize
     *************************/

    /**
     * Init the object, both import and export
     *
     * @return void
     */
    public function init()
    {
        $this->compress = function_exists('gzcompress');
        $this->fileadminFolderName = !empty($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']) ? rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') : 'fileadmin';
    }

    /********************************************************
     * Visual rendering of import/export memory, $this->dat
     ********************************************************/

    /**
     * Displays an overview of the header-content.
     *
     * @return array The view data
     */
    public function displayContentOverview()
    {
        if (!isset($this->dat['header'])) {
            return [];
        }
        // Check extension dependencies:
        foreach ($this->dat['header']['extensionDependencies'] as $extKey) {
            if (!empty($extKey) && !ExtensionManagementUtility::isLoaded($extKey)) {
                $this->error('DEPENDENCY: The extension with key "' . $extKey . '" must be installed!');
            }
        }

        // Probably this is done to save memory space?
        unset($this->dat['files']);

        $viewData = [];
        // Traverse header:
        $this->remainHeader = $this->dat['header'];
        // If there is a page tree set, show that:
        if (is_array($this->dat['header']['pagetree'])) {
            reset($this->dat['header']['pagetree']);
            $lines = [];
            $this->traversePageTree($this->dat['header']['pagetree'], $lines);

            $viewData['dat'] = $this->dat;
            $viewData['update'] = $this->update;
            $viewData['showDiff'] = $this->showDiff;
            if (!empty($lines)) {
                foreach ($lines as &$r) {
                    $r['controls'] = $this->renderControls($r);
                    $r['fileSize'] = GeneralUtility::formatSize($r['size']);
                    $r['message'] = ($r['msg'] && !$this->doesImport ? '<span class="text-danger">' . htmlspecialchars($r['msg']) . '</span>' : '');
                }
                $viewData['pagetreeLines'] = $lines;
            } else {
                $viewData['pagetreeLines'] = [];
            }
        }
        // Print remaining records that were not contained inside the page tree:
        if (is_array($this->remainHeader['records'])) {
            $lines = [];
            if (is_array($this->remainHeader['records']['pages'])) {
                $this->traversePageRecords($this->remainHeader['records']['pages'], $lines);
            }
            $this->traverseAllRecords($this->remainHeader['records'], $lines);
            if (!empty($lines)) {
                foreach ($lines as &$r) {
                    $r['controls'] = $this->renderControls($r);
                    $r['fileSize'] = GeneralUtility::formatSize($r['size']);
                    $r['message'] = ($r['msg'] && !$this->doesImport ? '<span class="text-danger">' . htmlspecialchars($r['msg']) . '</span>' : '');
                }
                $viewData['remainingRecords'] = $lines;
            }
        }

        return $viewData;
    }

    /**
     * Go through page tree for display
     *
     * @param array $pT Page tree array with uid/subrow (from ->dat[header][pagetree]
     * @param array $lines Output lines array (is passed by reference and modified)
     * @param string $preCode Pre-HTML code
     * @return void
     */
    public function traversePageTree($pT, &$lines, $preCode = '')
    {
        foreach ($pT as $k => $v) {
            // Add this page:
            $this->singleRecordLines('pages', $k, $lines, $preCode);
            // Subrecords:
            if (is_array($this->dat['header']['pid_lookup'][$k])) {
                foreach ($this->dat['header']['pid_lookup'][$k] as $t => $recUidArr) {
                    if ($t != 'pages') {
                        foreach ($recUidArr as $ruid => $value) {
                            $this->singleRecordLines($t, $ruid, $lines, $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;');
                        }
                    }
                }
                unset($this->remainHeader['pid_lookup'][$k]);
            }
            // Subpages, called recursively:
            if (is_array($v['subrow'])) {
                $this->traversePageTree($v['subrow'], $lines, $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;');
            }
        }
    }

    /**
     * Go through remaining pages (not in tree)
     *
     * @param array $pT Page tree array with uid/subrow (from ->dat[header][pagetree]
     * @param array $lines Output lines array (is passed by reference and modified)
     * @return void
     */
    public function traversePageRecords($pT, &$lines)
    {
        foreach ($pT as $k => $rHeader) {
            $this->singleRecordLines('pages', $k, $lines, '', 1);
            // Subrecords:
            if (is_array($this->dat['header']['pid_lookup'][$k])) {
                foreach ($this->dat['header']['pid_lookup'][$k] as $t => $recUidArr) {
                    if ($t != 'pages') {
                        foreach ($recUidArr as $ruid => $value) {
                            $this->singleRecordLines($t, $ruid, $lines, '&nbsp;&nbsp;&nbsp;&nbsp;');
                        }
                    }
                }
                unset($this->remainHeader['pid_lookup'][$k]);
            }
        }
    }

    /**
     * Go through ALL records (if the pages are displayed first, those will not be amoung these!)
     *
     * @param array $pT Page tree array with uid/subrow (from ->dat[header][pagetree]
     * @param array $lines Output lines array (is passed by reference and modified)
     * @return void
     */
    public function traverseAllRecords($pT, &$lines)
    {
        foreach ($pT as $t => $recUidArr) {
            $this->addGeneralErrorsByTable($t);
            if ($t != 'pages') {
                $preCode = '';
                foreach ($recUidArr as $ruid => $value) {
                    $this->singleRecordLines($t, $ruid, $lines, $preCode, 1);
                }
            }
        }
    }

    /**
     * Log general error message for a given table
     *
     * @param string $table database table name
     * @return void
     */
    protected function addGeneralErrorsByTable($table)
    {
        if ($this->update && $table === 'sys_file') {
            $this->error('Updating sys_file records is not supported! They will be imported as new records!');
        }
        if ($this->force_all_UIDS && $table === 'sys_file') {
            $this->error('Forcing uids of sys_file records is not supported! They will be imported as new records!');
        }
    }

    /**
     * Add entries for a single record
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param array $lines Output lines array (is passed by reference and modified)
     * @param string $preCode Pre-HTML code
     * @param bool $checkImportInPidRecord If you want import validation, you can set this so it checks if the import can take place on the specified page.
     * @return void
     */
    public function singleRecordLines($table, $uid, &$lines, $preCode, $checkImportInPidRecord = false)
    {
        // Get record:
        $record = $this->dat['header']['records'][$table][$uid];
        unset($this->remainHeader['records'][$table][$uid]);
        if (!is_array($record) && !($table === 'pages' && !$uid)) {
            $this->error('MISSING RECORD: ' . $table . ':' . $uid);
        }
        // Begin to create the line arrays information record, pInfo:
        $pInfo = [];
        $pInfo['ref'] = $table . ':' . $uid;
        // Unknown table name:
        $lang = $this->getLanguageService();
        if ($table === '_SOFTREF_') {
            $pInfo['preCode'] = $preCode;
            $pInfo['title'] = '<em>' . $lang->getLL('impexpcore_singlereco_softReferencesFiles', true) . '</em>';
        } elseif (!isset($GLOBALS['TCA'][$table])) {
            // Unknown table name:
            $pInfo['preCode'] = $preCode;
            $pInfo['msg'] = 'UNKNOWN TABLE \'' . $pInfo['ref'] . '\'';
            $pInfo['title'] = '<em>' . htmlspecialchars($record['title']) . '</em>';
        } else {
            // Otherwise, set table icon and title.
            // Import Validation (triggered by $this->display_import_pid_record) will show messages if import is not possible of various items.
            if (is_array($this->display_import_pid_record) && !empty($this->display_import_pid_record)) {
                if ($checkImportInPidRecord) {
                    if (!$this->getBackendUser()->doesUserHaveAccess($this->display_import_pid_record, ($table === 'pages' ? 8 : 16))) {
                        $pInfo['msg'] .= '\'' . $pInfo['ref'] . '\' cannot be INSERTED on this page! ';
                    }
                    if (!$this->checkDokType($table, $this->display_import_pid_record['doktype']) && !$GLOBALS['TCA'][$table]['ctrl']['rootLevel']) {
                        $pInfo['msg'] .= '\'' . $table . '\' cannot be INSERTED on this page type (change page type to \'Folder\'.) ';
                    }
                }
                if (!$this->getBackendUser()->check('tables_modify', $table)) {
                    $pInfo['msg'] .= 'You are not allowed to CREATE \'' . $table . '\' tables! ';
                }
                if ($GLOBALS['TCA'][$table]['ctrl']['readOnly']) {
                    $pInfo['msg'] .= 'TABLE \'' . $table . '\' is READ ONLY! ';
                }
                if ($GLOBALS['TCA'][$table]['ctrl']['adminOnly'] && !$this->getBackendUser()->isAdmin()) {
                    $pInfo['msg'] .= 'TABLE \'' . $table . '\' is ADMIN ONLY! ';
                }
                if ($GLOBALS['TCA'][$table]['ctrl']['is_static']) {
                    $pInfo['msg'] .= 'TABLE \'' . $table . '\' is a STATIC TABLE! ';
                }
                if ((int)$GLOBALS['TCA'][$table]['ctrl']['rootLevel'] === 1) {
                    $pInfo['msg'] .= 'TABLE \'' . $table . '\' will be inserted on ROOT LEVEL! ';
                }
                $diffInverse = false;
                $recInf = null;
                if ($this->update) {
                    // In case of update-PREVIEW we swap the diff-sources.
                    $diffInverse = true;
                    $recInf = $this->doesRecordExist($table, $uid, $this->showDiff ? '*' : '');
                    $pInfo['updatePath'] = $recInf ? htmlspecialchars($this->getRecordPath($recInf['pid'])) : '<strong>NEW!</strong>';
                    // Mode selector:
                    $optValues = [];
                    $optValues[] = $recInf ? $lang->getLL('impexpcore_singlereco_update') : $lang->getLL('impexpcore_singlereco_insert');
                    if ($recInf) {
                        $optValues['as_new'] = $lang->getLL('impexpcore_singlereco_importAsNew');
                    }
                    if ($recInf) {
                        if (!$this->global_ignore_pid) {
                            $optValues['ignore_pid'] = $lang->getLL('impexpcore_singlereco_ignorePid');
                        } else {
                            $optValues['respect_pid'] = $lang->getLL('impexpcore_singlereco_respectPid');
                        }
                    }
                    if (!$recInf && $this->getBackendUser()->isAdmin()) {
                        $optValues['force_uid'] = sprintf($lang->getLL('impexpcore_singlereco_forceUidSAdmin'), $uid);
                    }
                    $optValues['exclude'] = $lang->getLL('impexpcore_singlereco_exclude');
                    if ($table === 'sys_file') {
                        $pInfo['updateMode'] = '';
                    } else {
                        $pInfo['updateMode'] = $this->renderSelectBox('tx_impexp[import_mode][' . $table . ':' . $uid . ']', $this->import_mode[$table . ':' . $uid], $optValues);
                    }
                }
                // Diff view:
                if ($this->showDiff) {
                    // For IMPORTS, get new id:
                    if ($newUid = $this->import_mapId[$table][$uid]) {
                        $diffInverse = false;
                        $recInf = $this->doesRecordExist($table, $newUid, '*');
                        BackendUtility::workspaceOL($table, $recInf);
                    }
                    if (is_array($recInf)) {
                        $pInfo['showDiffContent'] = $this->compareRecords($recInf, $this->dat['records'][$table . ':' . $uid]['data'], $table, $diffInverse);
                    }
                }
            }
            $pInfo['preCode'] = $preCode . '<span title="' . htmlspecialchars($table . ':' . $uid) . '">'
                . $this->iconFactory->getIconForRecord($table, (array)$this->dat['records'][$table . ':' . $uid]['data'], Icon::SIZE_SMALL)->render()
                . '</span>';
            $pInfo['title'] = htmlspecialchars($record['title']);
            // View page:
            if ($table === 'pages') {
                $viewID = $this->mode === 'export' ? $uid : ($this->doesImport ? $this->import_mapId['pages'][$uid] : 0);
                if ($viewID) {
                    $pInfo['title'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($viewID)) . 'return false;">' . $pInfo['title'] . '</a>';
                }
            }
        }
        $pInfo['class'] = $table == 'pages' ? 'bgColor4-20' : 'bgColor4';
        $pInfo['type'] = 'record';
        $pInfo['size'] = $record['size'];
        $lines[] = $pInfo;
        // File relations:
        if (is_array($record['filerefs'])) {
            $this->addFiles($record['filerefs'], $lines, $preCode);
        }
        // DB relations
        if (is_array($record['rels'])) {
            $this->addRelations($record['rels'], $lines, $preCode);
        }
        // Soft ref
        if (!empty($record['softrefs'])) {
            $preCode_A = $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;';
            $preCode_B = $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            foreach ($record['softrefs'] as $info) {
                $pInfo = [];
                $pInfo['preCode'] = $preCode_A . $this->iconFactory->getIcon('status-status-reference-soft', Icon::SIZE_SMALL)->render();
                $pInfo['title'] = '<em>' . $info['field'] . ', "' . $info['spKey'] . '" </em>: <span title="' . htmlspecialchars($info['matchString']) . '">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($info['matchString'], 60)) . '</span>';
                if ($info['subst']['type']) {
                    if (strlen($info['subst']['title'])) {
                        $pInfo['title'] .= '<br/>' . $preCode_B . '<strong>' . $lang->getLL('impexpcore_singlereco_title', true) . '</strong> ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($info['subst']['title'], 60));
                    }
                    if (strlen($info['subst']['description'])) {
                        $pInfo['title'] .= '<br/>' . $preCode_B . '<strong>' . $lang->getLL('impexpcore_singlereco_descr', true) . '</strong> ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($info['subst']['description'], 60));
                    }
                    $pInfo['title'] .= '<br/>' . $preCode_B . ($info['subst']['type'] == 'file' ? $lang->getLL('impexpcore_singlereco_filename', true) . ' <strong>' . $info['subst']['relFileName'] . '</strong>' : '') . ($info['subst']['type'] == 'string' ? $lang->getLL('impexpcore_singlereco_value', true) . ' <strong>' . $info['subst']['tokenValue'] . '</strong>' : '') . ($info['subst']['type'] == 'db' ? $lang->getLL('impexpcore_softrefsel_record', true) . ' <strong>' . $info['subst']['recordRef'] . '</strong>' : '');
                }
                $pInfo['ref'] = 'SOFTREF';
                $pInfo['size'] = '';
                $pInfo['class'] = 'bgColor3';
                $pInfo['type'] = 'softref';
                $pInfo['_softRefInfo'] = $info;
                $pInfo['type'] = 'softref';
                $mode = $this->softrefCfg[$info['subst']['tokenID']]['mode'];
                if ($info['error'] && $mode !== 'editable' && $mode !== 'exclude') {
                    $pInfo['msg'] .= $info['error'];
                }
                $lines[] = $pInfo;
                // Add relations:
                if ($info['subst']['type'] == 'db') {
                    list($tempTable, $tempUid) = explode(':', $info['subst']['recordRef']);
                    $this->addRelations([['table' => $tempTable, 'id' => $tempUid, 'tokenID' => $info['subst']['tokenID']]], $lines, $preCode_B, [], '');
                }
                // Add files:
                if ($info['subst']['type'] == 'file') {
                    $this->addFiles([$info['file_ID']], $lines, $preCode_B, '', $info['subst']['tokenID']);
                }
            }
        }
    }

    /**
     * Add DB relations entries for a record's rels-array
     *
     * @param array $rels Array of relations
     * @param array $lines Output lines array (is passed by reference and modified)
     * @param string $preCode Pre-HTML code
     * @param array $recurCheck Recursivity check stack
     * @param string $htmlColorClass Alternative HTML color class to use.
     * @return void
     * @access private
     * @see singleRecordLines()
     */
    public function addRelations($rels, &$lines, $preCode, $recurCheck = [], $htmlColorClass = '')
    {
        foreach ($rels as $dat) {
            $table = $dat['table'];
            $uid = $dat['id'];
            $pInfo = [];
            $pInfo['ref'] = $table . ':' . $uid;
            if (in_array($pInfo['ref'], $recurCheck)) {
                continue;
            }
            $iconName = 'status-status-checked';
            $iconClass = '';
            $staticFixed = false;
            $record = null;
            if ($uid > 0) {
                $record = $this->dat['header']['records'][$table][$uid];
                if (!is_array($record)) {
                    if ($this->isTableStatic($table) || $this->isExcluded($table, $uid) || $dat['tokenID'] && !$this->includeSoftref($dat['tokenID'])) {
                        $pInfo['title'] = htmlspecialchars('STATIC: ' . $pInfo['ref']);
                        $iconClass = 'text-info';
                        $staticFixed = true;
                    } else {
                        $doesRE = $this->doesRecordExist($table, $uid);
                        $lostPath = $this->getRecordPath($table === 'pages' ? $doesRE['uid'] : $doesRE['pid']);
                        $pInfo['title'] = htmlspecialchars($pInfo['ref']);
                        $pInfo['title'] = '<span title="' . htmlspecialchars($lostPath) . '">' . $pInfo['title'] . '</span>';
                        $pInfo['msg'] = 'LOST RELATION' . (!$doesRE ? ' (Record not found!)' : ' (Path: ' . $lostPath . ')');
                        $iconClass = 'text-danger';
                        $iconName = 'status-dialog-warning';
                    }
                } else {
                    $pInfo['title'] = htmlspecialchars($record['title']);
                    $pInfo['title'] = '<span title="' . htmlspecialchars($this->getRecordPath(($table === 'pages' ? $record['uid'] : $record['pid']))) . '">' . $pInfo['title'] . '</span>';
                }
            } else {
                // Negative values in relation fields. This is typically sys_language fields, fe_users fields etc. They are static values. They CAN theoretically be negative pointers to uids in other tables but this is so rarely used that it is not supported
                $pInfo['title'] = htmlspecialchars('FIXED: ' . $pInfo['ref']);
                $staticFixed = true;
            }

            $icon = '<span class="' . $iconClass . '" title="' . htmlspecialchars($pInfo['ref']) . '">' . $this->iconFactory->getIcon($iconName, Icon::SIZE_SMALL)->render() . '</span>';

            $pInfo['preCode'] = $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;' . $icon;
            $pInfo['class'] = $htmlColorClass ?: 'bgColor3';
            $pInfo['type'] = 'rel';
            if (!$staticFixed || $this->showStaticRelations) {
                $lines[] = $pInfo;
                if (is_array($record) && is_array($record['rels'])) {
                    $this->addRelations($record['rels'], $lines, $preCode . '&nbsp;&nbsp;', array_merge($recurCheck, [$pInfo['ref']]), $htmlColorClass);
                }
            }
        }
    }

    /**
     * Add file relation entries for a record's rels-array
     *
     * @param array $rels Array of file IDs
     * @param array $lines Output lines array (is passed by reference and modified)
     * @param string $preCode Pre-HTML code
     * @param string $htmlColorClass Alternative HTML color class to use.
     * @param string $tokenID Token ID if this is a softreference (in which case it only makes sense with a single element in the $rels array!)
     * @return void
     * @access private
     * @see singleRecordLines()
     */
    public function addFiles($rels, &$lines, $preCode, $htmlColorClass = '', $tokenID = '')
    {
        foreach ($rels as $ID) {
            // Process file:
            $pInfo = [];
            $fI = $this->dat['header']['files'][$ID];
            if (!is_array($fI)) {
                if (!$tokenID || $this->includeSoftref($tokenID)) {
                    $pInfo['msg'] = 'MISSING FILE: ' . $ID;
                    $this->error('MISSING FILE: ' . $ID);
                } else {
                    return;
                }
            }
            $pInfo['preCode'] = $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;' . $this->iconFactory->getIcon('status-status-reference-hard', Icon::SIZE_SMALL)->render();
            $pInfo['title'] = htmlspecialchars($fI['filename']);
            $pInfo['ref'] = 'FILE';
            $pInfo['size'] = $fI['filesize'];
            $pInfo['class'] = $htmlColorClass ?: 'bgColor3';
            $pInfo['type'] = 'file';
            // If import mode and there is a non-RTE softreference, check the destination directory:
            if ($this->mode === 'import' && $tokenID && !$fI['RTE_ORIG_ID']) {
                if (isset($fI['parentRelFileName'])) {
                    $pInfo['msg'] = 'Seems like this file is already referenced from within an HTML/CSS file. That takes precedence. ';
                } else {
                    $testDirPrefix = PathUtility::dirname($fI['relFileName']) . '/';
                    $testDirPrefix2 = $this->verifyFolderAccess($testDirPrefix);
                    if (!$testDirPrefix2) {
                        $pInfo['msg'] = 'ERROR: There are no available filemounts to write file in! ';
                    } elseif ($testDirPrefix !== $testDirPrefix2) {
                        $pInfo['msg'] = 'File will be attempted written to "' . $testDirPrefix2 . '". ';
                    }
                }
                // Check if file exists:
                if (file_exists(PATH_site . $fI['relFileName'])) {
                    if ($this->update) {
                        $pInfo['updatePath'] .= 'File exists.';
                    } else {
                        $pInfo['msg'] .= 'File already exists! ';
                    }
                }
                // Check extension:
                $fileProcObj = $this->getFileProcObj();
                if ($fileProcObj->actionPerms['addFile']) {
                    $testFI = GeneralUtility::split_fileref(PATH_site . $fI['relFileName']);
                    if (!$this->allowPHPScripts && !$fileProcObj->checkIfAllowed($testFI['fileext'], $testFI['path'], $testFI['file'])) {
                        $pInfo['msg'] .= 'File extension was not allowed!';
                    }
                } else {
                    $pInfo['msg'] = 'You user profile does not allow you to create files on the server!';
                }
            }
            $pInfo['showDiffContent'] = PathUtility::stripPathSitePrefix($this->fileIDMap[$ID]);
            $lines[] = $pInfo;
            unset($this->remainHeader['files'][$ID]);
            // RTE originals:
            if ($fI['RTE_ORIG_ID']) {
                $ID = $fI['RTE_ORIG_ID'];
                $pInfo = [];
                $fI = $this->dat['header']['files'][$ID];
                if (!is_array($fI)) {
                    $pInfo['msg'] = 'MISSING RTE original FILE: ' . $ID;
                    $this->error('MISSING RTE original FILE: ' . $ID);
                }
                $pInfo['showDiffContent'] = PathUtility::stripPathSitePrefix($this->fileIDMap[$ID]);
                $pInfo['preCode'] = $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $this->iconFactory->getIcon('status-status-reference-hard', Icon::SIZE_SMALL)->render();
                $pInfo['title'] = htmlspecialchars($fI['filename']) . ' <em>(Original)</em>';
                $pInfo['ref'] = 'FILE';
                $pInfo['size'] = $fI['filesize'];
                $pInfo['class'] = $htmlColorClass ?: 'bgColor3';
                $pInfo['type'] = 'file';
                $lines[] = $pInfo;
                unset($this->remainHeader['files'][$ID]);
            }
            // External resources:
            if (is_array($fI['EXT_RES_ID'])) {
                foreach ($fI['EXT_RES_ID'] as $extID) {
                    $pInfo = [];
                    $fI = $this->dat['header']['files'][$extID];
                    if (!is_array($fI)) {
                        $pInfo['msg'] = 'MISSING External Resource FILE: ' . $extID;
                        $this->error('MISSING External Resource FILE: ' . $extID);
                    } else {
                        $pInfo['updatePath'] = $fI['parentRelFileName'];
                    }
                    $pInfo['showDiffContent'] = PathUtility::stripPathSitePrefix($this->fileIDMap[$extID]);
                    $pInfo['preCode'] = $preCode . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $this->iconFactory->getIcon('actions-insert-reference', Icon::SIZE_SMALL)->render();
                    $pInfo['title'] = htmlspecialchars($fI['filename']) . ' <em>(Resource)</em>';
                    $pInfo['ref'] = 'FILE';
                    $pInfo['size'] = $fI['filesize'];
                    $pInfo['class'] = $htmlColorClass ?: 'bgColor3';
                    $pInfo['type'] = 'file';
                    $lines[] = $pInfo;
                    unset($this->remainHeader['files'][$extID]);
                }
            }
        }
    }

    /**
     * Verifies that a table is allowed on a certain doktype of a page
     *
     * @param string $checkTable Table name to check
     * @param int $doktype doktype value.
     * @return bool TRUE if OK
     */
    public function checkDokType($checkTable, $doktype)
    {
        $allowedTableList = isset($GLOBALS['PAGES_TYPES'][$doktype]['allowedTables']) ? $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'] : $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
        $allowedArray = GeneralUtility::trimExplode(',', $allowedTableList, true);
        // If all tables or the table is listed as an allowed type, return TRUE
        if (strstr($allowedTableList, '*') || in_array($checkTable, $allowedArray)) {
            return true;
        }
        return false;
    }

    /**
     * Render input controls for import or export
     *
     * @param array $r Configuration for element
     * @return string HTML
     */
    public function renderControls($r)
    {
        if ($this->mode === 'export') {
            if ($r['type'] === 'record') {
                return '<input type="checkbox" name="tx_impexp[exclude][' . $r['ref'] . ']" id="checkExclude' . $r['ref'] . '" value="1" /> <label for="checkExclude' . $r['ref'] . '">' . $this->getLanguageService()->getLL('impexpcore_singlereco_exclude', true) . '</label>';
            } else {
                return  $r['type'] == 'softref' ? $this->softrefSelector($r['_softRefInfo']) : '';
            }
        } else {
            // During import
            // For softreferences with editable fields:
            if ($r['type'] == 'softref' && is_array($r['_softRefInfo']['subst']) && $r['_softRefInfo']['subst']['tokenID']) {
                $tokenID = $r['_softRefInfo']['subst']['tokenID'];
                $cfg = $this->softrefCfg[$tokenID];
                if ($cfg['mode'] === 'editable') {
                    return (strlen($cfg['title']) ? '<strong>' . htmlspecialchars($cfg['title']) . '</strong><br/>' : '') . htmlspecialchars($cfg['description']) . '<br/>
						<input type="text" name="tx_impexp[softrefInputValues][' . $tokenID . ']" value="' . htmlspecialchars((isset($this->softrefInputValues[$tokenID]) ? $this->softrefInputValues[$tokenID] : $cfg['defValue'])) . '" />';
                }
            }
        }
        return '';
    }

    /**
     * Selectorbox with export options for soft references
     *
     * @param array $cfg Softref configuration array. An export box is shown only if a substitution scheme is found for the soft reference.
     * @return string Selector box HTML
     */
    public function softrefSelector($cfg)
    {
        // Looking for file ID if any:
        $fI = $cfg['file_ID'] ? $this->dat['header']['files'][$cfg['file_ID']] : [];
        // Substitution scheme has to be around and RTE images MUST be exported.
        if (is_array($cfg['subst']) && $cfg['subst']['tokenID'] && !$fI['RTE_ORIG_ID']) {
            // Create options:
            $optValues = [];
            $optValues[''] = '';
            $optValues['editable'] = $this->getLanguageService()->getLL('impexpcore_softrefsel_editable');
            $optValues['exclude'] = $this->getLanguageService()->getLL('impexpcore_softrefsel_exclude');
            // Get current value:
            $value = $this->softrefCfg[$cfg['subst']['tokenID']]['mode'];
            // Render options selector:
            $selectorbox = $this->renderSelectBox(('tx_impexp[softrefCfg][' . $cfg['subst']['tokenID'] . '][mode]'), $value, $optValues) . '<br/>';
            if ($value === 'editable') {
                $descriptionField = '';
                // Title:
                if (strlen($cfg['subst']['title'])) {
                    $descriptionField .= '
					<input type="hidden" name="tx_impexp[softrefCfg][' . $cfg['subst']['tokenID'] . '][title]" value="' . htmlspecialchars($cfg['subst']['title']) . '" />
					<strong>' . htmlspecialchars($cfg['subst']['title']) . '</strong><br/>';
                }
                // Description:
                if (!strlen($cfg['subst']['description'])) {
                    $descriptionField .= '
					' . $this->getLanguageService()->getLL('impexpcore_printerror_description', true) . '<br/>
					<input type="text" name="tx_impexp[softrefCfg][' . $cfg['subst']['tokenID'] . '][description]" value="' . htmlspecialchars($this->softrefCfg[$cfg['subst']['tokenID']]['description']) . '" />';
                } else {
                    $descriptionField .= '

					<input type="hidden" name="tx_impexp[softrefCfg][' . $cfg['subst']['tokenID'] . '][description]" value="' . htmlspecialchars($cfg['subst']['description']) . '" />' . htmlspecialchars($cfg['subst']['description']);
                }
                // Default Value:
                $descriptionField .= '<input type="hidden" name="tx_impexp[softrefCfg][' . $cfg['subst']['tokenID'] . '][defValue]" value="' . htmlspecialchars($cfg['subst']['tokenValue']) . '" />';
            } else {
                $descriptionField = '';
            }
            return $selectorbox . $descriptionField;
        }
        return '';
    }

    /**
     * Verifies that the input path (relative to PATH_site) is found in the backend users filemounts.
     * If it doesn't it will try to find another relative filemount for the user and return an alternative path prefix for the file.
     *
     * @param string $dirPrefix Path relative to PATH_site
     * @param bool $noAlternative If set, Do not look for alternative path! Just return FALSE
     * @return string|bool If a path is available that will be returned, otherwise FALSE.
     */
    public function verifyFolderAccess($dirPrefix, $noAlternative = false)
    {
        $fileProcObj = $this->getFileProcObj();
        // Check, if dirPrefix is inside a valid Filemount for user:
        $result = $fileProcObj->checkPathAgainstMounts(PATH_site . $dirPrefix);
        // If not, try to find another relative filemount and use that instead:
        if (!$result) {
            if ($noAlternative) {
                return false;
            }
            // Find first web folder:
            $result = $fileProcObj->findFirstWebFolder();
            // If that succeeded, return the path to it:
            if ($result) {
                // Remove the "fileadmin/" prefix of input path - and append the rest to the return value:
                if (GeneralUtility::isFirstPartOfStr($dirPrefix, $this->fileadminFolderName . '/')) {
                    $dirPrefix = substr($dirPrefix, strlen($this->fileadminFolderName . '/'));
                }
                return PathUtility::stripPathSitePrefix($fileProcObj->mounts[$result]['path'] . $dirPrefix);
            }
        } else {
            return $dirPrefix;
        }
        return false;
    }

    /*****************************
     * Helper functions of kinds
     *****************************/

    /**
     *
     * @return string
     */
    protected function getTemporaryFolderName()
    {
        $temporaryPath = PATH_site . 'typo3temp/';
        do {
            $temporaryFolderName = $temporaryPath . 'export_temp_files_' . mt_rand(1, PHP_INT_MAX);
        } while (is_dir($temporaryFolderName));
        GeneralUtility::mkdir($temporaryFolderName);
        return $temporaryFolderName;
    }

    /**
     * Recursively flattening the idH array
     *
     * @param array $idH Page uid hierarchy
     * @param array $a Accumulation array of pages (internal, don't set from outside)
     * @return array Array with uid-uid pairs for all pages in the page tree.
     * @see Import::flatInversePageTree_pid()
     */
    public function flatInversePageTree($idH, $a = [])
    {
        if (is_array($idH)) {
            $idH = array_reverse($idH);
            foreach ($idH as $k => $v) {
                $a[$v['uid']] = $v['uid'];
                if (is_array($v['subrow'])) {
                    $a = $this->flatInversePageTree($v['subrow'], $a);
                }
            }
        }
        return $a;
    }

    /**
     * Returns TRUE if the input table name is to be regarded as a static relation (that is, not exported etc).
     *
     * @param string $table Table name
     * @return bool TRUE, if table is marked static
     */
    public function isTableStatic($table)
    {
        if (is_array($GLOBALS['TCA'][$table])) {
            return $GLOBALS['TCA'][$table]['ctrl']['is_static'] || in_array($table, $this->relStaticTables) || in_array('_ALL', $this->relStaticTables);
        }
        return false;
    }

    /**
     * Returns TRUE if the input table name is to be included as relation
     *
     * @param string $table Table name
     * @return bool TRUE, if table is marked static
     */
    public function inclRelation($table)
    {
        return is_array($GLOBALS['TCA'][$table])
            && (in_array($table, $this->relOnlyTables) || in_array('_ALL', $this->relOnlyTables))
            && $this->getBackendUser()->check('tables_select', $table);
    }

    /**
     * Returns TRUE if the element should be excluded as static record.
     *
     * @param string $table Table name
     * @param int $uid UID value
     * @return bool TRUE, if table is marked static
     */
    public function isExcluded($table, $uid)
    {
        return (bool)$this->excludeMap[$table . ':' . $uid];
    }

    /**
     * Returns TRUE if soft reference should be included in exported file.
     *
     * @param string $tokenID Token ID for soft reference
     * @return bool TRUE if softreference media should be included
     */
    public function includeSoftref($tokenID)
    {
        $mode = $this->softrefCfg[$tokenID]['mode'];
        return $tokenID && $mode !== 'exclude' && $mode !== 'editable';
    }

    /**
     * Checking if a PID is in the webmounts of the user
     *
     * @param int $pid Page ID to check
     * @return bool TRUE if OK
     */
    public function checkPID($pid)
    {
        if (!isset($this->checkPID_cache[$pid])) {
            $this->checkPID_cache[$pid] = (bool)$this->getBackendUser()->isInWebMount($pid);
        }
        return $this->checkPID_cache[$pid];
    }

    /**
     * Checks if the position of an updated record is configured to be corrected. This can be disabled globally and changed for elements individually.
     *
     * @param string $table Table name
     * @param int $uid Uid or record
     * @return bool TRUE if the position of the record should be updated to match the one in the import structure
     */
    public function dontIgnorePid($table, $uid)
    {
        return $this->import_mode[$table . ':' . $uid] !== 'ignore_pid' && (!$this->global_ignore_pid || $this->import_mode[$table . ':' . $uid] === 'respect_pid');
    }

    /**
     * Checks if the record exists
     *
     * @param string $table Table name
     * @param int $uid UID of record
     * @param string $fields Field list to select. Default is "uid,pid
     * @return array Result of \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord() which means the record if found, otherwise FALSE
     */
    public function doesRecordExist($table, $uid, $fields = '')
    {
        return BackendUtility::getRecord($table, $uid, $fields ? $fields : 'uid,pid');
    }

    /**
     * Returns the page title path of a PID value. Results are cached internally
     *
     * @param int $pid Record PID to check
     * @return string The path for the input PID
     */
    public function getRecordPath($pid)
    {
        if (!isset($this->cache_getRecordPath[$pid])) {
            $clause = $this->getBackendUser()->getPagePermsClause(1);
            $this->cache_getRecordPath[$pid] = (string)BackendUtility::getRecordPath($pid, $clause, 20);
        }
        return $this->cache_getRecordPath[$pid];
    }

    /**
     * Makes a selector-box from optValues
     *
     * @param string $prefix Form element name
     * @param string $value Current value
     * @param array $optValues Options to display (key/value pairs)
     * @return string HTML select element
     */
    public function renderSelectBox($prefix, $value, $optValues)
    {
        $opt = [];
        $isSelFlag = 0;
        foreach ($optValues as $k => $v) {
            $sel = (string)$k === (string)$value ? ' selected="selected"' : '';
            if ($sel) {
                $isSelFlag++;
            }
            $opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
        }
        if (!$isSelFlag && (string)$value !== '') {
            $opt[] = '<option value="' . htmlspecialchars($value) . '" selected="selected">' . htmlspecialchars(('[\'' . $value . '\']')) . '</option>';
        }
        return '<select name="' . $prefix . '">' . implode('', $opt) . '</select>';
    }

    /**
     * Compares two records, the current database record and the one from the import memory.
     * Will return HTML code to show any differences between them!
     *
     * @param array $databaseRecord Database record, all fields (new values)
     * @param array $importRecord Import memorys record for the same table/uid, all fields (old values)
     * @param string $table The table name of the record
     * @param bool $inverseDiff Inverse the diff view (switch red/green, needed for pre-update difference view)
     * @return string HTML
     */
    public function compareRecords($databaseRecord, $importRecord, $table, $inverseDiff = false)
    {
        // Initialize:
        $output = [];
        $diffUtility = GeneralUtility::makeInstance(DiffUtility::class);
        // Check if both inputs are records:
        if (is_array($databaseRecord) && is_array($importRecord)) {
            // Traverse based on database record
            foreach ($databaseRecord as $fN => $value) {
                if (is_array($GLOBALS['TCA'][$table]['columns'][$fN]) && $GLOBALS['TCA'][$table]['columns'][$fN]['config']['type'] != 'passthrough') {
                    if (isset($importRecord[$fN])) {
                        if (trim($databaseRecord[$fN]) !== trim($importRecord[$fN])) {
                            // Create diff-result:
                            $output[$fN] = $diffUtility->makeDiffDisplay(BackendUtility::getProcessedValue($table, $fN, !$inverseDiff ? $importRecord[$fN] : $databaseRecord[$fN], 0, 1, 1), BackendUtility::getProcessedValue($table, $fN, !$inverseDiff ? $databaseRecord[$fN] : $importRecord[$fN], 0, 1, 1));
                        }
                        unset($importRecord[$fN]);
                    }
                }
            }
            // Traverse remaining in import record:
            foreach ($importRecord as $fN => $value) {
                if (is_array($GLOBALS['TCA'][$table]['columns'][$fN]) && $GLOBALS['TCA'][$table]['columns'][$fN]['config']['type'] !== 'passthrough') {
                    $output[$fN] = '<strong>Field missing</strong> in database';
                }
            }
            // Create output:
            if (!empty($output)) {
                $tRows = [];
                foreach ($output as $fN => $state) {
                    $tRows[] = '
						<tr>
							<td class="bgColor5">' . $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$fN]['label'], true) . ' (' . htmlspecialchars($fN) . ')</td>
							<td class="bgColor4">' . $state . '</td>
						</tr>
					';
                }
                $output = '<table border="0" cellpadding="0" cellspacing="1">' . implode('', $tRows) . '</table>';
            } else {
                $output = 'Match';
            }
            return '<strong class="text-nowrap">[' . htmlspecialchars(($table . ':' . $importRecord['uid'] . ' => ' . $databaseRecord['uid'])) . ']:</strong> ' . $output;
        }
        return 'ERROR: One of the inputs were not an array!';
    }

    /**
     * Creates the original file name for a copy-RTE image (magic type)
     *
     * @param string $string RTE copy filename, eg. "RTEmagicC_user_pm_icon_01.gif.gif
     * @return string|NULL RTE original filename, eg. "RTEmagicP_user_pm_icon_01.gif". If the input filename was NOT prefixed RTEmagicC_ as RTE images would be, NULL is returned!
     */
    public function getRTEoriginalFilename($string)
    {
        // If "magic image":
        if (GeneralUtility::isFirstPartOfStr($string, 'RTEmagicC_')) {
            // Find original file:
            $pI = pathinfo(substr($string, strlen('RTEmagicC_')));
            $filename = substr($pI['basename'], 0, -strlen(('.' . $pI['extension'])));
            $origFilePath = 'RTEmagicP_' . $filename;
            return $origFilePath;
        }
        return null;
    }

    /**
     * Returns file processing object, initialized only once.
     *
     * @return ExtendedFileUtility File processor object
     */
    public function getFileProcObj()
    {
        if ($this->fileProcObj === null) {
            $this->fileProcObj = GeneralUtility::makeInstance(ExtendedFileUtility::class);
            $this->fileProcObj->init([], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
            $this->fileProcObj->setActionPermissions();
        }
        return $this->fileProcObj;
    }

    /**
     * Call Hook
     *
     * @param string $name Name of the hook
     * @param array $params Array with params
     * @return void
     */
    public function callHook($name, $params)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php'][$name])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php'][$name] as $hook) {
                GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
    }

    /*****************************
     * Error handling
     *****************************/

    /**
     * Sets error message in the internal error log
     *
     * @param string $msg Error message
     * @return void
     */
    public function error($msg)
    {
        $this->errorLog[] = $msg;
    }

    /**
     * Returns a table with the error-messages.
     *
     * @return string HTML print of error log
     */
    public function printErrorLog()
    {
        return !empty($this->errorLog) ? DebugUtility::viewArray($this->errorLog) : '';
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
