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
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
 * T3D file Export library (TYPO3 Record Document)
 */
class Export extends ImportExport
{
    /**
     * 1MB max file size
     *
     * @var int
     */
    public $maxFileSize = 1000000;

    /**
     * 1MB max record size
     *
     * @var int
     */
    public $maxRecordSize = 1000000;

    /**
     * 10MB max export size
     *
     * @var int
     */
    public $maxExportSize = 10000000;

    /**
     * Set  by user: If set, compression in t3d files is disabled
     *
     * @var bool
     */
    public $dontCompress = false;

    /**
     * If set, HTML file resources are included.
     *
     * @var bool
     */
    public $includeExtFileResources = false;

    /**
     * Files with external media (HTML/css style references inside)
     *
     * @var string
     */
    public $extFileResourceExtensions = 'html,htm,css';

    /**
     * Keys are [recordname], values are an array of fields to be included
     * in the export
     *
     * @var array
     */
    protected $recordTypesIncludeFields = [];

    /**
     * Default array of fields to be included in the export
     *
     * @var array
     */
    protected $defaultRecordIncludeFields = ['uid', 'pid'];

    /**
     * @var bool
     */
    protected $saveFilesOutsideExportFile = false;

    /**
     * @var NULL|string
     */
    protected $temporaryFilesPathForExport = null;

    /**************************
     * Initialize
     *************************/

    /**
     * Init the object
     *
     * @param bool $dontCompress If set, compression of t3d files is disabled
     * @return void
     */
    public function init($dontCompress = false)
    {
        parent::init();
        $this->dontCompress = $dontCompress;
        $this->mode = 'export';
    }

    /**************************
     * Export / Init + Meta Data
     *************************/

