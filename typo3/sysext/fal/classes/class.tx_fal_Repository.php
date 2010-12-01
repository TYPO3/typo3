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
 * File Abtraction Layer Repository
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_Repository implements t3lib_Singleton {

	/**
	 * Get the file by ID
	 *
	 * @param	integer				$uid	UID of the file record
	 * @return	t3lib_file_File				A file object instance
	 */
	public function getFileById($uid) {
		$recordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_files', 'uid=' . $uid . ' AND deleted=0');

		if (count($recordData) == 0) {
			throw new tx_fal_exception_FileNotFound();
		}

		$mount = $this->getMountForFile($recordData);

		return new tx_fal_File($mount, $recordData[0]);
	}

	/**
	 * DESCRIPTION
	 *
	 * @param	string				$field		TCA field name
	 * @param	string				$table		TCA table name
	 * @param	integer				$recordUid	Uid of record for $table
	 * @return	array/tx_fal_File				Array of tx_fal_File-objects for the defined relation ($field, $table, $recordUid)
	 */
	public function getFilesFromRelation($field, $table, $recordUid) {
			// @todo: Check if constant name and value TYPO3_MODE === 'FE' are correct for checking for FE.
		if(TYPO3_MODE === 'FE') {
			$GLOBALS['TSFE']->includeTCA();
		}
		t3lib_div::loadTCA($table);

		$assetObjects = array();

		// get column definition
		$MMfieldTCA = $GLOBALS['TCA'][$table]['columns'][$field]['config'];

		$dbGroup = t3lib_div::makeInstance('t3lib_loadDBGroup');
		$dbGroup->start($recordUid, 'sys_files', $MMfieldTCA['MM'], intval($recordUid), $table, $MMfieldTCA);
		$assetIds = $dbGroup->tableArray['sys_files'];
		if (is_array($assetIds)) {
			foreach ($assetIds as $key => $uid) {
				$assetObjects[] = $this->getFileById($uid);
			}
		}
		return $assetObjects;
	}

	/**
	 * Returns a file object by the file's complete path.
	 *
	 * @param	string				$filePath	The path to the file. May contain the alias of the mount if this is not given as the second parameter.
	 * @param	tx_fal_Mount		$mount		The mount the file is located in (optional). If this is not given, the mount is extracted from the first part of the path.
	 * @return	tx_fal_File						tx_fal_File object of file
	 *
	 * @throws	tx_fal_exception_FileNotFound
	 */
	public function getFileByPath($filePath, $mount = NULL) {
		if ($mount == NULL) {
			$pathParts = explode('/', $filePath, 2);
			$mountAlias = array_shift($pathParts);
			$mount = tx_fal_Mount::getInstanceForAlias($mountAlias);
		}

		$fileName = substr($filePath, strrpos($filePath, '/') + 1);
		$folderPath = substr($filePath, 0, strrpos($filePath, '/') + 1);

		$recordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_files',
			'file_path = "' . $folderPath . '" AND file_name = "' . $fileName . '" AND mount = ' . intval($mount->getUid));

		if (count($recordData) == 0) {
			throw new tx_fal_exception_FileNotFound();
		}
		$mount = $this->getMountForFile($recordData);

		return new tx_fal_File($mount, $recordData[0]);
	}

	/**
	 * Returns the mount record for a specified file.
	 *
	 * @param	array	$fileRecord		DESC
	 * @return	void
	 */
	protected function getMountForFile(array $fileRecord) {
		$mount = $fileRecord['mount'];

		return tx_fal_Mount::getInstanceForUid($mount);
	}

	/**
	 * Returns an array of tx_fal_File objects for all files in defined path $filePath
	 *
	 * @todo: respect file mount (extract it from the path or take it as parameter - both ways should be supported)
	 *
	 * @param	string				$filePath	File path
	 * @return	array/tx_fal_File				Array of tx_fal_File objects
	 *
	 * @throws	tx_fal_exception_FileNotFound
	 */
	public function getAllInPath($filePath) {

			// @todo fix sql injection / dbal incomp. -> use t3lib_db quote string
		$recordData = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_files',
			'file_path = "' . $filePath . '" AND deleted=0');


		if (count($recordData) == 0) {
			throw new tx_fal_exception_FileNotFound();
		}
		$mount = $this->getMountForFile($recordData[0]);

		$files = array();
		foreach ($recordData as $data) {
			$files[] = new tx_fal_File($mount, $data);
		}
		return $files;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Repository.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Repository.php']);
}
?>