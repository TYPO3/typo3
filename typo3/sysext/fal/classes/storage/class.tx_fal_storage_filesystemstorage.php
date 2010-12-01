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
 * File Abtraction Layer File System Storage
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_storage_FileSystemStorage implements tx_fal_storage_Interface {

	/**
	 * The configuration for this backend
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * DESCRIPTION
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * DESCRIPTION
	 *
	 * @param	array	$configuration		The configuration for the backend
	 */
	public function __construct($configuration) {
		$this->configuration = $configuration;

		if ($configuration['relative']) {
			$this->basePath = t3lib_div::resolveBackPath(PATH_site . $configuration['path']);
		} else {
			$this->basePath = $configuration['path'];
		}
		$this->basePath = rtrim($this->basePath, '/') . '/';

		// @todo throw exception if $this->basePath does not exist
	}

	/**
	 * @see tx_fal_storage_Interface::read()
	 */
	public function read($path) {
		return file_get_contents($this->basePath . $path);
	}

	/**
	 * @see tx_fal_storage_Interface::write()
	 */
	public function write($path, $content) {
		return file_put_contents($this->basePath . $path, $content);
	}

	/**
	 * @see tx_fal_storage_Interface::delete()
	 */
	public function delete($path) {
		$errorMessages = false;

		if ($this->exists($path)) {
			if (@unlink($this->basePath . $path)) {
				t3lib_div::devLog('file "' . $path . '" was deleted ', 'fal', 1, array('path' => $this->basePath . $path));
			} else {
				t3lib_div::devLog('ERROR: File "' . $path . '" could not be deleted!', 'fal', 3, array('path' => $this->basePath . $path));
				$errorMessages[$path] = 'ERROR: File "' . $path . '" could not be deleted!';
			}
		} else {
			$errorMessages[$path] = 'ERROR: File "' . $path . '" not found!';
		}

		if (empty($errorMessages)) {
			return array('success' => true);
		} else {
			return array(
					'success' => false,
 					'msg' => $errorMessages
 			);
		}
	}

	/**
	 * @see tx_fal_storage_Interface::exists()
	 */
	public function exists($path) {
		return file_exists($this->basePath . $path);
	}

	/**
	 * @see tx_fal_storage_Interface::getModificationTime()
	 */
	public function getModificationTime($path) {
		if ($this->exists($path)) {
			return filemtime($this->basePath . $path);
		}
	}

	/**
	 * @see tx_fal_storage_Interface::getFileHash()
	 */
	public function getFileHash($path) {
		if ($this->exists($path)) {
			return sha1_file($this->basePath . $path);
		}
	}

	/**
	 * @see tx_fal_storage_Interface::getSize()
	 */
	public function getSize($path) {
		if ($this->exists($path)) {
			return filesize($this->basePath . $path);
		}
	}

	/**
	 * @see tx_fal_storage_Interface::getBasePath()
	 */
	public function getBasePath() {
		return $this->basePath;
	}

	/**
	 * @see tx_fal_storage_Interface::open()
	 * @todo Implement tx_fal_storage_FileSystemStorage::open ?
	 */
	public function open($path, $mode) {}


	/**
	 * Copies a file inside this storage. All parameters are relative to the base of this storage
	 *
	 * @todo Andy Grunwald, 01.12.2010, move to interface
	 *
	 * @param	[to be defined]	$path		DESCRIPTION
	 * @param	[to be defined]	$newPath	DESCRIPTION
	 * @return	void
	 */
	public function copyFile($path, $newPath) {
		$path = $this->basePath . $path;
		$newPath = $this->basePath . $newPath;
		if (!file_exists($path)) {
			throw new InvalidArgumentException('Source file does not exist.');
		}

		if (!copy($path, $newPath)) {
			throw new RuntimeException("Copying file '$path' to '$newPath' failed.");
		}
	}

	/**
	 * Moves a file inside this storage.
	 *
	 * @todo Andy Grunwald, 01.12.2010, move to interface
	 *
	 * @param	string	$oldPath	The file to move
	 * @param	string	$newPath	The location to move to
	 * @return	array				With either success=true or success=false and a message why the action failed
	 */
	public function moveFile($oldPath, $newPath, $action='rename') {

		$oldPath = $this->basePath . $oldPath;
		$newPath = $this->basePath . $newPath;

		if (!file_exists($oldPath)) {
//			throw new InvalidArgumentException('Source file does not exist.');
			t3lib_div::devLog('ERROR: Source file does not exist. ' . __FUNCTION__, 'fal', 3, array(
				$oldPath
			));
		}
		if (file_exists($newPath)) {
//			throw new InvalidArgumentException('Target file already exists.');
			t3lib_div::devLog('ERROR: Target file already exists. ' . __FUNCTION__, 'fal', 3, array(
				$newPath
			));
		}

		/**
		 * @todo: <rupert.germann>, 26.11.2010
		 * implement overwriteExistingFiles switch ('altName'=1)
		 */

		$altName = 0;

		if ($action == 'rename') {
			$FILE = array(
				$action => array(
					array(
						'data' =>  basename($newPath),
						'target' => $oldPath,
						'altName' => $altName
					)
				)
			);
		}

		if ($action == 'move') {
			$FILE = array(
				$action => array(
					array(
						'data' => $oldPath,
						'target' => str_replace(basename($newPath), '', $newPath),
						'altName' => $altName
					)
				)
			);
		}

		$fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());

		$fileProcessor->start($FILE);
		$fileProcessor->processData();
		$fileProcessor->printLogErrorMessages();

			// catch flashmessages from class.t3lib_extfilefunc and return them via dataprovider
		$tmpFlashMessage = t3lib_FlashMessageQueue::getAllMessagesAndFlush();

			// take only the last message (needed because extfilefunc returns all messages from the same EXEC_TIME)
		$flashMessages = array_pop($tmpFlashMessage);




//		t3lib_div::devLog('$result ' . __FUNCTION__, 'fal', 1, array(
//	'$$flashMessages' => $flashMessages,
////	'$result' => $result,
//));

		if (empty($flashMessages)) {
			return array('success' => true);
		} else {
			return array(
					'success' => false,
 					'msg' => $flashMessages
 			);
		}
	}

	/**
	 * Creates a directory inside a path
	 *
	 * @todo Andy Grunwald, 01.12.2010, move to interface
	 *
	 * @return	boolean
	 */
	public function createDirectory($path, $directoryName) {
		$path = rtrim($this->basePath . $path, '/') . '/';

		return mkdir($path . $directoryName);
	}

	/**
	 * Returns a list of all files and directories inside a path
	 *
	 * @todo Andy Grunwald, 01.12.2010, move to interface
	 *
	 * @param	string	$path	DESCRIPTION
	 * @return	void
	 */
	public function getListing($path) {
		return t3lib_div::array_merge_recursive_overrule(
			$this->getDirectoriesInPath($path),
			$this->getFilesInPath($path)
		);
	}

	/**
	 * Returns a list of directories in a path.
	 *
	 * @param  $path
	 * @return array
	 */
	public function getDirectoriesInPath($path) {
		$folders = t3lib_div::get_dirs($this->basePath . $path);

		$foldersArray = array();
		foreach ($folders as $folder) {
			if ($folder{0} !== '.') {
				$foldersArray[$folder] = array(
					'name' => $folder,
					'type' => 'dir'
				);
			}
		}

		return $foldersArray;
	}

	/**
	 * Returns a list of files in a path.
	 *
	 * @param  $path
	 * @return array
	 */
	public function getFilesInPath($path) {
		$files = t3lib_div::getFilesInDir($this->basePath . $path, '', FALSE, '', '');

		$filesArray = array();
		foreach ($files as $file) {
			$filesArray[$file] = array(
				'name' => $file,
				'type' => 'file'
			);
		}

		return $filesArray;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/storage/class.tx_fal_storage_filesystemstorage.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/storage/class.tx_fal_storage_filesystemstorage.php']);
}
?>