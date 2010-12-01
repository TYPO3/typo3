<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * File Abtraction Layer Migration controller
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id: $
 */
class tx_fal_MigrationController {

	/**
	 * Iterator to use
	 *
	 * @var	tx_fal_DatabaseFieldnameIterator
	 */
	protected $fieldnameIterator = null;

	/**
	 * Limit for iterations
	 *
	 * @var integer
	 */
	protected $limit = 500;

	/**
	 * Iteration count on how many references were checked
	 *
	 * @var	integer
	 */
	protected $fileReferencesIteration = 0;

	/**
	 * Mount to use as base for the index
	 *
	 * @var	tx_fal_Mount
	 */
	protected $mount = null;

	/**
	 * Contructor of the controller
	 *
	 * @return	void
	 */
	public function __construct() {
		$this->mount = tx_fal_Mount::getInstanceForUid(0);
	}

	/**
	 * Setter for fieldname iterator
	 *
	 * @param	array/tx_fal_DatabaseFieldnameIterator	$fieldnameIterator	DESCRIPTION
	 * @return	tx_fal_MigrationController									DESCRIPTION
	 */
	public function setFieldnameIterator($fieldnameIterator) {
		$this->fieldnameIterator = $fieldnameIterator;

		return $this;
	}

	/**
	 * Setter for record iterator
	 *
	 * @param	array/tx_fal_RecordIterator		$recordIterator		DESCRIPTION
	 * @return	tx_fal_MigrationController							DESCRIPTION
	 */
	public function setRecordIterator($recordIterator) {
		$this->recordIterator = $recordIterator;

		return $this;
	}

