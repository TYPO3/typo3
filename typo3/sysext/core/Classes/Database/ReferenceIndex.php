<?php
namespace TYPO3\CMS\Core\Database;

/**
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reference index processing and relation extraction
 *
 * NOTICE: When the reference index is updated for an offline version the results may not be correct.
 * First, lets assumed that the reference update happens in LIVE workspace (ALWAYS update from Live workspace if you analyse whole database!)
 * Secondly, lets assume that in a Draft workspace you have changed the data structure of a parent page record - this is (in TemplaVoila) inherited by subpages.
 * When in the LIVE workspace the data structure for the records/pages in the offline workspace will not be evaluated to the right one simply because the data structure is taken from a rootline traversal and in the Live workspace that will NOT include the changed DataSTructure! Thus the evaluation will be based on the Data Structure set in the Live workspace!
 * Somehow this scenario is rarely going to happen. Yet, it is an inconsistency and I see now practical way to handle it - other than simply ignoring maintaining the index for workspace records. Or we can say that the index is precise for all Live elements while glitches might happen in an offline workspace?
 * Anyway, I just wanted to document this finding - I don't think we can find a solution for it. And its very TemplaVoila specific.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ReferenceIndex {

	/**
	 * Definition of tables to exclude from searching for relations
	 *
	 * Only tables which do not contain any relations and never did so far since references also won't be deleted for
	 * these. Since only tables with an entry in $GLOBALS['TCA] are handled by ReferenceIndex there is no need to add
	 * *_mm-tables.
	 *
	 * This is implemented as an array with fields as keys and booleans as values to be able to fast isset() instead of
	 * slow in_array() lookup.
	 *
	 * @var array
	 * @see updateRefIndexTable()
	 * @todo #65461 Create configuration for tables to exclude from ReferenceIndex
	 */
	static protected $nonRelationTables = array(
		'sys_log' => TRUE,
		'sys_history' => TRUE,
		'tx_extensionmanager_domain_model_extension' => TRUE
	);

	/**
	 * Definition of fields to exclude from searching for relations
	 *
	 * This is implemented as an array with fields as keys and booleans as values to be able to fast isset() instead of
	 * slow in_array() lookup.
	 *
	 * @var array
	 * @see getRelations()
	 * @see fetchTableRelationFields()
	 * @todo #65460 Create configuration for fields to exclude from ReferenceIndex
	 */
	static protected $nonRelationFields = array(
		'uid' => TRUE,
		'perms_userid' => TRUE,
		'perms_groupid' => TRUE,
		'perms_user' => TRUE,
		'perms_group' => TRUE,
		'perms_everybody' => TRUE,
		'pid' => TRUE
	);

	/**
	 * Fields of tables that could contain relations are cached per table. This is the prefix for the cache entries since
	 * the runtimeCache has a global scope.
	 *
	 * @var string
	 */
	static protected $cachePrefixTableRelationFields = 'core-refidx-tblRelFields-';

	/**
	 * @var array
	 * @todo Define visibility
	 */
	public $temp_flexRelations = array();

	/**
	 * @todo Define visibility
	 */
	public $errorLog = array();

	/**
	 * @todo Define visibility
	 */
	public $WSOL = FALSE;

	/**
	 * @todo Define visibility
	 */
	public $relations = array();

	/**
	 * @todo Define visibility
	 */
	public $words_strings = array();

	/**
	 * @todo Define visibility
	 */
	public $words = array();

	// Number which we can increase if a change in the code means we will have to force a re-generation of the index.
	/**
	 * @todo Define visibility
	 */
	public $hashVersion = 1;

	/**
	 * @var int
	 */
	protected $workspaceId = 0;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $runtimeCache = NULL;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->runtimeCache = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_runtime');
	}

	/**
	 * Sets the current workspace id.
	 *
	 * @param int $workspaceId
	 */
	public function setWorkspaceId($workspaceId) {
		$this->workspaceId = (int)$workspaceId;
	}

	/**
	 * Gets the current workspace id.
	 *
	 * @return int
	 */
	public function getWorkspaceId() {
		return $this->workspaceId;
	}

	/**
	 * Call this function to update the sys_refindex table for a record (even one just deleted)
	 * NOTICE: Currently, references updated for a deleted-flagged record will not include those from within flexform fields in some cases where the data structure is defined by another record since the resolving process ignores deleted records! This will also result in bad cleaning up in tcemain I think... Anyway, thats the story of flexforms; as long as the DS can change, lots of references can get lost in no time.
	 *
	 * @param string $tableName Table name
	 * @param int $uid UID of record
	 * @param bool $testOnly If set, nothing will be written to the index but the result value will still report statistics on what is added, deleted and kept. Can be used for mere analysis.
	 * @return array Array with statistics about how many index records were added, deleted and not altered plus the complete reference set for the record.
	 * @todo Define visibility
	 */
	public function updateRefIndexTable($tableName, $uid, $testOnly = FALSE) {

		// First, secure that the index table is not updated with workspace tainted relations:
		$this->WSOL = FALSE;

		// Init:
		$result = array(
			'keptNodes' => 0,
			'deletedNodes' => 0,
			'addedNodes' => 0
		);

		// If this table cannot contain relations, skip it
		if (isset(static::$nonRelationTables[$tableName])) {
			return $result;
		}

		// Fetch tableRelationFields and save them in cache if not there yet
		$cacheId = static::$cachePrefixTableRelationFields. $tableName;
		if (!$this->runtimeCache->has($cacheId)) {
			$tableRelationFields = $this->fetchTableRelationFields($tableName);
			$this->runtimeCache->set($cacheId, $tableRelationFields);
		} else {
			$tableRelationFields = $this->runtimeCache->get($cacheId);
		}

		// Get current index from Database with hash as index using $uidIndexField
		$currentRelations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tableName, 'sys_refindex')
			. ' AND recuid=' . (int)$uid . ' AND workspace=' . (int)$this->getWorkspaceId(),
			'', '', '', 'hash'
		);

		// If the table has fields which could contain relations and the record does exist (including deleted-flagged)
		if ($tableRelationFields !== '' && BackendUtility::getRecordRaw($tableName, 'uid=' . (int)$uid, 'uid')) {
			// Then, get relations:
			$relations = $this->generateRefIndexData($tableName, $uid);
			if (is_array($relations)) {
				// Traverse the generated index:
				foreach ($relations as $k => $datRec) {
					if (!is_array($relations[$k])) {
						continue;
					}
					$relations[$k]['hash'] = md5(implode('///', $relations[$k]) . '///' . $this->hashVersion);
					// First, check if already indexed and if so, unset that row (so in the end we know which rows to remove!)
					if (isset($currentRelations[$relations[$k]['hash']])) {
						unset($currentRelations[$relations[$k]['hash']]);
						$result['keptNodes']++;
						$relations[$k]['_ACTION'] = 'KEPT';
					} else {
						// If new, add it:
						if (!$testOnly) {
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_refindex', $relations[$k]);
						}
						$result['addedNodes']++;
						$relations[$k]['_ACTION'] = 'ADDED';
					}
				}
				$result['relations'] = $relations;
			} else {
				return FALSE;
			}
		}

		// If any old are left, remove them:
		if (!empty($currentRelations)) {
			$hashList = array_keys($currentRelations);
			if (!empty($hashList)) {
				$result['deletedNodes'] = count($hashList);
				$result['deletedNodes_hashList'] = implode(',', $hashList);
				if (!$testOnly) {
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_refindex', 'hash IN (' . implode(',', $GLOBALS['TYPO3_DB']->fullQuoteArray($hashList, 'sys_refindex')) . ')');
				}
			}
		}

		return $result;
	}

	/**
	 * Returns array of arrays with an index of all references found in record from table/uid
	 * If the result is used to update the sys_refindex table then ->WSOL must NOT be TRUE (no workspace overlay anywhere!)
	 *
	 * @param string $tableName Table name from $GLOBALS['TCA']
	 * @param int $uid Record UID
	 * @return array|NULL Index Rows
	 * @todo Define visibility
	 */
	public function generateRefIndexData($tableName, $uid) {

		if (!isset($GLOBALS['TCA'][$tableName])) {
			return NULL;
		}

		$this->relations = array();

		// Fetch tableRelationFields and save them in cache if not there yet
		$cacheId = static::$cachePrefixTableRelationFields . $tableName;
		if (!$this->runtimeCache->has($cacheId)) {
			$tableRelationFields = $this->fetchTableRelationFields($tableName);
			$this->runtimeCache->set($cacheId, $tableRelationFields);
		} else {
			$tableRelationFields = $this->runtimeCache->get($cacheId);
		}

		// Return if there are no fields which could contain relations
		if ($tableRelationFields === '') {
			return $this->relations;
		}

		$deleteField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'];

		if ($tableRelationFields === '*') {
			// If one field of a record is of type flex, all fields have to be fetched to be passed to BackendUtility::getFlexFormDS
			$selectFields = '*';
		} else {
			// otherwise only fields that might contain relations are fetched
			$selectFields = 'uid,' . $tableRelationFields . ($deleteField ? ',' . $deleteField : '');
		}

		// Get raw record from DB:
		$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($selectFields, $tableName, 'uid=' . (int)$uid);
		if (!is_array($record)) {
			return NULL;
		}

		// Initialize:
		$this->words_strings = array();
		$this->words = array();

		// Deleted:
		$deleted = $deleteField && $record[$deleteField] ? 1 : 0;

		// Get all relations from record:
		$recordRelations = $this->getRelations($tableName, $record);
		// Traverse those relations, compile records to insert in table:
		foreach ($recordRelations as $fieldName => $fieldRelations) {
			// Based on type,
			switch ((string)$fieldRelations['type']) {
				case 'db':
					$this->createEntryData_dbRels($tableName, $uid, $fieldName, '', $deleted, $fieldRelations['itemArray']);
					break;
				case 'file_reference':
					// not used (see getRelations()), but fallback to file
				case 'file':
					$this->createEntryData_fileRels($tableName, $uid, $fieldName, '', $deleted, $fieldRelations['newValueFiles']);
					break;
				case 'flex':
					// DB references:
					if (is_array($fieldRelations['flexFormRels']['db'])) {
						foreach ($fieldRelations['flexFormRels']['db'] as $flexPointer => $subList) {
							$this->createEntryData_dbRels($tableName, $uid, $fieldName, $flexPointer, $deleted, $subList);
						}
					}
					// File references in flexforms
					// @todo #65463 Test correct handling of file references in flexforms
					if (is_array($fieldRelations['flexFormRels']['file'])) {
						foreach ($fieldRelations['flexFormRels']['file'] as $flexPointer => $subList) {
							$this->createEntryData_fileRels($tableName, $uid, $fieldName, $flexPointer, $deleted, $subList);
						}
					}
					// Soft references in flexforms
					// @todo #65464 Test correct handling of soft references in flexforms
					if (is_array($fieldRelations['flexFormRels']['softrefs'])) {
						foreach ($fieldRelations['flexFormRels']['softrefs'] as $flexPointer => $subList) {
							$this->createEntryData_softreferences($tableName, $uid, $fieldName, $flexPointer, $deleted, $subList['keys']);
						}
					}
					break;
			}
			// Soft references in the field:
			if (is_array($fieldRelations['softrefs'])) {
				$this->createEntryData_softreferences($tableName, $uid, $fieldName, '', $deleted, $fieldRelations['softrefs']['keys']);
			}
		}

		return $this->relations;
	}

	/**
	 * Create array with field/value pairs ready to insert in database.
	 * The "hash" field is a fingerprint value across this table.
	 *
	 * @param string $table Tablename of source record (where reference is located)
	 * @param integer $uid UID of source record (where reference is located)
	 * @param string $field Fieldname of source record (where reference is located)
	 * @param string $flexpointer Pointer to location inside flexform structure where reference is located in [field]
	 * @param integer $deleted Whether record is deleted-flagged or not
	 * @param string $ref_table For database references; the tablename the reference points to. Special keyword "_FILE" indicates that "ref_string" is a file reference either absolute or relative to PATH_site. Special keyword "_STRING" indicates some special usage (typ. softreference) where "ref_string" is used for the value.
	 * @param integer $ref_uid For database references; The UID of the record (zero "ref_table" is "_FILE" or "_STRING")
	 * @param string $ref_string For "_FILE" or "_STRING" references: The filepath (relative to PATH_site or absolute) or other string.
	 * @param integer $sort The sorting order of references if many (the "group" or "select" TCA types). -1 if no sorting order is specified.
	 * @param string $softref_key If the reference is a soft reference, this is the soft reference parser key. Otherwise empty.
	 * @param string $softref_id Soft reference ID for key. Might be useful for replace operations.
	 * @return array Array record to insert into table.
	 * @todo Define visibility
	 */
	public function createEntryData($table, $uid, $field, $flexpointer, $deleted, $ref_table, $ref_uid, $ref_string = '', $sort = -1, $softref_key = '', $softref_id = '') {
		if (BackendUtility::isTableWorkspaceEnabled($table)) {
			$element = BackendUtility::getRecord($table, $uid, 't3ver_wsid');
			if ($element !== NULL && isset($element['t3ver_wsid']) && (int)$element['t3ver_wsid'] !== $this->getWorkspaceId()) {
				//The given Element is ws-enabled but doesn't live in the selected workspace
				// => don't add index as it's not actually there
				return FALSE;
			}
		}
		return array(
			'tablename' => $table,
			'recuid' => $uid,
			'field' => $field,
			'flexpointer' => $flexpointer,
			'softref_key' => $softref_key,
			'softref_id' => $softref_id,
			'sorting' => $sort,
			'deleted' => $deleted,
			'workspace' => $this->getWorkspaceId(),
			'ref_table' => $ref_table,
			'ref_uid' => $ref_uid,
			'ref_string' => $ref_string
		);
	}

	/**
	 * Enter database references to ->relations array
	 *
	 * @param string $table Tablename of source record (where reference is located)
	 * @param integer $uid UID of source record (where reference is located)
	 * @param string $fieldname Fieldname of source record (where reference is located)
	 * @param string $flexpointer Pointer to location inside flexform structure where reference is located in [field]
	 * @param integer $deleted Whether record is deleted-flagged or not
	 * @param array $items Data array with databaes relations (table/id)
	 * @return void
	 * @todo Define visibility
	 */
	public function createEntryData_dbRels($table, $uid, $fieldname, $flexpointer, $deleted, $items) {
		foreach ($items as $sort => $i) {
			$this->relations[] = $this->createEntryData($table, $uid, $fieldname, $flexpointer, $deleted, $i['table'], $i['id'], '', $sort);
		}
	}

	/**
	 * Enter file references to ->relations array
	 *
	 * @param string $table Tablename of source record (where reference is located)
	 * @param integer $uid UID of source record (where reference is located)
	 * @param string $fieldname Fieldname of source record (where reference is located)
	 * @param string $flexpointer Pointer to location inside flexform structure where reference is located in [field]
	 * @param integer $deleted Whether record is deleted-flagged or not
	 * @param array $items Data array with file relations
	 * @return 	void
	 * @todo Define visibility
	 */
	public function createEntryData_fileRels($table, $uid, $fieldname, $flexpointer, $deleted, $items) {
		foreach ($items as $sort => $i) {
			$filePath = $i['ID_absFile'];
			if (GeneralUtility::isFirstPartOfStr($filePath, PATH_site)) {
				$filePath = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($filePath);
			}
			$this->relations[] = $this->createEntryData($table, $uid, $fieldname, $flexpointer, $deleted, '_FILE', 0, $filePath, $sort);
		}
	}

	/**
	 * Enter softref references to ->relations array
	 *
	 * @param string $table Tablename of source record (where reference is located)
	 * @param integer $uid UID of source record (where reference is located)
	 * @param string $fieldname Fieldname of source record (where reference is located)
	 * @param string $flexpointer Pointer to location inside flexform struc
	 * @param integer $deleted
	 * @param array $keys Data array with soft reference keys
	 * @return void
	 * @todo Define visibility
	 */
	public function createEntryData_softreferences($table, $uid, $fieldname, $flexpointer, $deleted, $keys) {
		if (is_array($keys)) {
			foreach ($keys as $spKey => $elements) {
				if (is_array($elements)) {
					foreach ($elements as $subKey => $el) {
						if (is_array($el['subst'])) {
							switch ((string) $el['subst']['type']) {
								case 'db':
									list($tableName, $recordId) = explode(':', $el['subst']['recordRef']);
									$this->relations[] = $this->createEntryData($table, $uid, $fieldname, $flexpointer, $deleted, $tableName, $recordId, '', -1, $spKey, $subKey);
									break;
								case 'file_reference':
									// not used (see getRelations()), but fallback to file
								case 'file':
									$this->relations[] = $this->createEntryData($table, $uid, $fieldname, $flexpointer, $deleted, '_FILE', 0, $el['subst']['relFileName'], -1, $spKey, $subKey);
									break;
								case 'string':
									$this->relations[] = $this->createEntryData($table, $uid, $fieldname, $flexpointer, $deleted, '_STRING', 0, $el['subst']['tokenValue'], -1, $spKey, $subKey);
									break;
							}
						}
					}
				}
			}
		}
	}

	/*******************************
	 *
	 * Get relations from table row
	 *
	 *******************************/
	/**
	 * Returns relation information for a $table/$row-array
	 * Traverses all fields in input row which are configured in TCA/columns
	 * It looks for hard relations to files and records in the TCA types "select" and "group"
	 *
	 * @param string $table Table name
	 * @param array $row Row from table
	 * @param string $onlyField Specific field to fetch for.
	 * @return array Array with information about relations
	 * @see export_addRecord()
	 * @todo Define visibility
	 */
	public function getRelations($table, $row, $onlyField = '') {
		// Initialize:
		$uid = $row['uid'];
		$outRow = array();
		foreach ($row as $field => $value) {
			if (!isset(static::$nonRelationFields[$field]) && is_array($GLOBALS['TCA'][$table]['columns'][$field]) && (!$onlyField || $onlyField === $field)) {
				$conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
				// Add files
				$resultsFromFiles = $this->getRelations_procFiles($value, $conf, $uid);
				if (!empty($resultsFromFiles)) {
					// We have to fill different arrays here depending on the result.
					// internal_type file is still a relation of type file and
					// since http://forge.typo3.org/issues/49538 internal_type file_reference
					// is a database relation to a sys_file record
					$fileResultsFromFiles = array();
					$dbResultsFromFiles = array();
					foreach ($resultsFromFiles as $resultFromFiles) {
						if (isset($resultFromFiles['table']) && $resultFromFiles['table'] === 'sys_file') {
							$dbResultsFromFiles[] = $resultFromFiles;
						} else {
							// Creates an entry for the field with all the files:
							$fileResultsFromFiles[] = $resultFromFiles;
						}
					}
					if (!empty($fileResultsFromFiles)) {
						$outRow[$field] = array(
							'type' => 'file',
							'newValueFiles' => $fileResultsFromFiles
						);
					}
					if (!empty($dbResultsFromFiles)) {
						$outRow[$field] = array(
							'type' => 'db',
							'itemArray' => $dbResultsFromFiles
						);
					}
				}
				// Add a softref definition for link fields if the TCA does not specify one already
				if ($conf['type'] === 'input' && isset($conf['wizards']['link']) && empty($conf['softref'])) {
					$conf['softref'] = 'typolink';
				}
				// Add DB:
				$resultsFromDatabase = $this->getRelations_procDB($value, $conf, $uid, $table, $field);
				if (!empty($resultsFromDatabase)) {
					// Create an entry for the field with all DB relations:
					$outRow[$field] = array(
						'type' => 'db',
						'itemArray' => $resultsFromDatabase
					);
				}
				// For "flex" fieldtypes we need to traverse the structure looking for file and db references of course!
				if ($conf['type'] == 'flex') {
					// Get current value array:
					// NOTICE: failure to resolve Data Structures can lead to integrity problems with the reference index. Please look up the note in the JavaDoc documentation for the function \TYPO3\CMS\Backend\Utility\BackendUtility::getFlexFormDS()
					$currentValueArray = GeneralUtility::xml2array($value);
					// Traversing the XML structure, processing files:
					if (is_array($currentValueArray)) {
						$this->temp_flexRelations = array(
							'db' => array(),
							'file' => array(),
							'softrefs' => array()
						);
						// Create and call iterator object:
						$flexObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
						$flexObj->traverseFlexFormXMLData($table, $field, $row, $this, 'getRelations_flexFormCallBack');
						// Create an entry for the field:
						$outRow[$field] = array(
							'type' => 'flex',
							'flexFormRels' => $this->temp_flexRelations
						);
					}
				}
				// Soft References:
				if ((string)$value !== '') {
					$softRefValue = $value;
					$softRefs = BackendUtility::explodeSoftRefParserList($conf['softref']);
					if ($softRefs !== FALSE) {
						foreach ($softRefs as $spKey => $spParams) {
							$softRefObj = BackendUtility::softRefParserObj($spKey);
							if (is_object($softRefObj)) {
								$resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams);
								if (is_array($resultArray)) {
									$outRow[$field]['softrefs']['keys'][$spKey] = $resultArray['elements'];
									if ((string)$resultArray['content'] !== '') {
										$softRefValue = $resultArray['content'];
									}
								}
							}
						}
					}
					if (!empty($outRow[$field]['softrefs']) && (string)$value !== (string)$softRefValue && strpos($softRefValue, '{softref:') !== FALSE) {
						$outRow[$field]['softrefs']['tokenizedContent'] = $softRefValue;
					}
				}
			}
		}
		return $outRow;
	}

	/**
	 * Callback function for traversing the FlexForm structure in relation to finding file and DB references!
	 *
	 * @param array $dsArr Data structure for the current value
	 * @param mixed $dataValue Current value
	 * @param array $PA Additional configuration used in calling function
	 * @param string $structurePath Path of value in DS structure
	 * @param object $pObj Object reference to caller
	 * @return void
	 * @see \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_flex_procInData_travDS()
	 * @todo Define visibility
	 */
	public function getRelations_flexFormCallBack($dsArr, $dataValue, $PA, $structurePath, $pObj) {
		// Removing "data/" in the beginning of path (which points to location in data array)
		$structurePath = substr($structurePath, 5) . '/';
		$dsConf = $dsArr['TCEforms']['config'];
		// Implode parameter values:
		list($table, $uid, $field) = array(
			$PA['table'],
			$PA['uid'],
			$PA['field']
		);
		// Add files
		$resultsFromFiles = $this->getRelations_procFiles($dataValue, $dsConf, $uid);
		if (!empty($resultsFromFiles)) {
			// We have to fill different arrays here depending on the result.
			// internal_type file is still a relation of type file and
			// since http://forge.typo3.org/issues/49538 internal_type file_reference
			// is a database relation to a sys_file record
			$fileResultsFromFiles = array();
			$dbResultsFromFiles = array();
			foreach ($resultsFromFiles as $resultFromFiles) {
				if (isset($resultFromFiles['table']) && $resultFromFiles['table'] === 'sys_file') {
					$dbResultsFromFiles[] = $resultFromFiles;
				} else {
					$fileResultsFromFiles[] = $resultFromFiles;
				}
			}
			if (!empty($fileResultsFromFiles)) {
				$this->temp_flexRelations['file'][$structurePath] = $fileResultsFromFiles;
			}
			if (!empty($dbResultsFromFiles)) {
				$this->temp_flexRelations['db'][$structurePath] = $dbResultsFromFiles;
			}
		}
		// Add a softref definition for link fields if the TCA does not specify one already
		if ($dsConf['type'] === 'input' && isset($dsConf['wizards']['link']) && empty($dsConf['softref'])) {
			$dsConf['softref'] = 'typolink';
		}
		// Add DB:
		$resultsFromDatabase = $this->getRelations_procDB($dataValue, $dsConf, $uid, $table, $field);
		if (!empty($resultsFromDatabase)) {
			// Create an entry for the field with all DB relations:
			$this->temp_flexRelations['db'][$structurePath] = $resultsFromDatabase;
		}
		// Soft References:
		if (is_array($dataValue) || (string)$dataValue !== '') {
			$softRefValue = $dataValue;
			$softRefs = BackendUtility::explodeSoftRefParserList($dsConf['softref']);
			if ($softRefs !== FALSE) {
				foreach ($softRefs as $spKey => $spParams) {
					$softRefObj = BackendUtility::softRefParserObj($spKey);
					if (is_object($softRefObj)) {
						$resultArray = $softRefObj->findRef($table, $field, $uid, $softRefValue, $spKey, $spParams, $structurePath);
						if (is_array($resultArray) && is_array($resultArray['elements'])) {
							$this->temp_flexRelations['softrefs'][$structurePath]['keys'][$spKey] = $resultArray['elements'];
							if ((string)$resultArray['content'] !== '') {
								$softRefValue = $resultArray['content'];
							}
						}
					}
				}
			}
			if (!empty($this->temp_flexRelations['softrefs']) && (string)$dataValue !== (string)$softRefValue) {
				$this->temp_flexRelations['softrefs'][$structurePath]['tokenizedContent'] = $softRefValue;
			}
		}
	}

	/**
	 * Check field configuration if it is a file relation field and extract file relations if any
	 *
	 * @param string $value Field value
	 * @param array $conf Field configuration array of type "TCA/columns
	 * @param integer $uid Field uid
	 * @return bool|array If field type is OK it will return an array with the files inside. Else FALSE
	 * @todo Define visibility
	 */
	public function getRelations_procFiles($value, $conf, $uid) {
		if ($conf['type'] !== 'group' || ($conf['internal_type'] !== 'file' && $conf['internal_type'] !== 'file_reference')) {
			return FALSE;
		}

		// Collect file values in array:
		if ($conf['MM']) {
			$theFileValues = array();
			$dbAnalysis = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
			$dbAnalysis->start('', 'files', $conf['MM'], $uid);
			foreach ($dbAnalysis->itemArray as $someval) {
				if ($someval['id']) {
					$theFileValues[] = $someval['id'];
				}
			}
		} else {
			$theFileValues = explode(',', $value);
		}
		// Traverse the files and add them:
		$uploadFolder = $conf['internal_type'] == 'file' ? $conf['uploadfolder'] : '';
		$dest = $this->destPathFromUploadFolder($uploadFolder);
		$newValueFiles = array();
		foreach ($theFileValues as $file) {
			if (trim($file)) {
				$realFile = $dest . '/' . trim($file);
				$newValueFile = array(
					'filename' => basename($file),
					'ID' => md5($realFile),
					'ID_absFile' => $realFile
				);
				// Set sys_file and id for referenced files
				if ($conf['internal_type'] === 'file_reference') {
					try {
						$file = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($file);
						if ($file instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
							// For setting this as sys_file relation later, the keys filename, ID and ID_absFile
							// have not to be included, because the are not evaluated for db relations.
							$newValueFile = array(
								'table' => 'sys_file',
								'id' => $file->getUid()
							);
						}
					} catch (\Exception $e) {

					}
				}
				$newValueFiles[] = $newValueFile;
			}
		}
		return $newValueFiles;
	}

	/**
	 * Check field configuration if it is a DB relation field and extract DB relations if any
	 *
	 * @param string $value Field value
	 * @param array $conf Field configuration array of type "TCA/columns
	 * @param integer $uid Field uid
	 * @param string $table Table name
	 * @param string $field Field name
	 * @return array If field type is OK it will return an array with the database relations. Else FALSE
	 * @todo Define visibility
	 */
	public function getRelations_procDB($value, $conf, $uid, $table = '', $field = '') {
		// Get IRRE relations
		if (empty($conf)) {
			return FALSE;
		} elseif ($conf['type'] === 'inline' && !empty($conf['foreign_table']) && empty($conf['MM'])) {
			$dbAnalysis = $this->getRelationHandler();
			$dbAnalysis->setUseLiveReferenceIds(FALSE);
			$dbAnalysis->start($value, $conf['foreign_table'], '', $uid, $table, $conf);
			return $dbAnalysis->itemArray;
		// DB record lists:
		} elseif ($this->isDbReferenceField($conf)) {
			$allowedTables = $conf['type'] == 'group' ? $conf['allowed'] : $conf['foreign_table'] . ',' . $conf['neg_foreign_table'];
			if ($conf['MM_opposite_field']) {
				return array();
			}
			$dbAnalysis = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
			$dbAnalysis->start($value, $allowedTables, $conf['MM'], $uid, $table, $conf);
			return $dbAnalysis->itemArray;
		} elseif ($conf['type'] == 'inline' && $conf['foreign_table'] == 'sys_file_reference') {
			// @todo It looks like this was never called before since isDbReferenceField also checks for type 'inline' and any 'foreign_table'
			$files = (array)$GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid_local', 'sys_file_reference', ('tablenames=\'' . $table . '\' AND fieldname=\'' . $field . '\' AND uid_foreign=' . $uid . ' AND deleted=0'));
			$fileArray = array();
			foreach ($files as $fileUid) {
				$fileArray[] = array(
					'table' => 'sys_file',
					'id' => $fileUid['uid_local']
				);
			}
			return $fileArray;
		}
	}

	/*******************************
	 *
	 * Setting values
	 *
	 *******************************/
	/**
	 * Setting the value of a reference or removing it completely.
	 * Usage: For lowlevel clean up operations!
	 * WARNING: With this you can set values that are not allowed in the database since it will bypass all checks for validity! Hence it is targetted at clean-up operations. Please use TCEmain in the usual ways if you wish to manipulate references.
	 * Since this interface allows updates to soft reference values (which TCEmain does not directly) you may like to use it for that as an exception to the warning above.
	 * Notice; If you want to remove multiple references from the same field, you MUST start with the one having the highest sorting number. If you don't the removal of a reference with a lower number will recreate an index in which the remaining references in that field has new hash-keys due to new sorting numbers - and you will get errors for the remaining operations which cannot find the hash you feed it!
	 * To ensure proper working only admin-BE_USERS in live workspace should use this function
	 *
	 * @param string $hash 32-byte hash string identifying the record from sys_refindex which you wish to change the value for
	 * @param mixed $newValue Value you wish to set for reference. If NULL, the reference is removed (unless a soft-reference in which case it can only be set to a blank string). If you wish to set a database reference, use the format "[table]:[uid]". Any other case, the input value is set as-is
	 * @param boolean $returnDataArray Return $dataArray only, do not submit it to database.
	 * @param boolean $bypassWorkspaceAdminCheck If set, it will bypass check for workspace-zero and admin user
	 * @return string If a return string, that carries an error message, otherwise FALSE (=OK) (except if $returnDataArray is set!)
	 * @todo Define visibility
	 */
	public function setReferenceValue($hash, $newValue, $returnDataArray = FALSE, $bypassWorkspaceAdminCheck = FALSE) {
		if ($GLOBALS['BE_USER']->workspace === 0 && $GLOBALS['BE_USER']->isAdmin() || $bypassWorkspaceAdminCheck) {
			// Get current index from Database:
			$refRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_refindex', 'hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'sys_refindex'));
			// Check if reference existed.
			if (is_array($refRec)) {
				if ($GLOBALS['TCA'][$refRec['tablename']]) {
					// Get that record from database:
					$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $refRec['tablename'], 'uid=' . (int)$refRec['recuid']);
					if (is_array($record)) {
						// Get all relations from record, filter with fieldname:
						$dbrels = $this->getRelations($refRec['tablename'], $record, $refRec['field']);
						if ($dat = $dbrels[$refRec['field']]) {
							// Initialize data array that is to be sent to TCEmain afterwards:
							$dataArray = array();
							// Based on type,
							switch ((string) $dat['type']) {
								case 'db':
									$error = $this->setReferenceValue_dbRels($refRec, $dat['itemArray'], $newValue, $dataArray);
									if ($error) {
										return $error;
									}
									break;
								case 'file_reference':
									// not used (see getRelations()), but fallback to file
								case 'file':
									$error = $this->setReferenceValue_fileRels($refRec, $dat['newValueFiles'], $newValue, $dataArray);
									if ($error) {
										return $error;
									}
									break;
								case 'flex':
									// DB references:
									if (is_array($dat['flexFormRels']['db'][$refRec['flexpointer']])) {
										$error = $this->setReferenceValue_dbRels($refRec, $dat['flexFormRels']['db'][$refRec['flexpointer']], $newValue, $dataArray, $refRec['flexpointer']);
										if ($error) {
											return $error;
										}
									}
									// File references
									if (is_array($dat['flexFormRels']['file'][$refRec['flexpointer']])) {
										$this->setReferenceValue_fileRels($refRec, $dat['flexFormRels']['file'][$refRec['flexpointer']], $newValue, $dataArray, $refRec['flexpointer']);
										if ($error) {
											return $error;
										}
									}
									// Soft references in flexforms
									if ($refRec['softref_key'] && is_array($dat['flexFormRels']['softrefs'][$refRec['flexpointer']]['keys'][$refRec['softref_key']])) {
										$error = $this->setReferenceValue_softreferences($refRec, $dat['flexFormRels']['softrefs'][$refRec['flexpointer']], $newValue, $dataArray, $refRec['flexpointer']);
										if ($error) {
											return $error;
										}
									}
									break;
							}
							// Softreferences in the field:
							if ($refRec['softref_key'] && is_array($dat['softrefs']['keys'][$refRec['softref_key']])) {
								$error = $this->setReferenceValue_softreferences($refRec, $dat['softrefs'], $newValue, $dataArray);
								if ($error) {
									return $error;
								}
							}
							// Data Array, now ready to sent to TCEmain
							if ($returnDataArray) {
								return $dataArray;
							} else {
								// Execute CMD array:
								$tce = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
								$tce->stripslashes_values = FALSE;
								$tce->dontProcessTransformations = TRUE;
								$tce->bypassWorkspaceRestrictions = TRUE;
								$tce->bypassFileHandling = TRUE;
								// Otherwise this cannot update things in deleted records...
								$tce->bypassAccessCheckForRecords = TRUE;
								// Check has been done previously that there is a backend user which is Admin and also in live workspace
								$tce->start($dataArray, array());
								$tce->process_datamap();
								// Return errors if any:
								if (count($tce->errorLog)) {
									return LF . 'TCEmain:' . implode((LF . 'TCEmain:'), $tce->errorLog);
								}
							}
						}
					}
				} else {
					return 'ERROR: Tablename "' . $refRec['tablename'] . '" was not in TCA!';
				}
			} else {
				return 'ERROR: No reference record with hash="' . $hash . '" was found!';
			}
		} else {
			return 'ERROR: BE_USER object is not admin OR not in workspace 0 (Live)';
		}
	}

	/**
	 * Setting a value for a reference for a DB field:
	 *
	 * @param array $refRec sys_refindex record
	 * @param array $itemArray Array of references from that field
	 * @param string $newValue Value to substitute current value with (or NULL to unset it)
	 * @param array $dataArray Data array in which the new value is set (passed by reference)
	 * @param string $flexpointer Flexform pointer, if in a flex form field.
	 * @return string Error message if any, otherwise FALSE = OK
	 * @todo Define visibility
	 */
	public function setReferenceValue_dbRels($refRec, $itemArray, $newValue, &$dataArray, $flexpointer = '') {
		if ((int)$itemArray[$refRec['sorting']]['id'] === (int)$refRec['ref_uid'] && (string)$itemArray[$refRec['sorting']]['table'] === (string)$refRec['ref_table']) {
			// Setting or removing value:
			// Remove value:
			if ($newValue === NULL) {
				unset($itemArray[$refRec['sorting']]);
			} else {
				list($itemArray[$refRec['sorting']]['table'], $itemArray[$refRec['sorting']]['id']) = explode(':', $newValue);
			}
			// Traverse and compile new list of records:
			$saveValue = array();
			foreach ($itemArray as $pair) {
				$saveValue[] = $pair['table'] . '_' . $pair['id'];
			}
			// Set in data array:
			if ($flexpointer) {
				$flexToolObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = array();
				$flexToolObj->setArrayValueByPath(substr($flexpointer, 0, -1), $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'], implode(',', $saveValue));
			} else {
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',', $saveValue);
			}
		} else {
			return 'ERROR: table:id pair "' . $refRec['ref_table'] . ':' . $refRec['ref_uid'] . '" did not match that of the record ("' . $itemArray[$refRec['sorting']]['table'] . ':' . $itemArray[$refRec['sorting']]['id'] . '") in sorting index "' . $refRec['sorting'] . '"';
		}
	}

	/**
	 * Setting a value for a reference for a FILE field:
	 *
	 * @param array $refRec sys_refindex record
	 * @param array $itemArray Array of references from that field
	 * @param string $newValue Value to substitute current value with (or NULL to unset it)
	 * @param array $dataArray Data array in which the new value is set (passed by reference)
	 * @param string $flexpointer Flexform pointer, if in a flex form field.
	 * @return string Error message if any, otherwise FALSE = OK
	 * @todo Define visibility
	 */
	public function setReferenceValue_fileRels($refRec, $itemArray, $newValue, &$dataArray, $flexpointer = '') {
		$ID_absFile = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($itemArray[$refRec['sorting']]['ID_absFile']);
		if ($ID_absFile === (string)$refRec['ref_string'] && $refRec['ref_table'] === '_FILE') {
			// Setting or removing value:
			// Remove value:
			if ($newValue === NULL) {
				unset($itemArray[$refRec['sorting']]);
			} else {
				$itemArray[$refRec['sorting']]['filename'] = $newValue;
			}
			// Traverse and compile new list of records:
			$saveValue = array();
			foreach ($itemArray as $fileInfo) {
				$saveValue[] = $fileInfo['filename'];
			}
			// Set in data array:
			if ($flexpointer) {
				$flexToolObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = array();
				$flexToolObj->setArrayValueByPath(substr($flexpointer, 0, -1), $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'], implode(',', $saveValue));
			} else {
				$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',', $saveValue);
			}
		} else {
			return 'ERROR: either "' . $refRec['ref_table'] . '" was not "_FILE" or file PATH_site+"' . $refRec['ref_string'] . '" did not match that of the record ("' . $itemArray[$refRec['sorting']]['ID_absFile'] . '") in sorting index "' . $refRec['sorting'] . '"';
		}
	}

	/**
	 * Setting a value for a soft reference token
	 *
	 * @param array $refRec sys_refindex record
	 * @param array $softref Array of soft reference occurencies
	 * @param string $newValue Value to substitute current value with
	 * @param array $dataArray Data array in which the new value is set (passed by reference)
	 * @param string $flexpointer Flexform pointer, if in a flex form field.
	 * @return string Error message if any, otherwise FALSE = OK
	 * @todo Define visibility
	 */
	public function setReferenceValue_softreferences($refRec, $softref, $newValue, &$dataArray, $flexpointer = '') {
		if (is_array($softref['keys'][$refRec['softref_key']][$refRec['softref_id']])) {
			// Set new value:
			$softref['keys'][$refRec['softref_key']][$refRec['softref_id']]['subst']['tokenValue'] = '' . $newValue;
			// Traverse softreferences and replace in tokenized content to rebuild it with new value inside:
			foreach ($softref['keys'] as $sfIndexes) {
				foreach ($sfIndexes as $data) {
					$softref['tokenizedContent'] = str_replace('{softref:' . $data['subst']['tokenID'] . '}', $data['subst']['tokenValue'], $softref['tokenizedContent']);
				}
			}
			// Set in data array:
			if (!strstr($softref['tokenizedContent'], '{softref:')) {
				if ($flexpointer) {
					$flexToolObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
					$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = array();
					$flexToolObj->setArrayValueByPath(substr($flexpointer, 0, -1), $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'], $softref['tokenizedContent']);
				} else {
					$dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = $softref['tokenizedContent'];
				}
			} else {
				return 'ERROR: After substituting all found soft references there were still soft reference tokens in the text. (theoretically this does not have to be an error if the string "{softref:" happens to be in the field for another reason.)';
			}
		} else {
			return 'ERROR: Soft reference parser key "' . $refRec['softref_key'] . '" or the index "' . $refRec['softref_id'] . '" was not found.';
		}
	}

	/*******************************
	 *
	 * Helper functions
	 *
	 *******************************/

	/**
	 * Returns TRUE if the TCA/columns field type is a DB reference field
	 *
	 * @param array $configuration Config array for TCA/columns field
	 * @return bool TRUE if DB reference field (group/db or select with foreign-table)
	 */
	protected function isDbReferenceField(array $configuration) {
		return (
			($configuration['type'] === 'group' && $configuration['internal_type'] === 'db')
			|| (
				($configuration['type'] === 'select' || $configuration['type'] === 'inline')
				&& !empty($configuration['foreign_table'])
			)
		);
	}

	/**
	 * Returns TRUE if the TCA/columns field type is a reference field
	 *
	 * @param array $configuration Config array for TCA/columns field
	 * @return bool TRUE if reference field
	 * @todo Define visibility
	 */
	public function isReferenceField(array $configuration) {
		return (
			$this->isDbReferenceField($configuration)
			||
			($configuration['type'] === 'group' && ($configuration['internal_type'] === 'file' || $configuration['internal_type'] === 'file_reference')) // getRelations_procFiles
			||
			($configuration['type'] === 'input' && isset($configuration['wizards']['link'])) // getRelations_procDB
			||
			$configuration['type'] === 'flex'
			||
			isset($configuration['softref'])
			||
			(
				// @deprecated global soft reference parsers are deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8
				is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL'])
				&& !empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser_GL'])
			)
		);
	}

	/**
	 * Returns all fields of a table which could contain a relation
	 *
	 * @param string $tableName Name of the table
	 * @return string Fields which could contain a relation
	 */
	protected function fetchTableRelationFields($tableName) {
		$fields = array();

		foreach ($GLOBALS['TCA'][$tableName]['columns'] as $field => $fieldDefinition) {
			if (is_array($fieldDefinition['config'])) {
				// Check for flex field
				if (isset($fieldDefinition['config']['type']) && $fieldDefinition['config']['type'] === 'flex') {
					// Fetch all fields if the is a field of type flex in the table definition because the complete row is passed to
					// BackendUtility::getFlexFormDS in the end and might be needed in ds_pointerField or $hookObj->getFlexFormDS_postProcessDS
					return '*';
				}
				// Only fetch this field if it can contain a reference
				if ($this->isReferenceField($fieldDefinition['config'])) {
					$fields[] = $field;
				}
			}
		}

		return implode(',', $fields);
	}

	/**
	 * Returns destination path to an upload folder given by $folder
	 *
	 * @param string $folder Folder relative to PATH_site
	 * @return string Input folder prefixed with PATH_site. No checking for existence is done. Output must be a folder without trailing slash.
	 * @todo Define visibility
	 */
	public function destPathFromUploadFolder($folder) {
		if (!$folder) {
			return substr(PATH_site, 0, -1);
		}
		return PATH_site . $folder;
	}

	/**
	 * Sets error message in the internal error log
	 *
	 * @param string $msg Error message
	 * @return void
	 * @todo Define visibility
	 */
	public function error($msg) {
		$this->errorLog[] = $msg;
	}

	/**
	 * Updating Index (External API)
	 *
	 * @param boolean $testOnly If set, only a test
	 * @param boolean $cli_echo If set, output CLI status
	 * @return array Header and body status content
	 */
	public function updateIndex($testOnly, $cli_echo = FALSE) {
		$errors = array();
		$tableNames = array();
		$recCount = 0;
		$tableCount = 0;
		$headerContent = $testOnly ? 'Reference Index being TESTED (nothing written, use "--refindex update" to update)' : 'Reference Index being Updated';
		if ($cli_echo) {
			echo '*******************************************' . LF . $headerContent . LF . '*******************************************' . LF;
		}
		// Traverse all tables:
		foreach ($GLOBALS['TCA'] as $tableName => $cfg) {
			if (isset(static::$nonRelationTables[$tableName])) {
				continue;
			}
			// Traverse all records in tables, including deleted records:
			$fieldNames = (BackendUtility::isTableWorkspaceEnabled($tableName) ? 'uid,t3ver_wsid' : 'uid');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldNames, $tableName, '1=1');
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				// Table exists in $TCA but does not exist in the database
				GeneralUtility::sysLog(sprintf('Table "%s" exists in $TCA but does not exist in the database. You should run the Database Analyzer in the Install Tool to fix this.', $tableName), 'core', GeneralUtility::SYSLOG_SEVERITY_ERROR);
				continue;
			}
			$tableNames[] = $tableName;
			$tableCount++;
			$uidList = array(0);
			while ($recdat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				/** @var $refIndexObj ReferenceIndex */
				$refIndexObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\ReferenceIndex');
				if (isset($recdat['t3ver_wsid'])) {
					$refIndexObj->setWorkspaceId($recdat['t3ver_wsid']);
				}
				$result = $refIndexObj->updateRefIndexTable($tableName, $recdat['uid'], $testOnly);
				$uidList[] = $recdat['uid'];
				$recCount++;
				if ($result['addedNodes'] || $result['deletedNodes']) {
					$Err = 'Record ' . $tableName . ':' . $recdat['uid'] . ' had ' . $result['addedNodes'] . ' added indexes and ' . $result['deletedNodes'] . ' deleted indexes';
					$errors[] = $Err;
					if ($cli_echo) {
						echo $Err . LF;
					}
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			// Searching lost indexes for this table:
			$where = 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tableName, 'sys_refindex') . ' AND recuid NOT IN (' . implode(',', $uidList) . ')';
			$lostIndexes = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('hash', 'sys_refindex', $where);
			if (count($lostIndexes)) {
				$Err = 'Table ' . $tableName . ' has ' . count($lostIndexes) . ' lost indexes which are now deleted';
				$errors[] = $Err;
				if ($cli_echo) {
					echo $Err . LF;
				}
				if (!$testOnly) {
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_refindex', $where);
				}
			}
		}
		// Searching lost indexes for non-existing tables:
		$where = 'tablename NOT IN (' . implode(',', $GLOBALS['TYPO3_DB']->fullQuoteArray($tableNames, 'sys_refindex')) . ')';
		$lostTables = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('hash', 'sys_refindex', $where);
		if (count($lostTables)) {
			$Err = 'Index table hosted ' . count($lostTables) . ' indexes for non-existing tables, now removed';
			$errors[] = $Err;
			if ($cli_echo) {
				echo $Err . LF;
			}
			if (!$testOnly) {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_refindex', $where);
			}
		}
		$testedHowMuch = $recCount . ' records from ' . $tableCount . ' tables were checked/updated.' . LF;
		$bodyContent = $testedHowMuch . (count($errors) ? implode(LF, $errors) : 'Index Integrity was perfect!');
		if ($cli_echo) {
			echo $testedHowMuch . (count($errors) ? 'Updates: ' . count($errors) : 'Index Integrity was perfect!') . LF;
		}
		if (!$testOnly) {
			$registry = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
			$registry->set('core', 'sys_refindex_lastUpdate', $GLOBALS['EXEC_TIME']);
		}
		return array($headerContent, $bodyContent, count($errors));
	}

	/**
	 * @return RelationHandler
	 */
	protected function getRelationHandler() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');
	}

}