    /**
     * Set header basics
     *
     * @return void
     */
    public function setHeaderBasics()
    {
        // Initializing:
        if (is_array($this->softrefCfg)) {
            foreach ($this->softrefCfg as $key => $value) {
                if (!strlen($value['mode'])) {
                    unset($this->softrefCfg[$key]);
                }
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
        // Soft Reference mode for elements
        $this->dat['header']['softrefCfg'] = $this->softrefCfg;
        // List of extensions the import depends on.
        $this->dat['header']['extensionDependencies'] = $this->extensionDependencies;
    }

    /**
     * Set charset
     *
     * @param string $charset Charset for the content in the export. During import the character set will be converted if the target system uses another charset.
     * @return void
     */
    public function setCharset($charset)
    {
        $this->dat['header']['charset'] = $charset;
    }

    /**
     * Sets meta data
     *
     * @param string $title Title of the export
     * @param string $description Description of the export
     * @param string $notes Notes about the contents
     * @param string $packager_username Backend Username of the packager (the guy making the export)
     * @param string $packager_name Real name of the packager
     * @param string $packager_email Email of the packager
     * @return void
     */
    public function setMetaData($title, $description, $notes, $packager_username, $packager_name, $packager_email)
    {
        $this->dat['header']['meta'] = [
            'title' => $title,
            'description' => $description,
            'notes' => $notes,
            'packager_username' => $packager_username,
            'packager_name' => $packager_name,
            'packager_email' => $packager_email,
            'TYPO3_version' => TYPO3_version,
            'created' => strftime('%A %e. %B %Y', $GLOBALS['EXEC_TIME'])
        ];
    }

    /**
     * Option to enable having the files not included in the export file.
     * The files are saved to a temporary folder instead.
     *
     * @param bool $saveFilesOutsideExportFile
     * @see getTemporaryFilesPathForExport()
     */
    public function setSaveFilesOutsideExportFile($saveFilesOutsideExportFile)
    {
        $this->saveFilesOutsideExportFile = $saveFilesOutsideExportFile;
    }

    /**************************
     * Export / Init Page tree
     *************************/

    /**
     * Sets the page-tree array in the export header and returns the array in a flattened version
     *
     * @param array $idH Hierarchy of ids, the page tree: array([uid] => array("uid" => [uid], "subrow" => array(.....)), [uid] => ....)
     * @return array The hierarchical page tree converted to a one-dimensional list of pages
     */
    public function setPageTree($idH)
    {
        $this->dat['header']['pagetree'] = $this->unsetExcludedSections($idH);
        return $this->flatInversePageTree($this->dat['header']['pagetree']);
    }

    /**
     * Removes entries in the page tree which are found in ->excludeMap[]
     *
     * @param array $idH Page uid hierarchy
     * @return array Modified input array
     * @access private
     * @see setPageTree()
     */
    public function unsetExcludedSections($idH)
    {
        if (is_array($idH)) {
            foreach ($idH as $k => $v) {
                if ($this->excludeMap['pages:' . $idH[$k]['uid']]) {
                    unset($idH[$k]);
                } elseif (is_array($idH[$k]['subrow'])) {
                    $idH[$k]['subrow'] = $this->unsetExcludedSections($idH[$k]['subrow']);
                }
            }
        }
        return $idH;
    }

    /**************************
     * Export
     *************************/

    /**
     * Sets the fields of record types to be included in the export
     *
     * @param array $recordTypesIncludeFields Keys are [recordname], values are an array of fields to be included in the export
     * @throws Exception if an array value is not type of array
     * @return void
     */
    public function setRecordTypesIncludeFields(array $recordTypesIncludeFields)
    {
        foreach ($recordTypesIncludeFields as $table => $fields) {
            if (!is_array($fields)) {
                throw new Exception('The include fields for record type ' . htmlspecialchars($table) . ' are not defined by an array.', 1391440658);
            }
            $this->setRecordTypeIncludeFields($table, $fields);
        }
    }

    /**
     * Sets the fields of a record type to be included in the export
     *
     * @param string $table The record type
     * @param array $fields The fields to be included
     * @return void
     */
    public function setRecordTypeIncludeFields($table, array $fields)
    {
        $this->recordTypesIncludeFields[$table] = $fields;
    }

    /**
     * Adds the record $row from $table.
     * No checking for relations done here. Pure data.
     *
     * @param string $table Table name
     * @param array $row Record row.
     * @param int $relationLevel (Internal) if the record is added as a relation, this is set to the "level" it was on.
     * @return void
     */
    public function export_addRecord($table, $row, $relationLevel = 0)
    {
        BackendUtility::workspaceOL($table, $row);
        if ((string)$table !== '' && is_array($row) && $row['uid'] > 0 && !$this->excludeMap[$table . ':' . $row['uid']]) {
            if ($this->checkPID($table === 'pages' ? $row['uid'] : $row['pid'])) {
                if (!isset($this->dat['records'][$table . ':' . $row['uid']])) {
                    // Prepare header info:
                    $row = $this->filterRecordFields($table, $row);
                    $headerInfo = [];
                    $headerInfo['uid'] = $row['uid'];
                    $headerInfo['pid'] = $row['pid'];
                    $headerInfo['title'] = GeneralUtility::fixed_lgd_cs(BackendUtility::getRecordTitle($table, $row), 40);
                    $headerInfo['size'] = strlen(serialize($row));
                    if ($relationLevel) {
                        $headerInfo['relationLevel'] = $relationLevel;
                    }
                    // If record content is not too large in size, set the header content and add the rest:
                    if ($headerInfo['size'] < $this->maxRecordSize) {
                        // Set the header summary:
                        $this->dat['header']['records'][$table][$row['uid']] = $headerInfo;
                        // Create entry in the PID lookup:
                        $this->dat['header']['pid_lookup'][$row['pid']][$table][$row['uid']] = 1;
                        // Initialize reference index object:
                        $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
                        // Yes to workspace overlays for exporting....
                        $refIndexObj->WSOL = true;
                        $relations = $refIndexObj->getRelations($table, $row);
                        $relations = $this->fixFileIDsInRelations($relations);
                        $relations = $this->removeSoftrefsHavingTheSameDatabaseRelation($relations);
                        // Data:
                        $this->dat['records'][$table . ':' . $row['uid']] = [];
                        $this->dat['records'][$table . ':' . $row['uid']]['data'] = $row;
                        $this->dat['records'][$table . ':' . $row['uid']]['rels'] = $relations;
                        // Add information about the relations in the record in the header:
                        $this->dat['header']['records'][$table][$row['uid']]['rels'] = $this->flatDBrels($this->dat['records'][$table . ':' . $row['uid']]['rels']);
                        // Add information about the softrefs to header:
                        $this->dat['header']['records'][$table][$row['uid']]['softrefs'] = $this->flatSoftRefs($this->dat['records'][$table . ':' . $row['uid']]['rels']);
                    } else {
                        $this->error('Record ' . $table . ':' . $row['uid'] . ' was larger than maxRecordSize (' . GeneralUtility::formatSize($this->maxRecordSize) . ')');
                    }
                } else {
                    $this->error('Record ' . $table . ':' . $row['uid'] . ' already added.');
                }
            } else {
                $this->error('Record ' . $table . ':' . $row['uid'] . ' was outside your DB mounts!');
            }
        }
    }

    /**
     * This changes the file reference ID from a hash based on the absolute file path
     * (coming from ReferenceIndex) to a hash based on the relative file path.
     *
     * @param array $relations
     * @return array
     */
    protected function fixFileIDsInRelations(array $relations)
    {
        foreach ($relations as $field => $relation) {
            if (isset($relation['type']) && $relation['type'] === 'file') {
                foreach ($relation['newValueFiles'] as $key => $fileRelationData) {
                    $absoluteFilePath = $fileRelationData['ID_absFile'];
                    if (GeneralUtility::isFirstPartOfStr($absoluteFilePath, PATH_site)) {
                        $relatedFilePath = PathUtility::stripPathSitePrefix($absoluteFilePath);
                        $relations[$field]['newValueFiles'][$key]['ID'] = md5($relatedFilePath);
                    }
                }
            }
            if ($relation['type'] === 'flex') {
                if (is_array($relation['flexFormRels']['file'])) {
                    foreach ($relation['flexFormRels']['file'] as $key => $subList) {
                        foreach ($subList as $subKey => $fileRelationData) {
                            $absoluteFilePath = $fileRelationData['ID_absFile'];
                            if (GeneralUtility::isFirstPartOfStr($absoluteFilePath, PATH_site)) {
                                $relatedFilePath = PathUtility::stripPathSitePrefix($absoluteFilePath);
                                $relations[$field]['flexFormRels']['file'][$key][$subKey]['ID'] = md5($relatedFilePath);
                            }
                        }
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * Relations could contain db relations to sys_file records. Some configuration combinations of TCA and
     * SoftReferenceIndex create also softref relation entries for the identical file. This results
     * in double included files, one in array "files" and one in array "file_fal".
     * This function checks the relations for this double inclusions and removes the redundant softref relation.
     *
     * @param array $relations
     * @return array
     */
    protected function removeSoftrefsHavingTheSameDatabaseRelation($relations)
    {
        $fixedRelations = [];
        foreach ($relations as $field => $relation) {
            $newRelation = $relation;
            if (isset($newRelation['type']) && $newRelation['type'] === 'db') {
                foreach ($newRelation['itemArray'] as $key => $dbRelationData) {
                    if ($dbRelationData['table'] === 'sys_file') {
                        if (isset($newRelation['softrefs']['keys']['typolink'])) {
                            foreach ($newRelation['softrefs']['keys']['typolink'] as $softrefKey => $softRefData) {
                                if ($softRefData['subst']['type'] === 'file') {
                                    $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject($softRefData['subst']['relFileName']);
                                    if ($file instanceof File) {
                                        if ($file->getUid() == $dbRelationData['id']) {
                                            unset($newRelation['softrefs']['keys']['typolink'][$softrefKey]);
                                        }
                                    }
                                }
                            }
                            if (empty($newRelation['softrefs']['keys']['typolink'])) {
                                unset($newRelation['softrefs']);
                            }
                        }
                    }
                }
            }
            $fixedRelations[$field] = $newRelation;
        }
        return $fixedRelations;
    }

    /**
     * This analyses the existing added records, finds all database relations to records and adds these records to the export file.
     * This function can be called repeatedly until it returns an empty array.
     * In principle it should not allow to infinite recursivity, but you better set a limit...
     * Call this BEFORE the ext_addFilesFromRelations (so files from added relations are also included of course)
     *
     * @param int $relationLevel Recursion level
     * @return array overview of relations found and added: Keys [table]:[uid], values array with table and id
     * @see export_addFilesFromRelations()
     */
    public function export_addDBRelations($relationLevel = 0)
    {
        // Traverse all "rels" registered for "records"
        if (!is_array($this->dat['records'])) {
            $this->error('There were no records available.');
            return [];
        }
        $addR = [];
        foreach ($this->dat['records'] as $k => $value) {
            if (!is_array($this->dat['records'][$k])) {
                continue;
            }
            foreach ($this->dat['records'][$k]['rels'] as $fieldname => $vR) {
                // For all DB types of relations:
                if ($vR['type'] == 'db') {
                    foreach ($vR['itemArray'] as $fI) {
                        $this->export_addDBRelations_registerRelation($fI, $addR);
                    }
                }
                // For all flex/db types of relations:
                if ($vR['type'] == 'flex') {
                    // DB relations in flex form fields:
                    if (is_array($vR['flexFormRels']['db'])) {
                        foreach ($vR['flexFormRels']['db'] as $subList) {
                            foreach ($subList as $fI) {
                                $this->export_addDBRelations_registerRelation($fI, $addR);
                            }
                        }
                    }
                    // DB oriented soft references in flex form fields:
                    if (is_array($vR['flexFormRels']['softrefs'])) {
                        foreach ($vR['flexFormRels']['softrefs'] as $subList) {
                            foreach ($subList['keys'] as $spKey => $elements) {
                                foreach ($elements as $el) {
                                    if ($el['subst']['type'] === 'db' && $this->includeSoftref($el['subst']['tokenID'])) {
                                        list($tempTable, $tempUid) = explode(':', $el['subst']['recordRef']);
                                        $fI = [
                                            'table' => $tempTable,
                                            'id' => $tempUid
                                        ];
                                        $this->export_addDBRelations_registerRelation($fI, $addR, $el['subst']['tokenID']);
                                    }
                                }
                            }
                        }
                    }
                }
                // In any case, if there are soft refs:
                if (is_array($vR['softrefs']['keys'])) {
                    foreach ($vR['softrefs']['keys'] as $spKey => $elements) {
                        foreach ($elements as $el) {
                            if ($el['subst']['type'] === 'db' && $this->includeSoftref($el['subst']['tokenID'])) {
                                list($tempTable, $tempUid) = explode(':', $el['subst']['recordRef']);
                                $fI = [
                                    'table' => $tempTable,
                                    'id' => $tempUid
                                ];
                                $this->export_addDBRelations_registerRelation($fI, $addR, $el['subst']['tokenID']);
                            }
                        }
                    }
                }
            }
        }

        // Now, if there were new records to add, do so:
        if (!empty($addR)) {
            foreach ($addR as $fI) {
                // Get and set record:
                $row = BackendUtility::getRecord($fI['table'], $fI['id']);
                if (is_array($row)) {
                    $this->export_addRecord($fI['table'], $row, $relationLevel + 1);
                }
                // Set status message
                // Relation pointers always larger than zero except certain "select" types with
                // negative values pointing to uids - but that is not supported here.
                if ($fI['id'] > 0) {
                    $rId = $fI['table'] . ':' . $fI['id'];
                    if (!isset($this->dat['records'][$rId])) {
                        $this->dat['records'][$rId] = 'NOT_FOUND';
                        $this->error('Relation record ' . $rId . ' was not found!');
                    }
                }
            }
        }
        // Return overview of relations found and added
        return $addR;
    }

    /**
     * Helper function for export_addDBRelations()
     *
     * @param array $fI Array with table/id keys to add
     * @param array $addR Add array, passed by reference to be modified
     * @param string $tokenID Softref Token ID, if applicable.
     * @return void
     * @see export_addDBRelations()
     */
    public function export_addDBRelations_registerRelation($fI, &$addR, $tokenID = '')
    {
        $rId = $fI['table'] . ':' . $fI['id'];
        if (
            isset($GLOBALS['TCA'][$fI['table']]) && !$this->isTableStatic($fI['table']) && !$this->isExcluded($fI['table'], $fI['id'])
            && (!$tokenID || $this->includeSoftref($tokenID)) && $this->inclRelation($fI['table'])
        ) {
            if (!isset($this->dat['records'][$rId])) {
                // Set this record to be included since it is not already.
                $addR[$rId] = $fI;
            }
        }
    }

    /**
     * This adds all files in relations.
     * Call this method AFTER adding all records including relations.
     *
     * @return void
     * @see export_addDBRelations()
     */
    public function export_addFilesFromRelations()
    {
        // Traverse all "rels" registered for "records"
        if (!is_array($this->dat['records'])) {
            $this->error('There were no records available.');
            return;
        }
        foreach ($this->dat['records'] as $k => $value) {
            if (!isset($this->dat['records'][$k]['rels']) || !is_array($this->dat['records'][$k]['rels'])) {
                continue;
            }
            foreach ($this->dat['records'][$k]['rels'] as $fieldname => $vR) {
                // For all file type relations:
                if ($vR['type'] == 'file') {
                    foreach ($vR['newValueFiles'] as $key => $fI) {
                        $this->export_addFile($fI, $k, $fieldname);
                        // Remove the absolute reference to the file so it doesn't expose absolute paths from source server:
                        unset($this->dat['records'][$k]['rels'][$fieldname]['newValueFiles'][$key]['ID_absFile']);
                    }
                }
                // For all flex type relations:
                if ($vR['type'] == 'flex') {
                    if (is_array($vR['flexFormRels']['file'])) {
                        foreach ($vR['flexFormRels']['file'] as $key => $subList) {
                            foreach ($subList as $subKey => $fI) {
                                $this->export_addFile($fI, $k, $fieldname);
                                // Remove the absolute reference to the file so it doesn't expose absolute paths from source server:
                                unset($this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['file'][$key][$subKey]['ID_absFile']);
                            }
                        }
                    }
                    // DB oriented soft references in flex form fields:
                    if (is_array($vR['flexFormRels']['softrefs'])) {
                        foreach ($vR['flexFormRels']['softrefs'] as $key => $subList) {
                            foreach ($subList['keys'] as $spKey => $elements) {
                                foreach ($elements as $subKey => $el) {
                                    if ($el['subst']['type'] === 'file' && $this->includeSoftref($el['subst']['tokenID'])) {
                                        // Create abs path and ID for file:
                                        $ID_absFile = GeneralUtility::getFileAbsFileName(PATH_site . $el['subst']['relFileName']);
                                        $ID = md5($el['subst']['relFileName']);
                                        if ($ID_absFile) {
                                            if (!$this->dat['files'][$ID]) {
                                                $fI = [
                                                    'filename' => PathUtility::basename($ID_absFile),
                                                    'ID_absFile' => $ID_absFile,
                                                    'ID' => $ID,
                                                    'relFileName' => $el['subst']['relFileName']
                                                ];
                                                $this->export_addFile($fI, '_SOFTREF_');
                                            }
                                            $this->dat['records'][$k]['rels'][$fieldname]['flexFormRels']['softrefs'][$key]['keys'][$spKey][$subKey]['file_ID'] = $ID;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                // In any case, if there are soft refs:
                if (is_array($vR['softrefs']['keys'])) {
                    foreach ($vR['softrefs']['keys'] as $spKey => $elements) {
                        foreach ($elements as $subKey => $el) {
                            if ($el['subst']['type'] === 'file' && $this->includeSoftref($el['subst']['tokenID'])) {
                                // Create abs path and ID for file:
                                $ID_absFile = GeneralUtility::getFileAbsFileName(PATH_site . $el['subst']['relFileName']);
                                $ID = md5($el['subst']['relFileName']);
                                if ($ID_absFile) {
                                    if (!$this->dat['files'][$ID]) {
                                        $fI = [
                                            'filename' => PathUtility::basename($ID_absFile),
                                            'ID_absFile' => $ID_absFile,
                                            'ID' => $ID,
                                            'relFileName' => $el['subst']['relFileName']
                                        ];
                                        $this->export_addFile($fI, '_SOFTREF_');
                                    }
                                    $this->dat['records'][$k]['rels'][$fieldname]['softrefs']['keys'][$spKey][$subKey]['file_ID'] = $ID;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * This adds all files from sys_file records
     *
     * @return void
     */
    public function export_addFilesFromSysFilesRecords()
    {
        if (!isset($this->dat['header']['records']['sys_file']) || !is_array($this->dat['header']['records']['sys_file'])) {
            return;
        }
        foreach ($this->dat['header']['records']['sys_file'] as $sysFileUid => $_) {
            $recordData = $this->dat['records']['sys_file:' . $sysFileUid]['data'];
            $file = ResourceFactory::getInstance()->createFileObject($recordData);
            $this->export_addSysFile($file);
        }
    }

    /**
     * Adds a files content from a sys file record to the export memory
     *
     * @param File $file
     * @return void
     */
    public function export_addSysFile(File $file)
    {
        if ($file->getProperty('size') >= $this->maxFileSize) {
            $this->error('File ' . $file->getPublicUrl() . ' was larger (' . GeneralUtility::formatSize($file->getProperty('size')) . ') than the maxFileSize (' . GeneralUtility::formatSize($this->maxFileSize) . ')! Skipping.');
            return;
        }
        $fileContent = '';
        try {
            if (!$this->saveFilesOutsideExportFile) {
                $fileContent = $file->getContents();
            } else {
                $file->checkActionPermission('read');
            }
        } catch (\Exception $e) {
            $this->error('Error when trying to add file ' . $file->getCombinedIdentifier() . ': ' . $e->getMessage());
            return;
        }
        $fileUid = $file->getUid();
        $fileInfo = $file->getStorage()->getFileInfo($file);
        // we sadly have to cast it to string here, because the size property is also returning a string
        $fileSize = (string)$fileInfo['size'];
        if ($fileSize !== $file->getProperty('size')) {
            $this->error('File size of ' . $file->getCombinedIdentifier() . ' is not up-to-date in index! File added with current size.');
            $this->dat['records']['sys_file:' . $fileUid]['data']['size'] = $fileSize;
        }
        $fileSha1 = $file->getStorage()->hashFile($file, 'sha1');
        if ($fileSha1 !== $file->getProperty('sha1')) {
            $this->error('File sha1 hash of ' . $file->getCombinedIdentifier() . ' is not up-to-date in index! File added on current sha1.');
            $this->dat['records']['sys_file:' . $fileUid]['data']['sha1'] = $fileSha1;
        }

        $fileRec = [];
        $fileRec['filesize'] = $fileSize;
        $fileRec['filename'] = $file->getProperty('name');
        $fileRec['filemtime'] = $file->getProperty('modification_date');

        // build unique id based on the storage and the file identifier
        $fileId = md5($file->getStorage()->getUid() . ':' . $file->getProperty('identifier_hash'));

        // Setting this data in the header
        $this->dat['header']['files_fal'][$fileId] = $fileRec;

        if (!$this->saveFilesOutsideExportFile) {
            // ... and finally add the heavy stuff:
            $fileRec['content'] = $fileContent;
        } else {
            GeneralUtility::upload_copy_move($file->getForLocalProcessing(false), $this->getTemporaryFilesPathForExport() . $file->getProperty('sha1'));
        }
        $fileRec['content_sha1'] = $fileSha1;

        $this->dat['files_fal'][$fileId] = $fileRec;
    }

    /**
     * Adds a files content to the export memory
     *
     * @param array $fI File information with three keys: "filename" = filename without path, "ID_absFile" = absolute filepath to the file (including the filename), "ID" = md5 hash of "ID_absFile". "relFileName" is optional for files attached to records, but mandatory for soft referenced files (since the relFileName determines where such a file should be stored!)
     * @param string $recordRef If the file is related to a record, this is the id on the form [table]:[id]. Information purposes only.
     * @param string $fieldname If the file is related to a record, this is the field name it was related to. Information purposes only.
     * @return void
     */
    public function export_addFile($fI, $recordRef = '', $fieldname = '')
    {
        if (!@is_file($fI['ID_absFile'])) {
            $this->error($fI['ID_absFile'] . ' was not a file! Skipping.');
            return;
        }
        if (filesize($fI['ID_absFile']) >= $this->maxFileSize) {
            $this->error($fI['ID_absFile'] . ' was larger (' . GeneralUtility::formatSize(filesize($fI['ID_absFile'])) . ') than the maxFileSize (' . GeneralUtility::formatSize($this->maxFileSize) . ')! Skipping.');
            return;
        }
        $fileInfo = stat($fI['ID_absFile']);
        $fileRec = [];
        $fileRec['filesize'] = $fileInfo['size'];
        $fileRec['filename'] = PathUtility::basename($fI['ID_absFile']);
        $fileRec['filemtime'] = $fileInfo['mtime'];
        //for internal type file_reference
        $fileRec['relFileRef'] = PathUtility::stripPathSitePrefix($fI['ID_absFile']);
        if ($recordRef) {
            $fileRec['record_ref'] = $recordRef . '/' . $fieldname;
        }
        if ($fI['relFileName']) {
            $fileRec['relFileName'] = $fI['relFileName'];
        }
        // Setting this data in the header
        $this->dat['header']['files'][$fI['ID']] = $fileRec;
        // ... and for the recordlisting, why not let us know WHICH relations there was...
        if ($recordRef && $recordRef !== '_SOFTREF_') {
            $refParts = explode(':', $recordRef, 2);
            if (!is_array($this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'])) {
                $this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'] = [];
            }
            $this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'][] = $fI['ID'];
        }
        $fileMd5 = md5_file($fI['ID_absFile']);
        if (!$this->saveFilesOutsideExportFile) {
            // ... and finally add the heavy stuff:
            $fileRec['content'] = GeneralUtility::getUrl($fI['ID_absFile']);
        } else {
            GeneralUtility::upload_copy_move($fI['ID_absFile'], $this->getTemporaryFilesPathForExport() . $fileMd5);
        }
        $fileRec['content_md5'] = $fileMd5;
        $this->dat['files'][$fI['ID']] = $fileRec;
        // For soft references, do further processing:
        if ($recordRef === '_SOFTREF_') {
            // RTE files?
            if ($RTEoriginal = $this->getRTEoriginalFilename(PathUtility::basename($fI['ID_absFile']))) {
                $RTEoriginal_absPath = PathUtility::dirname($fI['ID_absFile']) . '/' . $RTEoriginal;
                if (@is_file($RTEoriginal_absPath)) {
                    $RTEoriginal_ID = md5($RTEoriginal_absPath);
                    $fileInfo = stat($RTEoriginal_absPath);
                    $fileRec = [];
                    $fileRec['filesize'] = $fileInfo['size'];
                    $fileRec['filename'] = PathUtility::basename($RTEoriginal_absPath);
                    $fileRec['filemtime'] = $fileInfo['mtime'];
                    $fileRec['record_ref'] = '_RTE_COPY_ID:' . $fI['ID'];
                    $this->dat['header']['files'][$fI['ID']]['RTE_ORIG_ID'] = $RTEoriginal_ID;
                    // Setting this data in the header
                    $this->dat['header']['files'][$RTEoriginal_ID] = $fileRec;
                    $fileMd5 = md5_file($RTEoriginal_absPath);
                    if (!$this->saveFilesOutsideExportFile) {
                        // ... and finally add the heavy stuff:
                        $fileRec['content'] = GeneralUtility::getUrl($RTEoriginal_absPath);
                    } else {
                        GeneralUtility::upload_copy_move($RTEoriginal_absPath, $this->getTemporaryFilesPathForExport() . $fileMd5);
                    }
                    $fileRec['content_md5'] = $fileMd5;
                    $this->dat['files'][$RTEoriginal_ID] = $fileRec;
                } else {
                    $this->error('RTE original file "' . PathUtility::stripPathSitePrefix($RTEoriginal_absPath) . '" was not found!');
                }
            }
            // Files with external media?
            // This is only done with files grabbed by a softreference parser since it is deemed improbable that hard-referenced files should undergo this treatment.
            $html_fI = pathinfo(PathUtility::basename($fI['ID_absFile']));
            if ($this->includeExtFileResources && GeneralUtility::inList($this->extFileResourceExtensions, strtolower($html_fI['extension']))) {
                $uniquePrefix = '###' . md5($GLOBALS['EXEC_TIME']) . '###';
                if (strtolower($html_fI['extension']) === 'css') {
                    $prefixedMedias = explode($uniquePrefix, preg_replace('/(url[[:space:]]*\\([[:space:]]*["\']?)([^"\')]*)(["\']?[[:space:]]*\\))/i', '\\1' . $uniquePrefix . '\\2' . $uniquePrefix . '\\3', $fileRec['content']));
                } else {
                    // html, htm:
                    $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
                    $prefixedMedias = explode($uniquePrefix, $htmlParser->prefixResourcePath($uniquePrefix, $fileRec['content'], [], $uniquePrefix));
                }
                $htmlResourceCaptured = false;
                foreach ($prefixedMedias as $k => $v) {
                    if ($k % 2) {
                        $EXTres_absPath = GeneralUtility::resolveBackPath(PathUtility::dirname($fI['ID_absFile']) . '/' . $v);
                        $EXTres_absPath = GeneralUtility::getFileAbsFileName($EXTres_absPath);
                        if ($EXTres_absPath && GeneralUtility::isFirstPartOfStr($EXTres_absPath, PATH_site . $this->fileadminFolderName . '/') && @is_file($EXTres_absPath)) {
                            $htmlResourceCaptured = true;
                            $EXTres_ID = md5($EXTres_absPath);
                            $this->dat['header']['files'][$fI['ID']]['EXT_RES_ID'][] = $EXTres_ID;
                            $prefixedMedias[$k] = '{EXT_RES_ID:' . $EXTres_ID . '}';
                            // Add file to memory if it is not set already:
                            if (!isset($this->dat['header']['files'][$EXTres_ID])) {
                                $fileInfo = stat($EXTres_absPath);
                                $fileRec = [];
                                $fileRec['filesize'] = $fileInfo['size'];
                                $fileRec['filename'] = PathUtility::basename($EXTres_absPath);
                                $fileRec['filemtime'] = $fileInfo['mtime'];
                                $fileRec['record_ref'] = '_EXT_PARENT_:' . $fI['ID'];
                                // Media relative to the HTML file.
                                $fileRec['parentRelFileName'] = $v;
                                // Setting this data in the header
                                $this->dat['header']['files'][$EXTres_ID] = $fileRec;
                                // ... and finally add the heavy stuff:
                                $fileRec['content'] = GeneralUtility::getUrl($EXTres_absPath);
                                $fileRec['content_md5'] = md5($fileRec['content']);
                                $this->dat['files'][$EXTres_ID] = $fileRec;
                            }
                        }
                    }
                }
                if ($htmlResourceCaptured) {
                    $this->dat['files'][$fI['ID']]['tokenizedContent'] = implode('', $prefixedMedias);
                }
            }
        }
    }

    /**
     * If saveFilesOutsideExportFile is enabled, this function returns the path
     * where the files referenced in the export are copied to.
     *
     * @return string
     * @throws \RuntimeException
     * @see setSaveFilesOutsideExportFile()
     */
    public function getTemporaryFilesPathForExport()
    {
        if (!$this->saveFilesOutsideExportFile) {
            throw new \RuntimeException('You need to set saveFilesOutsideExportFile to TRUE before you want to get the temporary files path for export.', 1401205213);
        }
        if ($this->temporaryFilesPathForExport === null) {
            $temporaryFolderName = $this->getTemporaryFolderName();
            $this->temporaryFilesPathForExport = $temporaryFolderName . '/';
        }
        return $this->temporaryFilesPathForExport;
    }

    /**
     * DB relations flattend to 1-dim array.
     * The list will be unique, no table/uid combination will appear twice.
     *
     * @param array $dbrels 2-dim Array of database relations organized by table key
     * @return array 1-dim array where entries are table:uid and keys are array with table/id
     */
    public function flatDBrels($dbrels)
    {
        $list = [];
        foreach ($dbrels as $dat) {
            if ($dat['type'] == 'db') {
                foreach ($dat['itemArray'] as $i) {
                    $list[$i['table'] . ':' . $i['id']] = $i;
                }
            }
            if ($dat['type'] == 'flex' && is_array($dat['flexFormRels']['db'])) {
                foreach ($dat['flexFormRels']['db'] as $subList) {
                    foreach ($subList as $i) {
                        $list[$i['table'] . ':' . $i['id']] = $i;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * Soft References flattend to 1-dim array.
     *
     * @param array $dbrels 2-dim Array of database relations organized by table key
     * @return array 1-dim array where entries are arrays with properties of the soft link found and keys are a unique combination of field, spKey, structure path if applicable and token ID
     */
    public function flatSoftRefs($dbrels)
    {
        $list = [];
        foreach ($dbrels as $field => $dat) {
            if (is_array($dat['softrefs']['keys'])) {
                foreach ($dat['softrefs']['keys'] as $spKey => $elements) {
                    if (is_array($elements)) {
                        foreach ($elements as $subKey => $el) {
                            $lKey = $field . ':' . $spKey . ':' . $subKey;
                            $list[$lKey] = array_merge(['field' => $field, 'spKey' => $spKey], $el);
                            // Add file_ID key to header - slightly "risky" way of doing this because if the calculation
                            // changes for the same value in $this->records[...] this will not work anymore!
                            if ($el['subst'] && $el['subst']['relFileName']) {
                                $list[$lKey]['file_ID'] = md5(PATH_site . $el['subst']['relFileName']);
                            }
                        }
                    }
                }
            }
            if ($dat['type'] == 'flex' && is_array($dat['flexFormRels']['softrefs'])) {
                foreach ($dat['flexFormRels']['softrefs'] as $structurePath => $subSoftrefs) {
                    if (is_array($subSoftrefs['keys'])) {
                        foreach ($subSoftrefs['keys'] as $spKey => $elements) {
                            foreach ($elements as $subKey => $el) {
                                $lKey = $field . ':' . $structurePath . ':' . $spKey . ':' . $subKey;
                                $list[$lKey] = array_merge(['field' => $field, 'spKey' => $spKey, 'structurePath' => $structurePath], $el);
                                // Add file_ID key to header - slightly "risky" way of doing this because if the calculation
                                // changes for the same value in $this->records[...] this will not work anymore!
                                if ($el['subst'] && $el['subst']['relFileName']) {
                                    $list[$lKey]['file_ID'] = md5(PATH_site . $el['subst']['relFileName']);
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
     * If include fields for a specific record type are set, the data
     * are filtered out with fields are not included in the fields.
     *
     * @param string $table The record type to be filtered
     * @param array $row The data to be filtered
     * @return array The filtered record row
     */
    protected function filterRecordFields($table, array $row)
    {
        if (isset($this->recordTypesIncludeFields[$table])) {
            $includeFields = array_unique(array_merge(
                $this->recordTypesIncludeFields[$table],
                $this->defaultRecordIncludeFields
            ));
            $newRow = [];
            foreach ($row as $key => $value) {
                if (in_array($key, $includeFields)) {
                    $newRow[$key] = $value;
                }
            }
        } else {
            $newRow = $row;
        }
        return $newRow;
    }

    /**************************
     * File Output
     *************************/

    /**
     * This compiles and returns the data content for an exported file
     *
     * @param string $type Type of output; "xml" gives xml, otherwise serialized array, possibly compressed.
     * @return string The output file stream
     */
    public function compileMemoryToFileContent($type = '')
    {
        if ($type == 'xml') {
            $out = $this->createXML();
        } else {
            $compress = $this->doOutputCompress();
            $out = '';
            // adding header:
            $out .= $this->addFilePart(serialize($this->dat['header']), $compress);
            // adding records:
            $out .= $this->addFilePart(serialize($this->dat['records']), $compress);
            // adding files:
            $out .= $this->addFilePart(serialize($this->dat['files']), $compress);
            // adding files_fal:
            $out .= $this->addFilePart(serialize($this->dat['files_fal']), $compress);
        }
        return $out;
    }

    /**
     * Creates XML string from input array
     *
     * @return string XML content
     */
    public function createXML()
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
                        'softrefs' => 'softref_element'
                    ],
                    'alt_options' => [
                        '/pagetree' => [
                            'disableTypeAttrib' => true,
                            'useIndexTagForNum' => 'node',
                            'parentTagMap' => [
                                'node:subrow' => 'node'
                            ]
                        ],
                        '/pid_lookup/page_contents' => [
                            'disableTypeAttrib' => true,
                            'parentTagMap' => [
                                'page_contents' => 'table'
                            ],
                            'grandParentTagMap' => [
                                'page_contents/table' => 'item'
                            ]
                        ]
                    ]
                ],
                '/records' => [
                    'disableTypeAttrib' => true,
                    'parentTagMap' => [
                        'records' => 'tablerow',
                        'tablerow:data' => 'fieldlist',
                        'tablerow:rels' => 'related',
                        'related' => 'field',
                        'field:itemArray' => 'relations',
                        'field:newValueFiles' => 'filerefs',
                        'field:flexFormRels' => 'flexform',
                        'relations' => 'element',
                        'filerefs' => 'file',
                        'flexform:db' => 'db_relations',
                        'flexform:file' => 'file_relations',
                        'flexform:softrefs' => 'softref_relations',
                        'softref_relations' => 'structurePath',
                        'db_relations' => 'path',
                        'file_relations' => 'path',
                        'path' => 'element',
                        'keys' => 'softref_key',
                        'softref_key' => 'softref_element'
                    ],
                    'alt_options' => [
                        '/records/tablerow/fieldlist' => [
                            'useIndexTagForAssoc' => 'field'
                        ]
                    ]
                ],
                '/files' => [
                    'disableTypeAttrib' => true,
                    'parentTagMap' => [
                        'files' => 'file'
                    ]
                ],
                '/files_fal' => [
                    'disableTypeAttrib' => true,
                    'parentTagMap' => [
                        'files_fal' => 'file'
                    ]
                ]
            ]
        ];
        // Creating XML file from $outputArray:
        $charset = $this->dat['header']['charset'] ?: 'utf-8';
        $XML = '<?xml version="1.0" encoding="' . $charset . '" standalone="yes" ?>' . LF;
        $XML .= GeneralUtility::array2xml($this->dat, '', 0, 'T3RecordDocument', 0, $options);
        return $XML;
    }

    /**
     * Returns TRUE if the output should be compressed.
     *
     * @return bool TRUE if compression is possible AND requested.
     */
    public function doOutputCompress()
    {
        return $this->compress && !$this->dontCompress;
    }

    /**
     * Returns a content part for a filename being build.
     *
     * @param array $data Data to store in part
     * @param bool $compress Compress file?
     * @return string Content stream.
     */
    public function addFilePart($data, $compress = false)
    {
        if ($compress) {
            $data = gzcompress($data);
        }
        return md5($data) . ':' . ($compress ? '1' : '0') . ':' . str_pad(strlen($data), 10, '0', STR_PAD_LEFT) . ':' . $data . ':';
    }
}