	/**
	 * Setter for limit
	 *
	 * @param	integer		$limit				DESCRIPTION
	 * @return	tx_fal_MigrationController		DESCRIPTION
	 */
	public function setLimit($limit) {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Execution of the migration
	 *
	 * @return	void
	 */
	public function execute() {
			// fetch combination of tablename and fieldname
		foreach ($this->fieldnameIterator as $tableName => $fieldName) {
			$sourcePath = $this->getSourcePath($tableName, $fieldName);
				// fetch records for tablename and fieldname and loop over them
			$this->recordIterator->fetchRecordsForTableAndField($tableName, $fieldName);
			foreach ($this->recordIterator as $recordUid => $filenameList) {

					// explode filenameList and check each name
				$fileNames = t3lib_div::trimExplode(',', $filenameList);
				foreach ($fileNames as $filenamePosition => $fileName) {
					$assetUid = 0;
					$duplicateRecord = $this->fetchDuplicateRecord($fileName, $sourcePath, $tableName, $fieldName);

					if ($duplicateRecord == FALSE) {
						$destinationPath = $this->getDestinationPath($tableName, $fieldName, $recordUid);
						if (is_file($sourcePath . $fileName)) {

							@copy($sourcePath . $fileName, $destinationPath . $fileName);

							t3lib_div::fixPermissions($destinationPath . $fileName);
							$newfile = tx_fal_Indexer::addFileToIndex($this->mount, $destinationPath . $fileName);

							$assetUid = $newfile;
							$this->fileMoved++;
						} else {
							t3lib_div::devLog('copy file ', __CLASS__, 1, array('FILE NOT FOUND'));
						}
					} else {
						$assetUid = $duplicateRecord['uid'];
						$this->doublicatedFound++;
					}

					if ($assetUid) {
						$sorting = $filenamePosition + 1;
						#t3lib_div::devLog('create reference ', __CLASS__, 1, array($tableName, $fieldName, $recordUid, $assetUid, $sorting));
						$this->createReference($tableName, $fieldName, $recordUid, $assetUid, $sorting);
					}

					$this->fileReferencesIteration++;
					if ($this->fileReferencesIteration >= $this->limit) {
						break 3;
					}
				}

					// after migrating ever file for this field cound the references and write them back
				$referenceCount = $this->fetchReferenceCount($tableName, $fieldName, $recordUid);
				$this->updateReferenceCount($tableName, $fieldName, $recordUid, $referenceCount);
			}
		}
	}

	/**
	 * Render the destination path
	 *
	 * @param	string	$tableName		DESCRIPTION
	 * @param	string	$fieldName		DESCRIPTION
	 * @param	string	$recordUid		DESCRIPTION
	 * @return	string					DESCRIPTION
	 */
	protected function getDestinationPath($tableName, $fieldName, $recordUid) {
		$fileadminDir = PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
		// fix folder
		if ($fileadminDir{strlen($fileadminDir)-1} == '/') {
			$fileadminDir = substr($fileadminDir, 0, strlen($fileadminDir) - 1);
		}
		$destinationPath = $fileadminDir . '/FAL_Migration/' . $tableName . '/' . $fieldName;

		if (is_array($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['fal/classes/controller/class.tx_fal_migrationcontroller.php']['copyFileToPath'])) {
			$params = array(
				'tableName' => $tableName,
				'fieldName' => $fieldName,
				'recordUid' => $recordUid,
			);

			foreach ($GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['fal/classes/controller/class.tx_fal_migrationcontroller.php']['copyFileToPath'] as $hookReference) {
				$hookObject =& t3lib_div::getUserObj($hookReference);
				if (method_exists($hookObject, 'copyFileToPath')) {
					$destinationPath = $hookObject->copyFileToPath($params, $destinationPath, $this);
				}
			}
		}

		if (strpos($destinationPath, $fileadminDir . '/FAL_Migration/') === false) {
			$destinationPath = $fileadminDir . '/FAL_Migration/' . $destinationPath;
		}

		if (!is_dir($destinationPath)) {
			t3lib_div::mkdir_deep(PATH_site, str_replace(PATH_site, '', $destinationPath));
		}

		return $destinationPath;
	}

	/**
	 * Fetch path from tca for tablename and field
	 *
	 * @param	string	$tableName		DESCRIPTION
	 * @param	string	$fieldName		DESCRIPTION
	 * @return	string					DESCRIPTION
	 */
	protected function getSourcePath($tableName, $fieldName) {
		$pathFromTCA = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['uploadfolder'];
		// make sure a trailing slash is there
		$pathParts = t3lib_div::trimExplode('/', $pathFromTCA);
		$fixedPath = implode('/', $pathParts) . '/';
		return PATH_site . $fixedPath;
	}

	/**
	 * Check if a sys_file is already available with the same sha1 hash
	 *
	 * @param	[to be defined]	$fileName	DESCRIPTION
	 * @return	boolean						DESCRIPTION
	 */
	protected function fetchDuplicateRecord($fileName, $filePath, $table, $fieldName) {
		$GLOBALS['TYPO3_DB']->debugOutput = 1;
		$result = FALSE;
		if(isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$table][$fieldName])) {
			$fieldName = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$table][$fieldName];
		}
		$fileHash = $this->getFileHash($fileName, $filePath);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'sys_files.*',
			'sys_files',
			'sys_files_usage_mm',
			$table,
			'AND sys_files.file_hash = \'' . $fileHash . '\' AND sys_files_usage_mm.tablenames = \''.$table.'\' AND sys_files_usage_mm.ident = \''.$fieldName.'\''
		);
		// if files with same filehash are found
		while ($fileRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if(count($fileRecord) > 1) {
				$result = $fileRecord;
				break;
			}
		}
		return $result;
	}

	/**
	 * Remove filename additions like _01 _02
	 *
	 * @param	string	$fileName		DESCRIPTION
	 * @return	string					DESCRIPTION
	 */
	protected function makeFilenameUnique($fileName) {
		// get extension
		$fileInfo = pathinfo($fileName);
		$fileName = str_replace('.' . $fileInfo['extension'], '', $fileName);
		$fileNameParts = explode('_', $fileName);
		$void = array_pop($fileNameParts);
		return implode('_', $fileNameParts) . '.' . $fileInfo['extension'];
	}

	/**
	 * Fetches the filehash and returns it
	 *
	 * @param	string	$fileName	DESCRIPTION
	 * @param	string	$filePath	DESCRIPTION
	 * @return	string				DESCRIPTION
	 */
	protected function getFileHash($fileName, $filePath) {
		return sha1_file($filePath . '/' . $fileName);
	}

	/**
	 * Create reference between record and asset
	 *
	 * @param	string	$tableName	DESCRIPTION
	 * @param	string	$fieldName	DESCRIPTION
	 * @param	integer	$recordUid	DESCRIPTION
	 * @param	integer	$assetUid	DESCRIPTION
	 * @param	integer	$sorting	DESCRIPTION
	 * @return	void
	 */
	protected function createReference($tableName, $fieldName, $recordUid, $assetUid, $sorting) {
		// get real fieldname
		if(isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$tableName][$fieldName])) {
			$fieldName = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fal']['tableAndFieldMapping'][$tableName][$fieldName];
		}
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			'sys_files_usage_mm',
			'uid_local = ' . $assetUid .
				' AND uid_foreign = ' . $recordUid .
				' AND tablenames = \'' . $tableName . '\'' .
				' AND ident = \'' . $fieldName . '\'' .
				' AND sorting = ' . $sorting
		);

		if (!$count) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'sys_files_usage_mm',
				array(
					'uid_local' => $assetUid,
					'uid_foreign' => $recordUid,
					'tablenames' => $tableName,
					'ident' => $fieldName,
					'sorting' => $sorting,
				)
			);
		}
	}

	/**
	 * Count the references for combination of asset, record, tablename, fieldname
	 *
	 * @param	string	$tableName	DESCRIPTION
	 * @param	string	$fieldName	DESCRIPTION
	 * @param	integer $recordUid	DESCRIPTION
	 * @param	integer $assetUid	DESCRIPTION
	 * @return	integer				DESCRIPTION
	 */
	protected function fetchReferenceCount($tableName, $fieldName, $recordUid) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'*',
			'sys_files_usage_mm',
			'uid_foreign = ' . $recordUid .
				' AND tablenames = \'' . $tableName . '\'' .
				' AND ident = \'' . $fieldName . '\''
		);
	}

	/**
	 * Update the reference count to table
	 *
	 * @param	integer	$referenceCount		DESCRIPTION
	 * @return	void
	 */
	protected function updateReferenceCount($tableName, $fieldName, $recordUid, $referenceCount) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$tableName,
			'uid = ' . $recordUid,
			array($fieldName . '_rel' => $referenceCount)
		);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/controller/class.tx_fal_migrationcontroller.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/controller/class.tx_fal_migrationcontroller.php']);
}
?>