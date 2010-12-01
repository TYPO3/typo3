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
 * File Abtraction Layer Indexer
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_Indexer {

	/**
	 * DESCRIPTION
	 *
	 * @todo Implement tx_fal_Indexer::indexDirectory
	 *
	 * @static
	 * @return	[to be defined]		DESCRIPTION
	 */
	public static function indexDirectory() {
	}

	/**
	 * Add a file to the index. This only works for files that already reside in the virtual
	 * file system we use; so you have to move the file to a mount manually.
	 *
	 * @todo previous doc comment said, that this method would return a file object
	 *
	 * @static
	 * @param	tx_fal_Mount	$mount		The mount the file resides in.
	 * @param	string			$filePath	Path to the file, relative to the mount root
	 * @return	integer						The file record, if the file could be successfully indexed
	 *
	 * @throws	RuntimeExceptionRuntimeException
	 */
	public static function addFileToIndex(tx_fal_Mount $mount, $filePath) {

		$backend = $mount->getStorageBackend();
		$relativePath = substr($filePath, strlen($backend->getBasePath()));
		if (!$backend->exists($relativePath)) {
			throw new RuntimeException("File $relativePath does not exist.");
		}
		$fileName = basename($relativePath);
		$folder = dirname($relativePath) . '/';


			// check, if the index entry already exists
		$existingEntry = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_files',
			'file_path = "' . $folder . '" AND file_name = "' . $fileName . '" AND mount = ' . intval($mount->getUid) . ' AND deleted=0');

		if ($existingEntry[0]['uid'] > 0) {
			return $existingEntry[0]['uid'];
		} else {
			$success = $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_files', array(
				'file_name' => $fileName,
				'file_path' => $folder,
				'file_size' => $backend->getSize($relativePath),
				'file_mtime' => $backend->getModificationTime($relativePath),
				'file_hash' => $backend->getFileHash($relativePath),
				'mount' => $mount->getUid(),
				'crdate' => $GLOBALS['EXEC_TIME'],
				'tstamp' => $GLOBALS['EXEC_TIME']
			));

			if (!$success) {
				t3lib_div::devLog(
					'Indexing file ' . $folder . $fileName . ' in mount ' . $mount->getAlias() . ' failed.
					 SQL error: ' . $GLOBALS['TYPO3_DB']->sql_error(), 'fal_indexer', 3);
			}

			/** @var $repo tx_fal_Repository */
			return $GLOBALS['TYPO3_DB']->sql_insert_id();

		}
		//$repo = t3lib_div::makeInstance('tx_fal_Repository');
		//return $repo->getFileById($GLOBALS['TYPO3_DB']->sql_insert_id());
	}

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	tx_fal_File		$file	DESCRIPTION
	 * @return	[to be defined]			DESCRIPTION
	 */
	public static function updateFileIndex(tx_fal_File $file) {

		$updateFields = array(
				'file_name' => $file->getName(),
				'file_path' => $file->getPath(),
				//'file_mtime' => $file->mount->getStorageBackend()->getModificationTime($file->getUrl()),
				'tstamp' => $GLOBALS['EXEC_TIME']
			);

		$sucess = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_files', 'uid=' . $file->getUid() . ' AND deleted=0', $updateFields);
	}

	/**
	 * Delete a file from index
	 *
	 * @static
	 * @param		integer		$fileUid	UID of the file record to remove from index
	 * @return		void
	 */
	public static function deleteFileFromIndex($fileUid) {
		$sucess = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_files', 'uid=' . $fileUid,
			array('deleted' => 1)
		);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Indexer.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Indexer.php']);
}
?>