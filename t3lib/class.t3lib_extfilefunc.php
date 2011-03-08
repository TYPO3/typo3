<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * extending class to class t3lib_basicFileFunctions
 *
 * $Id$
 * Revised for TYPO3 3.6 May/2004 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  105: class t3lib_extFileFunctions extends t3lib_basicFileFunctions
 *  151:	 function start($fileCmds)
 *  181:	 function init_actionPerms($setup)
 *  213:	 function processData()
 *  270:	 function printLogErrorMessages($redirect='')
 *  328:	 function findRecycler($theFile)
 *  357:	 function writeLog($action,$error,$details_nr,$details,$data)
 *
 *			  SECTION: File operation functions
 *  384:	 function func_delete($cmds)
 *  451:	 function func_copy($cmds)
 *  542:	 function func_move($cmds)
 *  637:	 function func_rename($cmds)
 *  683:	 function func_newfolder($cmds)
 *  713:	 function func_newfile($cmds)
 *  750:	 function func_edit($cmds)
 *  782:	 function func_upload($cmds)
 *  821:	 function func_unzip($cmds)
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Contains functions for performing file operations like copying, pasting, uploading, moving, deleting etc. through the TCE
 * Extending class to class t3lib_basicFileFunctions.
 *
 * see basicFileFunctions
 * see document "TYPO3 Core API" for syntax
 *
 * This class contains functions primarily used by tce_file.php (TYPO3 Core Engine for file manipulation)
 * Functions include copying, moving, deleting, uploading and so on...
 *
 * Important internal variables:
 *
 * $filemounts		(see basicFileFunctions)
 * $f_ext	  (see basicFileFunctions)
 *	 ... All fileoperations must be within the filemount-paths. Further the fileextension MUST validate true with the f_ext array
 *
 * The unzip-function allows unzip only if the destination path has it's f_ext[]['allow'] set to '*'!!
 * You are allowed to copy/move folders within the same 'space' (web/ftp).
 * You are allowed to copy/move folders between spaces (web/ftp) IF the destination has it's f_ext[]['allow'] set to '*'!
 *
 * Advice:
 * You should always exclude php-files from the webspace. This will keep people from uploading, copy/moving and renaming files to become executable php scripts.
 * You should never mount a ftp_space 'below' the webspace so that it reaches into the webspace. This is because if somebody unzips a zip-file in the ftp-space so that it reaches out into the webspace this will be a violation of the safety
 * For example this is a bad idea: you have an ftp-space that is '/www/' and a web-space that is '/www/htdocs/'
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_extFileFunctions extends t3lib_basicFileFunctions {

		// External static variables:
		// Notice; some of these are overridden in the start() method with values from $GLOBALS['TYPO3_CONF_VARS']['BE']
	var $maxCopyFileSize = 10000; // max copy size (kb) for files
	var $maxMoveFileSize = 10000; // max move size (kb) for files
	var $maxUploadFileSize = 10000; // max upload size (kb) for files. Remember that PHP has an inner limit often set to 2 MB
	var $unzipPath = ''; // Path to unzip-program (with trailing '/')
	var $dontCheckForUnique = 0; // If set, the uploaded files will overwrite existing files.

	var $actionPerms = Array( // This array is self-explaning (look in the class below). It grants access to the functions. This could be set from outside in order to enabled functions to users. See also the function init_actionPerms() which takes input directly from the user-record
		'deleteFile' => 0, // Deleting files physically
		'deleteFolder' => 0, // Deleting foldes physically
		'deleteFolderRecursively' => 0, // normally folders are deleted by the PHP-function rmdir(), but with this option a user deletes with 'rm -Rf ....' which is pretty wild!
		'moveFile' => 0,
		'moveFolder' => 0,
		'copyFile' => 0,
		'copyFolder' => 0,
		'newFolder' => 0,
		'newFile' => 0,
		'editFile' => 0,
		'unzipFile' => 0,
		'uploadFile' => 0,
		'renameFile' => 0,
		'renameFolder' => 0
	);

	var $recyclerFN = '_recycler_'; // This is regarded to be the recycler folder
	var $useRecycler = 1; // 0 = no, 1 = if available, 2 = always

		// Internal, static:
	var $PHPFileFunctions = 0; // If set, all fileoperations are done by the default PHP-functions. This is necessary under windows! On UNIX the system commands by exec() can be used unless safe_mode is enabled
	var $dont_use_exec_commands = 0; // This is necessary under windows!

		// Internal, dynamic:
	var $internalUploadMap = array(); // Will contain map between upload ID and the final filename

	var $lastError = '';


	/**
	 * Initialization of the class
	 *
	 * @param	array		The $file array with the commands to execute. See "TYPO3 Core API" document
	 * @return	void
	 */
	function start($fileCmds) {

			// Configure settings from TYPO3_CONF_VARS:
		if (TYPO3_OS == 'WIN' || $GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']) {
			$this->PHPFileFunctions = 1;
			$this->dont_use_exec_commands = 1;
		} else {
			$this->PHPFileFunctions = $GLOBALS['TYPO3_CONF_VARS']['BE']['usePHPFileFunctions'];
		}

		$this->unzipPath = $GLOBALS['TYPO3_CONF_VARS']['BE']['unzip_path'];

		$maxFileSize = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize']);
		if ($maxFileSize > 0) {
			$this->maxCopyFileSize = $maxFileSize;
			$this->maxMoveFileSize = $maxFileSize;
		}
		$this->maxUploadFileSize = t3lib_div::getMaxUploadFileSize();

			// Initializing file processing commands:
		$this->fileCmdMap = $fileCmds;
	}

	/**
	 * Sets up permission to perform file/directory operations.
	 * See below or the be_user-table for the significance of the various bits in $setup.
	 *
	 * @param	integer		File permission integer from BE_USER OR'ed with permissions of back-end groups this user is a member of
	 * @return	void
	 */
	function init_actionPerms($setup) {
		if (($setup & 1) == 1) { // Files: Upload,Copy,Move,Delete,Rename
			$this->actionPerms['uploadFile'] = 1;
			$this->actionPerms['copyFile'] = 1;
			$this->actionPerms['moveFile'] = 1;
			$this->actionPerms['deleteFile'] = 1;
			$this->actionPerms['renameFile'] = 1;
			$this->actionPerms['editFile'] = 1;
			$this->actionPerms['newFile'] = 1;
		}
		if (($setup & 2) == 2) { // Files: Unzip
			$this->actionPerms['unzipFile'] = 1;
		}
		if (($setup & 4) == 4) { // Directory: Move,Delete,Rename,New
			$this->actionPerms['moveFolder'] = 1;
			$this->actionPerms['deleteFolder'] = 1;
			$this->actionPerms['renameFolder'] = 1;
			$this->actionPerms['newFolder'] = 1;
		}
		if (($setup & 8) == 8) { // Directory: Copy
			$this->actionPerms['copyFolder'] = 1;
		}
		if (($setup & 16) == 16) { // Directory: Delete recursively (rm -Rf)
			$this->actionPerms['deleteFolderRecursively'] = 1;
		}
	}

	/**
	 * Processing the command array in $this->fileCmdMap
	 *
	 * @return	mixed	false, if the file functions were not initialized
	 *					otherwise returns an array of all the results that are returned
	 *					from each command, separated in each action.
	 */
	function processData() {
		$result = array();
		if (!$this->isInit) {
			return FALSE;
		}

		if (is_array($this->fileCmdMap)) {

				// Check if there were uploads expected, but no one made
			if ($this->fileCmdMap['upload']) {
				$uploads = $this->fileCmdMap['upload'];
				foreach ($uploads as $upload) {
					if (!$_FILES['upload_' . $upload['data']]['name']) {
						unset($this->fileCmdMap['upload'][$upload['data']]);
					}
				}
				if (count($this->fileCmdMap['upload']) == 0) {
					$this->writelog(1, 1, 108, 'No file was uploaded!', '');
				}
			}

				// Traverse each set of actions
			foreach ($this->fileCmdMap as $action => $actionData) {

					// Traverse all action data. More than one file might be affected at the same time.
				if (is_array($actionData)) {
					$result[$action] = array();
					foreach ($actionData as $cmdArr) {

							// Clear file stats
						clearstatcache();

							// Branch out based on command:
						switch ($action) {
							case 'delete':
								$result[$action][] = $this->func_delete($cmdArr);
							break;
							case 'copy':
								$result[$action][] = $this->func_copy($cmdArr);
							break;
							case 'move':
								$result[$action][] = $this->func_move($cmdArr);
							break;
							case 'rename':
								$result[$action][] = $this->func_rename($cmdArr);
							break;
							case 'newfolder':
								$result[$action][] = $this->func_newfolder($cmdArr);
							break;
							case 'newfile':
								$result[$action][] = $this->func_newfile($cmdArr);
							break;
							case 'editfile':
								$result[$action][] = $this->func_edit($cmdArr);
							break;
							case 'upload':
								$result[$action][] = $this->func_upload($cmdArr);
							break;
							case 'unzip':
								$result[$action][] = $this->func_unzip($cmdArr);
							break;
						}

							// Hook for post-processing the action
						if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'])) {
							foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'] as $classRef) {
								$hookObject = t3lib_div::getUserObj($classRef);

								if (!($hookObject instanceof t3lib_extFileFunctions_processDataHook)) {
									throw new UnexpectedValueException('$hookObject must implement interface t3lib_extFileFunctions_processDataHook', 1279719168);
								}

								$hookObject->processData_postProcessAction($action, $cmdArr, $result[$action], $this);
							}
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Adds log error messages from the operations of this script instance to the FlashMessageQueue
	 *
	 * @param	string		Redirect URL (for creating link in message)
	 * @return	void
	 */
	function printLogErrorMessages($redirect = '') {
		$this->getErrorMessages();
	}


	/**
	 * Adds log error messages from the previous file operations of this script instance
	 * to the FlashMessageQueue
	 *
	 * @return	void
	 */
	function getErrorMessages() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'sys_log',
				'type = 2 AND userid = ' . intval($GLOBALS['BE_USER']->user['uid'])
						. ' AND tstamp=' . intval($GLOBALS['EXEC_TIME'])
						. ' AND error != 0'
		);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$logData = unserialize($row['log_data']);
			$msg = $row['error'] . ': ' . sprintf($row['details'], $logData[0], $logData[1], $logData[2], $logData[3], $logData[4]);
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$msg,
				'',
				t3lib_FlashMessage::ERROR,
				TRUE
			);
			t3lib_FlashMessageQueue::addMessage($flashMessage);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}


	/**
	 * Goes back in the path and checks in each directory if a folder named $this->recyclerFN (usually '_recycler_') is present.
	 * If a folder in the tree happens to be a _recycler_-folder (which means that we're deleting something inside a _recycler_-folder) this is ignored
	 *
	 * @param	string		Takes a valid Path ($theFile)
	 * @return	string		Returns the path (without trailing slash) of the closest recycle-folder if found. Else false.
	 */
	function findRecycler($theFile) {
		if ($this->isPathValid($theFile)) {
			$theFile = $this->cleanDirectoryName($theFile);
			$fI = t3lib_div::split_fileref($theFile);
			$c = 0;
			while ($this->checkPathAgainstMounts($fI['path']) && $c < 20) {
				$rDir = $fI['path'] . $this->recyclerFN;
				if (@is_dir($rDir) && $this->recyclerFN != $fI['file']) {
					return $rDir;
				}
				$theFile = $fI['path'];
				$theFile = $this->cleanDirectoryName($theFile);
				$fI = t3lib_div::split_fileref($theFile);
				$c++;
			}
		}
	}

	/**
	 * Logging file operations
	 *
	 * @param	integer		The action number. See the functions in the class for a hint. Eg. edit is '9', upload is '1' ...
	 * @param	integer		The severity: 0 = message, 1 = error, 2 = System Error, 3 = security notice (admin)
	 * @param	integer		This number is unique for every combination of $type and $action. This is the error-message number, which can later be used to translate error messages.
	 * @param	string		This is the default, raw error message in english
	 * @param	array		Array with special information that may go into $details by "%s" marks / sprintf() when the log is shown
	 * @return	void
	 * @see	class.t3lib_userauthgroup.php
	 */
	function writeLog($action, $error, $details_nr, $details, $data) {
		$type = 2; // Type value for tce_file.php
		if (is_object($GLOBALS['BE_USER'])) {
			$GLOBALS['BE_USER']->writelog($type, $action, $error, $details_nr, $details, $data);
		}
		$this->lastError = vsprintf($details, $data);
	}


	/*************************************
	 *
	 * File operation functions
	 *
	 **************************************/

	/**
	 * Deleting files and folders (action=4)
	 *
	 * @param	array		$cmds['data'] is the file/folder to delete
	 * @return	boolean		Returns true upon success
	 */
	function func_delete($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

			// Checking path:
		$theFile = $cmds['data'];
		if (!$this->isPathValid($theFile)) {
			$this->writelog(4, 2, 101, 'Target "%s" had invalid path (".." and "//" is not allowed in path).', Array($theFile));
			return FALSE;
		}

			// Recycler moving or not?
		if ($this->useRecycler && $recyclerPath = $this->findRecycler($theFile)) {
				// If a recycler is found, the deleted items is moved to the recycler and not just deleted.
			$newCmds = Array();
			$newCmds['data'] = $theFile;
			$newCmds['target'] = $recyclerPath;
			$newCmds['altName'] = 1;
			$this->func_move($newCmds);
			$this->writelog(4, 0, 4, 'Item "%s" moved to recycler at "%s"', Array($theFile, $recyclerPath));
			return TRUE;
		} elseif ($this->useRecycler != 2) { // if $this->useRecycler==2 then we cannot delete for real!!
			if (@is_file($theFile)) { // If we are deleting a file...
				if (!$this->actionPerms['deleteFile']) {
					$this->writelog(4, 1, 112, 'You are not allowed to delete files', '');
					return FALSE;
				}
				if (!$this->checkPathAgainstMounts($theFile)) {
					$this->writelog(4, 1, 111, 'Target was not within your mountpoints! T="%s"', Array($theFile));
					return FALSE;
				}
				if (@unlink($theFile)) {
					$this->writelog(4, 0, 1, 'File "%s" deleted', Array($theFile));
					return TRUE;
				} else {
					$this->writelog(4, 1, 110, 'Could not delete file "%s". Write-permission problem?', Array($theFile));
				}
				// FINISHED deleting file

			} elseif (@is_dir($theFile)) { // if we're deleting a folder
				if (!$this->actionPerms['deleteFolder']) {
					$this->writelog(4, 1, 123, 'You are not allowed to delete directories', '');
					return FALSE;
				}

				$theFile = $this->is_directory($theFile);
				if (!$theFile) {
					$this->writelog(4, 2, 122, 'Target seemed not to be a directory! (Shouldn\'t happen here!)', '');
					return FALSE;
				}
				if (!$this->checkPathAgainstMounts($theFile)) {
					$this->writelog(4, 1, 121, 'Target was not within your mountpoints! T="%s"', Array($theFile));
					return FALSE;
				}
					// I choose not to append '/' to $theFile here as this will prevent us from deleting mounts!! (which makes sense to me...)
				if ($this->actionPerms['deleteFolderRecursively']) {
					if (t3lib_div::rmdir($theFile, TRUE)) {
						$this->writelog(4, 0, 2, 'Directory "%s" deleted recursively!', Array($theFile));
						return TRUE;
					} else {
						$this->writelog(4, 2, 119, 'Directory "%s" WAS NOT deleted recursively! Write-permission problem?', Array($theFile));
					}
				} else {
					if (@rmdir($theFile)) {
						$this->writelog(4, 0, 3, 'Directory "%s" deleted', Array($theFile));
						return TRUE;
					} else {
						$this->writelog(4, 1, 120, 'Could not delete directory! Write-permission problem? Is directory "%s" empty? (You are not allowed to delete directories recursively).', Array($theFile));
					}
				}
				// FINISHED copying directory

			} else {
				$this->writelog(4, 2, 130, 'The item was not a file or directory! "%s"', Array($theFile));
			}
		} else {
			$this->writelog(4, 1, 131, 'No recycler found!', '');
		}
	}

	/**
	 * Copying files and folders (action=2)
	 *
	 * @param	array		$cmds['data'] is the file/folder to copy. $cmds['target'] is the path where to copy to. $cmds['altName'] (boolean): If set, another filename is found in case the target already exists
	 * @return	string		Returns the new filename upon success
	 */
	function func_copy($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

			// Initialize and check basic conditions:
		$theFile = $cmds['data'];
		$theDest = $this->is_directory($cmds['target']); // Clean up destination directory
		$altName = $cmds['altName'];
		if (!$theDest) {
			$this->writelog(2, 2, 100, 'Destination "%s" was not a directory', Array($cmds['target']));
			return FALSE;
		}
		if (!$this->isPathValid($theFile) || !$this->isPathValid($theDest)) {
			$this->writelog(2, 2, 101, 'Target or destination had invalid path (".." and "//" is not allowed in path). T="%s", D="%s"', Array($theFile, $theDest));
			return FALSE;
		}

			// Processing of file or directory.
		if (@is_file($theFile)) { // If we are copying a file...
			if (!$this->actionPerms['copyFile']) {
				$this->writelog(2, 1, 114, 'You are not allowed to copy files', '');
				return FALSE;
			}
			if (filesize($theFile) >= ($this->maxCopyFileSize * 1024)) {
				$this->writelog(2, 1, 113, 'File "%s" exceeds the size-limit of %s bytes', Array($theFile, $this->maxCopyFileSize * 1024));
				return FALSE;
			}
			$fI = t3lib_div::split_fileref($theFile);
			if ($altName) { // If altName is set, we're allowed to create a new filename if the file already existed
				$theDestFile = $this->getUniqueName($fI['file'], $theDest);
				$fI = t3lib_div::split_fileref($theDestFile);
			} else {
				$theDestFile = $theDest . '/' . $fI['file'];
			}
			if (!$theDestFile || file_exists($theDestFile)) {
				$this->writelog(2, 1, 112, 'File "%s" already exists!', Array($theDestFile));
				return FALSE;
			}
			if (!$this->checkIfAllowed($fI['fileext'], $theDest, $fI['file'])) {
				$this->writelog(2, 1, 111, 'Extension of file name "%s" is not allowed in "%s"!', Array($fI['file'], $theDest . '/'));
				return FALSE;
			}
			if (!$this->checkPathAgainstMounts($theDestFile) || !$this->checkPathAgainstMounts($theFile)) {
				$this->writelog(2, 1, 110, 'Target or destination was not within your mountpoints! T="%s", D="%s"', Array($theFile, $theDestFile));
				return FALSE;
			}
			if ($this->PHPFileFunctions) {
				copy($theFile, $theDestFile);
			} else {
				$cmd = 'cp "' . $theFile . '" "' . $theDestFile . '"';
				t3lib_utility_Command::exec($cmd);
			}
			t3lib_div::fixPermissions($theDestFile);
			clearstatcache();
			if (@is_file($theDestFile)) {
				$this->writelog(2, 0, 1, 'File "%s" copied to "%s"', Array($theFile, $theDestFile));
				return $theDestFile;
			} else {
				$this->writelog(2, 2, 109, 'File "%s" WAS NOT copied to "%s"! Write-permission problem?', Array($theFile, $theDestFile));
				return FALSE;
			}
			// FINISHED copying file

		} elseif (@is_dir($theFile) && !$this->dont_use_exec_commands) { // if we're copying a folder
			if (!$this->actionPerms['copyFolder']) {
				$this->writelog(2, 1, 125, 'You are not allowed to copy directories', '');
				return FALSE;
			}
			$theFile = $this->is_directory($theFile);
			if (!$theFile) {
				$this->writelog(2, 2, 124, 'Target seemed not to be a directory! (Shouldn\'t happen here!)', '');
				return FALSE;
			}
			$fI = t3lib_div::split_fileref($theFile);
			if ($altName) { // If altName is set, we're allowed to create a new filename if the file already existed
				$theDestFile = $this->getUniqueName($fI['file'], $theDest);
				$fI = t3lib_div::split_fileref($theDestFile);
			} else {
				$theDestFile = $theDest . '/' . $fI['file'];
			}
			if (!$theDestFile || file_exists($theDestFile)) {
				$this->writelog(2, 1, 123, 'Target "%s" already exists!', Array($theDestFile));
				return FALSE;
			}
			if (!t3lib_div::isFirstPartOfStr($theDestFile . '/', $theFile . '/')) {
				$this->writelog(2, 1, 122, 'Destination cannot be inside the target! D="%s", T="%s"', Array($theDestFile . '/', $theFile . '/'));
				return FALSE;
			}
				// Check if the one folder is inside the other or on the same level... to target/dest is the same?
			if ($this->checkIfFullAccess($theDest) || $this->is_webPath($theDestFile) == $this->is_webPath($theFile)) { // no copy of folders between spaces
				$this->writelog(2, 1, 121, 'You don\'t have full access to the destination directory "%s"!', Array($theDest . '/'));
				return FALSE;
			}
			if (!$this->checkPathAgainstMounts($theDestFile) || !$this->checkPathAgainstMounts($theFile)) {
				$this->writelog(2, 1, 120, 'Target or destination was not within your mountpoints! T="%s", D="%s"', Array($theFile, $theDestFile));
				return FALSE;
			}
			// No way to do this under windows!
			$cmd = 'cp -R "' . $theFile . '" "' . $theDestFile . '"';
			t3lib_utility_Command::($cmd);
			clearstatcache();
			if (@is_dir($theDestFile)) {
				$this->writelog(2, 0, 2, 'Directory "%s" copied to "%s"', Array($theFile, $theDestFile));
				return $theDestFile;
			} else {
				$this->writelog(2, 2, 119, 'Directory "%s" WAS NOT copied to "%s"! Write-permission problem?', Array($theFile, $theDestFile));
				return FALSE;
			}
			// FINISHED copying directory

		} else {
			$this->writelog(2, 2, 130, 'The item "%s" was not a file or directory!', Array($theFile));
		}
	}

	/**
	 * Moving files and folders (action=3)
	 *
	 * @param	array		$cmds['data'] is the file/folder to move. $cmds['target'] is the path where to move to. $cmds['altName'] (boolean): If set, another filename is found in case the target already exists
	 * @return	string		Returns the new filename upon success
	 */
	function func_move($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

			// Initialize and check basic conditions:
		$theFile = $cmds['data'];
		$theDest = $this->is_directory($cmds['target']); // Clean up destination directory
		$altName = $cmds['altName'];
		if (!$theDest) {
			$this->writelog(3, 2, 100, 'Destination "%s" was not a directory', Array($cmds['target']));
			return FALSE;
		}
		if (!$this->isPathValid($theFile) || !$this->isPathValid($theDest)) {
			$this->writelog(3, 2, 101, 'Target or destination had invalid path (".." and "//" is not allowed in path). T="%s", D="%s"', Array($theFile, $theDest));
			return FALSE;
		}

			// Processing of file or directory:
		if (@is_file($theFile)) { // If we are moving a file...
			if (!$this->actionPerms['moveFile']) {
				$this->writelog(3, 1, 114, 'You are not allowed to move files', '');
				return FALSE;
			}
			if (filesize($theFile) >= ($this->maxMoveFileSize * 1024)) {
				$this->writelog(3, 1, 113, 'File "%s" exceeds the size-limit of %s bytes', Array($theFile, $this->maxMoveFileSize * 1024));
				return FALSE;
			}
			$fI = t3lib_div::split_fileref($theFile);
			if ($altName) { // If altName is set, we're allowed to create a new filename if the file already existed
				$theDestFile = $this->getUniqueName($fI['file'], $theDest);
				$fI = t3lib_div::split_fileref($theDestFile);
			} else {
				$theDestFile = $theDest . '/' . $fI['file'];
			}
			if (!$theDestFile || file_exists($theDestFile)) {
				$this->writelog(3, 1, 112, 'File "%s" already exists!', Array($theDestFile));
				return FALSE;
			}
			if (!$this->checkIfAllowed($fI['fileext'], $theDest, $fI['file'])) {
				$this->writelog(3, 1, 111, 'Extension of file name "%s" is not allowed in "%s"!', Array($fI['file'], $theDest . '/'));
				return FALSE;
			}
			if (!$this->checkPathAgainstMounts($theDestFile) || !$this->checkPathAgainstMounts($theFile)) {
				$this->writelog(3, 1, 110, 'Target or destination was not within your mountpoints! T="%s", D="%s"', Array($theFile, $theDestFile));
				return FALSE;
			}
			if ($this->PHPFileFunctions) {
				@rename($theFile, $theDestFile);
			} else {
				$cmd = 'mv "' . $theFile . '" "' . $theDestFile . '"';
				t3lib_utility_Command::exec($cmd);
			}
			clearstatcache();
			if (@is_file($theDestFile)) {
				$this->writelog(3, 0, 1, 'File "%s" moved to "%s"', Array($theFile, $theDestFile));
				return $theDestFile;
			} else {
				$this->writelog(3, 2, 109, 'File "%s" WAS NOT moved to "%s"! Write-permission problem?', Array($theFile, $theDestFile));
				return FALSE;
			}
			// FINISHED moving file

		} elseif (@is_dir($theFile)) { // if we're moving a folder
			if (!$this->actionPerms['moveFolder']) {
				$this->writelog(3, 1, 125, 'You are not allowed to move directories', '');
				return FALSE;
			}
			$theFile = $this->is_directory($theFile);
			if (!$theFile) {
				$this->writelog(3, 2, 124, 'Target seemed not to be a directory! (Shouldn\'t happen here!)', '');
				return FALSE;
			}
			$fI = t3lib_div::split_fileref($theFile);
			if ($altName) { // If altName is set, we're allowed to create a new filename if the file already existed
				$theDestFile = $this->getUniqueName($fI['file'], $theDest);
				$fI = t3lib_div::split_fileref($theDestFile);
			} else {
				$theDestFile = $theDest . '/' . $fI['file'];
			}
			if (!$theDestFile || file_exists($theDestFile)) {
				$this->writelog(3, 1, 123, 'Target "%s" already exists!', Array($theDestFile));
				return FALSE;
			}
			if (t3lib_div::isFirstPartOfStr($theDestFile . '/', $theFile . '/')) { // Check if the one folder is inside the other or on the same level... to target/dest is the same?
				$this->writelog(3, 1, 122, 'Destination cannot be inside the target! D="%s", T="%s"', Array($theDestFile . '/', $theFile . '/'));
				return FALSE;
			}
			if (!$this->checkIfFullAccess($theDest) && $this->is_webPath($theDestFile) != $this->is_webPath($theFile)) { // // no moving of folders between spaces
				$this->writelog(3, 1, 121, 'You don\'t have full access to the destination directory "%s"!', Array($theDest . '/'));
				return FALSE;
			}
			if (!$this->checkPathAgainstMounts($theDestFile) || !$this->checkPathAgainstMounts($theFile)) {
				$this->writelog(3, 1, 120, 'Target or destination was not within your mountpoints! T="%s", D="%s"', Array($theFile, $theDestFile));
				return FALSE;
			}
			if ($this->PHPFileFunctions) {
				@rename($theFile, $theDestFile);
			} else {
				$cmd = 'mv "' . $theFile . '" "' . $theDestFile . '"';
				$errArr = array();
				t3lib_utility_Command::exec($cmd, $errArr);
			}
			clearstatcache();
			if (@is_dir($theDestFile)) {
				$this->writelog(3, 0, 2, 'Directory "%s" moved to "%s"', Array($theFile, $theDestFile));
				return $theDestFile;
			} else {
				$this->writelog(3, 2, 119, 'Directory "%s" WAS NOT moved to "%s"! Write-permission problem?', Array($theFile, $theDestFile));
				return FALSE;
			}
			// FINISHED moving directory

		} else {
			$this->writelog(3, 2, 130, 'The item "%s" was not a file or directory!', Array($theFile));
		}
	}

	/**
	 * Renaming files or foldes (action=5)
	 *
	 * @param	array		$cmds['data'] is the new name. $cmds['target'] is the target (file or dir).
	 * @return	string		Returns the new filename upon success
	 */
	function func_rename($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$theNewName = $this->cleanFileName($cmds['data']);
		if (!$theNewName) {
			return FALSE;
		}
		if (!$this->checkFileNameLen($theNewName)) {
			$this->writelog(5, 1, 124, 'New name "%s" was too long (max %s characters)', Array($theNewName, $this->maxInputNameLen));
			return FALSE;
		}
		$theTarget = $cmds['target'];
		$type = filetype($theTarget);
		if ($type != 'file' && $type != 'dir') { // $type MUST BE file or dir
			$this->writelog(5, 2, 123, 'Target "%s" was neither a directory nor a file!', Array($theTarget));
			return FALSE;
		}
		$fileInfo = t3lib_div::split_fileref($theTarget); // Fetches info about path, name, extention of $theTarget
		if ($fileInfo['file'] == $theNewName) { // The name should be different from the current. And the filetype must be allowed
			$this->writelog(5, 1, 122, 'Old and new name is the same (%s)', Array($theNewName));
			return FALSE;
		}
		$theRenameName = $fileInfo['path'] . $theNewName;
		if (!$this->checkPathAgainstMounts($fileInfo['path'])) {
			$this->writelog(5, 1, 121, 'Destination path "%s" was not within your mountpoints!', Array($fileInfo['path']));
			return FALSE;
		}
		if (file_exists($theRenameName)) {
			$this->writelog(5, 1, 120, 'Destination "%s" existed already!', Array($theRenameName));
			return FALSE;
		}
		switch ($type) {
			case 'file':
				if (!$this->actionPerms['renameFile']) {
					$this->writelog(5, 1, 102, 'You are not allowed to rename files!', '');
					return FALSE;
				}
				$fI = t3lib_div::split_fileref($theRenameName);
				if (!$this->checkIfAllowed($fI['fileext'], $fileInfo['path'], $fI['file'])) {
					$this->writelog(5, 1, 101, 'Extension of file name "%s" was not allowed!', Array($fI['file']));
					return FALSE;
				}
				if (@rename($theTarget, $theRenameName)) {
					$this->writelog(5, 0, 1, 'File renamed from "%s" to "%s"', Array($fileInfo['file'], $theNewName));
					return $theRenameName;
				} else {
					$this->writelog(5, 1, 100, 'File "%s" was not renamed! Write-permission problem in "%s"?', Array($theTarget, $fileInfo['path']));
					return FALSE;
				}

				break;

			case 'dir':
				if (!$this->actionPerms['renameFolder']) {
					$this->writelog(5, 1, 111, 'You are not allowed to rename directories!', '');
					return FALSE;
				}
				if (@rename($theTarget, $theRenameName)) {
					$this->writelog(5, 0, 2, 'Directory renamed from "%s" to "%s"', Array($fileInfo['file'], $theNewName));
					return $theRenameName;
				} else {
					$this->writelog(5, 1, 110, 'Directory "%s" was not renamed! Write-permission problem in "%s"?', Array($theTarget, $fileInfo['path']));
					return FALSE;
				}

				break;
		}
	}

	/**
	 * This creates a new folder. (action=6)
	 *
	 * @param	array		$cmds['data'] is the foldername. $cmds['target'] is the path where to create it.
	 * @return	string		Returns the new foldername upon success
	 */
	function func_newfolder($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$theFolder = $this->cleanFileName($cmds['data']);
		if (!isset($theFolder) || trim($theFolder) == '') {
			return FALSE;
		}
		if (!$this->checkFileNameLen($theFolder)) {
			$this->writelog(6, 1, 105, 'New name "%s" was too long (max %s characters)', Array($theFolder, $this->maxInputNameLen));
			return FALSE;
		}
		$theTarget = $this->is_directory($cmds['target']); // Check the target dir
		if (!$theTarget) {
			$this->writelog(6, 2, 104, 'Destination "%s" was not a directory', Array($cmds['target']));
			return FALSE;
		}
		if (!$this->actionPerms['newFolder']) {
			$this->writelog(6, 1, 103, 'You are not allowed to create directories!', '');
			return FALSE;
		}
		$theNewFolder = $theTarget . '/' . $theFolder;
		if (!$this->checkPathAgainstMounts($theNewFolder)) {
			$this->writelog(6, 1, 102, 'Destination path "%s" was not within your mountpoints!', Array($theTarget . '/'));
			return FALSE;
		}
		if (file_exists($theNewFolder)) {
			$this->writelog(6, 1, 101, 'File or directory "%s" existed already!', Array($theNewFolder));
			return FALSE;
		}
		if (t3lib_div::mkdir($theNewFolder)) {
			$this->writelog(6, 0, 1, 'Directory "%s" created in "%s"', Array($theFolder, $theTarget . '/'));
			return $theNewFolder;
		} else {
			$this->writelog(6, 1, 100, 'Directory "%s" not created. Write-permission problem in "%s"?', Array($theFolder, $theTarget . '/'));
			return FALSE;
		}
	}

	/**
	 * This creates a new file. (action=8)
	 *
	 * @param	array		$cmds['data'] is the new filename. $cmds['target'] is the path where to create it
	 * @return	string		Returns the new filename upon success
	 */
	function func_newfile($cmds) {
		$extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
		if (!$this->isInit) {
			return FALSE;
		}
		$newName = $this->cleanFileName($cmds['data']);
		if (!$newName) {
			return FALSE;
		}
		if (!$this->checkFileNameLen($newName)) {
			$this->writelog(8, 1, 105, 'New name "%s" was too long (max %s characters)', Array($newName, $this->maxInputNameLen));
			return FALSE;
		}
		$theTarget = $this->is_directory($cmds['target']); // Check the target dir
		$fileInfo = t3lib_div::split_fileref($theTarget); // Fetches info about path, name, extention of $theTarget
		if (!$theTarget) {
			$this->writelog(8, 2, 104, 'Destination "%s" was not a directory', Array($cmds['target']));
			return FALSE;
		}
		if (!$this->actionPerms['newFile']) {
			$this->writelog(8, 1, 103, 'You are not allowed to create files!', '');
			return FALSE;
		}
		$theNewFile = $theTarget . '/' . $newName;
		if (!$this->checkPathAgainstMounts($theNewFile)) {
			$this->writelog(8, 1, 102, 'Destination path "%s" was not within your mountpoints!', Array($theTarget . '/'));
			return FALSE;
		}
		if (file_exists($theNewFile)) {
			$this->writelog(8, 1, 101, 'File "%s" existed already!', Array($theNewFile));
			return FALSE;
		}
		$fI = t3lib_div::split_fileref($theNewFile);
		if (!$this->checkIfAllowed($fI['fileext'], $fileInfo['path'], $fI['file'])) {
			$this->writelog(8, 1, 106, 'Extension of file name "%s" was not allowed!', Array($fI['file']));
			return FALSE;
		}
		if (!t3lib_div::inList($extList, $fI['fileext'])) {
			$this->writelog(8, 1, 107, 'File extension "%s" is not a textfile format! (%s)', Array($fI['fileext'], $extList));
			return FALSE;
		}
		if (t3lib_div::writeFile($theNewFile, '')) {
			clearstatcache();
			$this->writelog(8, 0, 1, 'File created: "%s"', Array($fI['file']));
			return $theNewFile;
		} else {
			$this->writelog(8, 1, 100, 'File "%s" was not created! Write-permission problem in "%s"?', Array($fI['file'], $theTarget));
			return FALSE;
		}
	}

	/**
	 * Editing textfiles or folders (action=9)
	 *
	 * @param	array		$cmds['data'] is the new content. $cmds['target'] is the target (file or dir)
	 * @return	boolean		Returns true on success
	 */
	function func_edit($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}
		$theTarget = $cmds['target'];
		$content = $cmds['data'];
		$extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
		$type = filetype($theTarget);
		if ($type != 'file') { // $type MUST BE file
			$this->writelog(9, 2, 123, 'Target "%s" was not a file!', Array($theTarget));
			return FALSE;
		}
		$fileInfo = t3lib_div::split_fileref($theTarget); // Fetches info about path, name, extention of $theTarget
		$fI = $fileInfo;
		if (!$this->checkPathAgainstMounts($fileInfo['path'])) {
			$this->writelog(9, 1, 121, 'Destination path "%s" was not within your mountpoints!', Array($fileInfo['path']));
			return FALSE;
		}
		if (!$this->actionPerms['editFile']) {
			$this->writelog(9, 1, 104, 'You are not allowed to edit files!', '');
			return FALSE;
		}
		$fI = t3lib_div::split_fileref($theTarget);
		if (!$this->checkIfAllowed($fI['fileext'], $fileInfo['path'], $fI['file'])) {
			$this->writelog(9, 1, 103, 'Extension of file name "%s" was not allowed!', Array($fI['file']));
			return FALSE;
		}
		if (!t3lib_div::inList($extList, $fileInfo['fileext'])) {
			$this->writelog(9, 1, 102, 'File extension "%s" is not a textfile format! (%s)', Array($fI['fileext'], $extList));
			return FALSE;
		}
		if (t3lib_div::writeFile($theTarget, $content)) {
			clearstatcache();
			$this->writelog(9, 0, 1, 'File saved to "%s", bytes: %s, MD5: %s ', Array($fileInfo['file'], @filesize($theTarget), md5($content)));
			return TRUE;
		} else {
			$this->writelog(9, 1, 100, 'File "%s" was not saved! Write-permission problem in "%s"?', Array($theTarget, $fileInfo['path']));
			return FALSE;
		}
	}

	/**
	 * Upload of files (action=1)
	 *
	 * @param	array		$cmds['data'] is the ID-number (points to the global var that holds the filename-ref  ($_FILES['upload_'.$id]['name']). $cmds['target'] is the target directory, $cmds['charset'] is the the character set of the file name (utf-8 is needed for JS-interaction)
	 * @return	string		Returns the new filename upon success
	 */
	function func_upload($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}
		$id = $cmds['data'];
		if (!$_FILES['upload_' . $id]['name']) {
			$this->writelog(1, 2, 108, 'No file was uploaded!', '');
			return FALSE;
		}
		$theFile = $_FILES['upload_' . $id]['tmp_name']; // filename of the uploaded file
		$theFileSize = $_FILES['upload_' . $id]['size']; // filesize of the uploaded file
		$theName = $this->cleanFileName(stripslashes($_FILES['upload_' . $id]['name']), (isset($cmds['charset']) ? $cmds['charset'] : '')); // The original filename
		if (!is_uploaded_file($theFile) || !$theName) { // Check the file
			$this->writelog(1, 2, 106, 'The upload has failed, no uploaded file found!', '');
			return FALSE;
		}
		if (!$this->actionPerms['uploadFile']) {
			$this->writelog(1, 1, 105, 'You are not allowed to upload files!', '');
			return FALSE;
		}
		if ($theFileSize >= ($this->maxUploadFileSize * 1024)) {
			$this->writelog(1, 1, 104, 'The uploaded file exceeds the size-limit of %s bytes', Array($this->maxUploadFileSize * 1024));
			return FALSE;
		}
		$fI = t3lib_div::split_fileref($theName);
		$theTarget = $this->is_directory($cmds['target']); // Check the target dir
		if (!$theTarget || !$this->checkPathAgainstMounts($theTarget . '/')) {
			$this->writelog(1, 1, 103, 'Destination path "%s" was not within your mountpoints!', Array($theTarget . '/'));
			return FALSE;
		}
		if (!$this->checkIfAllowed($fI['fileext'], $theTarget, $fI['file'])) {
			$this->writelog(1, 1, 102, 'Extension of file name "%s" is not allowed in "%s"!', Array($fI['file'], $theTarget . '/'));
			return FALSE;
		}
		$theNewFile = $this->getUniqueName($theName, $theTarget, $this->dontCheckForUnique);
		if (!$theNewFile) {
			$this->writelog(1, 1, 101, 'No unique filename available in "%s"!', Array($theTarget . '/'));
			return FALSE;
		}
		t3lib_div::upload_copy_move($theFile, $theNewFile);
		clearstatcache();
		if (@is_file($theNewFile)) {
			$this->internalUploadMap[$id] = $theNewFile;
			$this->writelog(1, 0, 1, 'Uploading file "%s" to "%s"', Array($theName, $theNewFile, $id));
			return $theNewFile;
		} else {
			$this->writelog(1, 1, 100, 'Uploaded file could not be moved! Write-permission problem in "%s"?', Array($theTarget . '/'));
			return FALSE;
		}
	}

	/**
	 * Unzipping file (action=7)
	 * This is permitted only if the user has fullAccess or if the file resides
	 *
	 * @param	array		$cmds['data'] is the zip-file. $cmds['target'] is the target directory. If not set we'll default to the same directory as the file is in.
	 * @return	boolean		Returns true on success
	 */
	function func_unzip($cmds) {
		if (!$this->isInit || $this->dont_use_exec_commands) {
			return FALSE;
		}

		$theFile = $cmds['data'];
		if (!@is_file($theFile)) {
			$this->writelog(7, 2, 105, 'The file "%s" did not exist!', Array($theFile));
			return FALSE;
		}
		$fI = t3lib_div::split_fileref($theFile);
		if (!isset($cmds['target'])) {
			$cmds['target'] = $fI['path'];
		}
		$theDest = $this->is_directory($cmds['target']); // Clean up destination directory
		if (!$theDest) {
			$this->writelog(7, 2, 104, 'Destination "%s" was not a directory', Array($cmds['target']));
			return FALSE;
		}
		if (!$this->actionPerms['unzipFile']) {
			$this->writelog(7, 1, 103, 'You are not allowed to unzip files', '');
			return FALSE;
		}
		if ($fI['fileext'] != 'zip') {
			$this->writelog(7, 1, 102, 'File extension is not "zip"', '');
			return FALSE;
		}
		if (!$this->checkIfFullAccess($theDest)) {
			$this->writelog(7, 1, 101, 'You don\'t have full access to the destination directory "%s"!', Array($theDest));
			return FALSE;
		}
		if ($this->checkPathAgainstMounts($theFile) && $this->checkPathAgainstMounts($theDest . '/')) {
			// No way to do this under windows.
			$cmd = $this->unzipPath . 'unzip -qq "' . $theFile . '" -d "' . $theDest . '"';
			t3lib_utility_Command::exec($cmd);
			$this->writelog(7, 0, 1, 'Unzipping file "%s" in "%s"', Array($theFile, $theDest));
			return TRUE;
		} else {
			$this->writelog(7, 1, 100, 'File "%s" or destination "%s" was not within your mountpoints!', Array($theFile, $theDest));
			return FALSE;
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extfilefunc.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extfilefunc.php']);
}

?>