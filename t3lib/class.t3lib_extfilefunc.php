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
 * Extending class to class t3lib_basicFileFunctions
 *
 * Revised for TYPO3 3.6 May/2004 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
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
 *	 ... All fileoperations must be within the filemount-paths. Further the fileextension MUST validate TRUE with the f_ext array
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
	var $unzipPath = ''; // Path to unzip-program (with trailing '/')
	var $dontCheckForUnique = 0; // If set, the uploaded files will overwrite existing files.

	var $actionPerms = array( // This array is self-explaning (look in the class below). It grants access to the functions. This could be set from outside in order to enabled functions to users. See also the function init_actionPerms() which takes input directly from the user-record
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

	/**
	 * Whether to use recycler (0 = no, 1 = if available, 2 = always)
	 *
	 * @var integer
	 * @deprecated since TYPO3 6.0
	 */
	var $useRecycler = 1;

		// Internal, dynamic:
	var $internalUploadMap = array(); // Will contain map between upload ID and the final filename

	var $lastError = '';

	/**
	 * @var array
	 */
	protected $fileCmdMap;

	/**
	 * The File Factory
	 *
	 * @var t3lib_file_Factory
	 */
	protected $fileFactory;

	/**
	 * Initialization of the class
	 *
	 * @param array $fileCmds Array with the commands to execute. See "TYPO3 Core API" document
	 * @return void
	 */
	function start($fileCmds) {

		$unzipPath = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['unzip_path']);
		if (substr($unzipPath, -1) !== '/' && is_dir($unzipPath)) {
			// Make sure the path ends with a slash
			$unzipPath.= '/';
		}
		$this->unzipPath = $unzipPath;

			// Initialize Object Factory
		$this->fileFactory = t3lib_file_Factory::getInstance();

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
	 * @return	mixed	FALSE, if the file functions were not initialized
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
						. ' AND error<>0'
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
	 * @return	string		Returns the path (without trailing slash) of the closest recycle-folder if found. Else FALSE.
	 *
	 * @todo To be put in Storage with a better concept
	 * @deprecated since TYPO3 6.0, use t3lib_file_Storage method instead
	 */
	function findRecycler($theFile) {
		t3lib_div::logDeprecatedFunction();

		if ($this->isPathValid($theFile)) {
			$theFile = $this->cleanDirectoryName($theFile);
			$fI = t3lib_div::split_fileref($theFile);
			$c = 0;
				// !!! Method has been put in the storage, can be saftely removed
				$rDir = $fI['path'] . $this->recyclerFN;
			while ($this->checkPathAgainstMounts($fI['path']) && $c < 20) {
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
	 * @return	boolean		Returns TRUE upon success
	 */
	function func_delete($cmds) {
		$result = FALSE;

		if (!$this->isInit) {
			return $result;
		}

			// Example indentifier for $cmds['data'] => "4:mypath/tomyfolder/myfile.jpg"
			// for backwards compatibility: the combined file identifier was the path+filename
		$fileObject = $this->getFileObject($cmds['data']);

			// @todo implement the recycler feature which has been removed from the original implementation
			// $this->writelog(4, 0, 4, 'Item "%s" moved to recycler at "%s"', array($theFile, $recyclerPath));

			// Copies the file
		if ($fileObject instanceof t3lib_file_File) {
			$refIndexRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_refindex',
				"ref_table='sys_file' AND ref_uid=" . $fileObject->getUid()
			);

			if (count($refIndexRecords) > 0) {
				$shortcutContent = array();

				foreach ($refIndexRecords as $row) {
					$shortcutRecord = NULL;
					$shortcutRecord = t3lib_BEfunc::getRecord($row['tablename'], $row['recuid']);
					if (is_array($shortcutRecord) && $row['tablename'] !== 'sys_file_reference') {
						$icon = t3lib_iconWorks::getSpriteIconForRecord($row['tablename'], $shortcutRecord);

						$onClick = 'showClickmenu("' . $row['tablename'] . '", "' . $row['recuid'] . '", "1", "+info,history,edit,delete", "|", "");return false;';
						$shortcutContent[] = '<a href="#" oncontectmenu="' . htmlspecialchars($onClick) . '" onclick="' . htmlspecialchars($onClick) . '">' . $icon . '</a>' .
							htmlspecialchars(t3lib_BEfunc::getRecordTitle($row['tablename'], $shortcutRecord) . '  [' . t3lib_BEfunc::getRecordPath($shortcutRecord['pid'], '', 80) . ']');
					}
				}

				$out = '<p>The file cannot be deleted since it is still used at the following places:<br />' .
						implode('<br />', $shortcutContent) . '</p>';

				$flashMessage = t3lib_div::makeInstance(
					't3lib_flashMessage',
					$out,
					'File not deleted',
					t3lib_FlashMessage::WARNING,
					TRUE
				);
				t3lib_FlashMessageQueue::addMessage($flashMessage);
				return;

			} else {
				try {
					$result = $fileObject->delete();
				} catch (t3lib_file_exception_InsufficientFileAccessPermissionsException $e) {
					$this->writelog(4, 1, 112, 'You are not allowed to access the file', array($fileObject->getIdentifier()));
				} catch (t3lib_file_exception_NotInMountPointException $e) {
					$this->writelog(4, 1, 111, 'Target was not within your mountpoints! T="%s"', array($fileObject->getIdentifier()));
				} catch (RuntimeException $e) {
					$this->writelog(4, 1, 110, 'Could not delete file "%s". Write-permission problem?', array($fileObject->getIdentifier()));
				}
					// Log success
				$this->writelog(4, 0, 1, 'File "%s" deleted', array($fileObject->getIdentifier()));
			}
			// Working on a folder
		} else {
			try {
				/** @var $fileObject t3lib_file_FolderInterface */
				$result = $fileObject->delete(TRUE);
			} catch (t3lib_file_exception_InsufficientFileAccessPermissionsException $e) {
				$this->writelog(4, 1, 123, 'You are not allowed to access the directory', array($fileObject->getIdentifier()));
			} catch (t3lib_file_exception_NotInMountPointException $e) {
				$this->writelog(4, 1, 121, 'Target was not within your mountpoints! T="%s"', array($fileObject->getIdentifier()));
			} catch (RuntimeException $e) {
				$this->writelog(4, 1, 120, 'Could not delete directory! Write-permission problem? Is directory "%s" empty? (You are not allowed to delete directories recursively).', array($fileObject->getIdentifier()));
			}

				// Log success
			$this->writelog(4, 0, 3, 'Directory "%s" deleted', array($fileObject->getIdentifier()));
		}

		return $result;
	}

	/**
	 * Gets a File or a Folder object from an identifier [storage]:[fileId]
	 *
	 * @param string $identifier
	 * @return t3lib_file_Folder|t3lib_file_File
	 */
	protected function getFileObject($identifier) {
		$object = $this->fileFactory->retrieveFileOrFolderObject($identifier);

		if (!is_object($object)) {
			throw new t3lib_file_exception_InvalidFileException(
				'The item ' . $identifier . ' was not a file or directory!!',
				1320122453
			);
		}

		return $object;
	}

	/**
	 * Copying files and folders (action=2)
	 *
	 * $cmds['data'] (string): The file/folder to copy
	 * + example "4:mypath/tomyfolder/myfile.jpg")
	 * + for backwards compatibility: the identifier was the path+filename
	 * $cmds['target'] (string): The path where to copy to.
	 * + example "2:targetpath/targetfolder/"
	 * $cmds['altName'] (string): Use an alternative name if the target already exists
	 *
	 * @param array $cmds Command details as described above
	 * @return t3lib_file_File
	 */
	protected function func_copy($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$sourceFileObject = $this->getFileObject($cmds['data']);
		/** @var $targetFolderObject t3lib_file_Folder */
		$targetFolderObject = $this->getFileObject($cmds['target']);

			// basic check
		if (!($targetFolderObject instanceof t3lib_file_Folder)) {
			$this->writelog(2, 2, 100, 'Destination "%s" was not a directory', array($cmds['target']));
			return FALSE;
		}

			// if this is TRUE, we append _XX to the file name if
		$appendSuffixOnConflict = (string) $cmds['altName'];
		$resultObject = NULL;

			// copying the file
		if ($sourceFileObject instanceof t3lib_file_File) {
			try {
				$conflictMode = ($appendSuffixOnConflict !== '') ? 'renameNewFile' : 'cancel';
				$resultObject = $sourceFileObject->copyTo($targetFolderObject, NULL, $conflictMode);
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(2, 1, 114, 'You are not allowed to copy files', '');
			} catch (t3lib_file_exception_InsufficientFileAccessPermissionsException $e) {
				$this->writelog(2, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_IllegalFileExtensionException $e) {
				$this->writelog(2, 1, 111, 'Extension of file name "%s" is not allowed in "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_ExistingTargetFileNameException $e) {
				$this->writelog(2, 1, 112, 'File "%s" already exists in folder "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (RuntimeException $e) {
				$this->writelog(2, 2, 109, 'File "%s" WAS NOT copied to "%s"! Write-permission problem?', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			}

			$this->writelog(2, 0, 1, 'File "%s" copied to "%s"', array($sourceFileObject->getIdentifier(), $resultObject->getIdentifier()));

		} else { // Else means this is a Folder
			$sourceFolderObject = $sourceFileObject;

			try {
				$conflictMode = ($appendSuffixOnConflict !== '') ? 'renameNewFile' : 'cancel';
				$resultObject = $sourceFolderObject->copyTo($targetFolderObject, NULL, $conflictMode);
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(2, 1, 125, 'You are not allowed to copy directories', '');
			} catch (t3lib_file_exception_InsufficientFileAccessPermissionsException $e) {
				$this->writelog(2, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_InsufficientFolderAccessPermissionsException $e) {
				$this->writelog(2, 1, 121, 'You don\'t have full access to the destination directory "%s"!', array($targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_InvalidTargetFolderException $e) {
				$this->writelog(2, 1, 122, 'Destination cannot be inside the target! D="%s", T="%s"', array($targetFolderObject->getIdentifier(), $sourceFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_ExistingTargetFolderException $e) {
				$this->writelog(2, 1, 123, 'Target "%s" already exists!', array($targetFolderObject->getIdentifier()));
			} catch (RuntimeException $e) {
				$this->writelog(2, 2, 119, 'Directory "%s" WAS NOT copied to "%s"! Write-permission problem?', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			}

			$this->writelog(2, 0, 2, 'Directory "%s" copied to "%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
		}

		return $resultObject;
	}

	/**
	 * Moving files and folders (action=3)
	 *
	 * $cmds['data'] (string): The file/folder to move
	 * + example "4:mypath/tomyfolder/myfile.jpg")
	 * + for backwards compatibility: the identifier was the path+filename
	 * $cmds['target'] (string): The path where to move to.
	 * + example "2:targetpath/targetfolder/"
	 * $cmds['altName'] (string): Use an alternative name if the target already exists
	 *
	 * @param array $cmds Command details as described above
	 * @return t3lib_file_File
	 */
	protected function func_move($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$sourceFileObject = $this->getFileObject($cmds['data']);
		$targetFolderObject = $this->getFileObject($cmds['target']);

			// basic check
		if (!($targetFolderObject instanceof t3lib_file_Folder)) {
			$this->writelog(3, 2, 100, 'Destination "%s" was not a directory', array($cmds['target']));
			return FALSE;
		}

		$alternativeName = (string) $cmds['altName'];
		$resultObject = NULL;

			// moving the file
		if ($sourceFileObject instanceof t3lib_file_File) {
			try {
				if ($alternativeName !== '') {
						// don't allow overwriting existing files, but find a new name
					$resultObject = $sourceFileObject->moveTo($targetFolderObject, $alternativeName, 'renameNewFile');
				} else {
						// don't allow overwriting existing files
					$resultObject = $sourceFileObject->moveTo($targetFolderObject, NULL, 'cancel');
				}
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(3, 1, 114, 'You are not allowed to move files', '');
			} catch (t3lib_file_exception_InsufficientFileAccessPermissionsException $e) {
				$this->writelog(3, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_IllegalFileExtensionException $e) {
				$this->writelog(3, 1, 111, 'Extension of file name "%s" is not allowed in "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_ExistingTargetFileNameException $e) {
				$this->writelog(3, 1, 112, 'File "%s" already exists in folder "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (RuntimeException $e) {
				$this->writelog(3, 2, 109, 'File "%s" WAS NOT copied to "%s"! Write-permission problem?', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			}

			$this->writelog(3, 0, 1, 'File "%s" moved to "%s"', array($sourceFileObject->getIdentifier(), $resultObject->getIdentifier()));

		} else { // Else means this is a Folder
			$sourceFolderObject = $sourceFileObject;

			try {
				if ($alternativeName !== '') {
						// don't allow overwriting existing files, but find a new name
					$resultObject = $sourceFolderObject->moveTo($targetFolderObject, $alternativeName, 'renameNewFile');
				} else {
						// don't allow overwriting existing files
					$resultObject = $sourceFolderObject->moveTo($targetFolderObject, NULL, 'renameNewFile');
				}
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(3, 1, 125, 'You are not allowed to move directories', '');
			} catch (t3lib_file_exception_InsufficientFileAccessPermissionsException $e) {
				$this->writelog(3, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_InsufficientFolderAccessPermissionsException $e) {
				$this->writelog(3, 1, 121, 'You don\'t have full access to the destination directory "%s"!', array($targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_InvalidTargetFolderException $e) {
				$this->writelog(3, 1, 122, 'Destination cannot be inside the target! D="%s", T="%s"', array($targetFolderObject->getIdentifier(), $sourceFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_ExistingTargetFolderException $e) {
				$this->writelog(3, 1, 123, 'Target "%s" already exists!', array($targetFolderObject->getIdentifier()));
			} catch (RuntimeException $e) {
				$this->writelog(3, 2, 119, 'Directory "%s" WAS NOT moved to "%s"! Write-permission problem?', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
			}

			$this->writelog(3, 0, 2, 'Directory "%s" moved to "%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
		}

		return $resultObject;
	}

	/**
	 * Renaming files or foldes (action=5)
	 *
	 * $cmds['data'] (string): The file/folder to copy
	 * + example "4:mypath/tomyfolder/myfile.jpg")
	 * + for backwards compatibility: the identifier was the path+filename
	 * $cmds['target'] (string): New name of the file/folder
	 *
	 * @param array $cmds Command details as described above
	 * @return t3lib_file_File Returns the new file upon success
	 */
	function func_rename($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$sourceFileObject = $this->getFileObject($cmds['data']);
		$targetFile = $cmds['target'];
		$resultObject = NULL;

		if ($sourceFileObject instanceof t3lib_file_File) {
			try {
					// Try to rename the File
				$resultObject = $sourceFileObject->rename($targetFile);
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(5, 1, 102, 'You are not allowed to rename files!', '');
			} catch (t3lib_file_exception_IllegalFileExtensionException $e) {
				$this->writelog(5, 1, 101, 'Extension of file name "%s" was not allowed!', array($targetFile));
			} catch (t3lib_file_exception_ExistingTargetFileNameException $e) {
				$this->writelog(5, 1, 120, 'Destination "%s" existed already!', array($targetFile));
			} catch (t3lib_file_exception_NotInMountPointException $e) {
				$this->writelog(5, 1, 121, 'Destination path "%s" was not within your mountpoints!', array($targetFile));
			} catch (RuntimeException $e) {
					$this->writelog(5, 1, 100, 'File "%s" was not renamed! Write-permission problem in "%s"?', array($sourceFileObject->getName(), $targetFile));
			}
			$this->writelog(5, 0, 1, 'File renamed from "%s" to "%s"', array($sourceFileObject->getName(), $targetFile));

		} else { // Else means this is a Folder
			try {
					// Try to rename the Folder
				$resultObject = $sourceFileObject->rename($targetFile);
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(5, 1, 111, 'You are not allowed to rename directories!', '');
			} catch (t3lib_file_exception_ExistingTargetFileNameException $e) {
				$this->writelog(5, 1, 120, 'Destination "%s" existed already!', array($targetFile));
			} catch (t3lib_file_exception_NotInMountPointException $e) {
				$this->writelog(5, 1, 121, 'Destination path "%s" was not within your mountpoints!', array($targetFile));
			} catch (RuntimeException $e) {
					$this->writelog(5, 1, 110, 'Directory "%s" was not renamed! Write-permission problem in "%s"?', array($sourceFileObject->getName(), $targetFile));
			}
			$this->writelog(5, 0, 2, 'Directory renamed from "%s" to "%s"', array($sourceFileObject->getName(), $targetFile));
		}

		return $resultObject;
	}

	/**
	 * This creates a new folder. (action=6)
	 *
	 * $cmds['data'] (string): The new folder name
	 * $cmds['target'] (string): The path where to copy to.
	 * + example "2:targetpath/targetfolder/"
	 *
	 * @param array $cmds Command details as described above
	 * @return t3lib_file_Folder Returns the new foldername upon success
	 */
	function func_newfolder($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$targetFolderObject = $this->getFileObject($cmds['target']);

		if (!($targetFolderObject instanceof t3lib_file_Folder)) {
			$this->writelog(6, 2, 104, 'Destination "%s" was not a directory', array($cmds['target']));
			return FALSE;
		}

		$resultObject = NULL;

		try {
			$folderName = $cmds['data'];
			$resultObject = $targetFolderObject->createFolder($folderName);
			$this->writelog(6, 0, 1, 'Directory "%s" created in "%s"', array($folderName, $targetFolderObject->getIdentifier() . '/'));
		} catch (t3lib_file_exception_InsufficientFolderWritePermissionsException $e) {
			$this->writelog(6, 1, 103, 'You are not allowed to create directories!', '');
		} catch (t3lib_file_exception_NotInMountPointException $e) {
			$this->writelog(6, 1, 102, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier() . '/'));
		} catch (t3lib_file_exception_ExistingTargetFolderException $e) {
			$this->writelog(6, 1, 101, 'File or directory "%s" existed already!', array($folderName));
		} catch (RuntimeException $e) {
			$this->writelog(6, 1, 100, 'Directory "%s" not created. Write-permission problem in "%s"?', array($folderName, $targetFolderObject->getIdentifier() . '/'));
		}

		return $resultObject;
	}

	/**
	 * This creates a new file. (action=8)
	 * $cmds['data'] (string): The new file name
	 * $cmds['target'] (string): The path where to create it.
	 * + example "2:targetpath/targetfolder/"
	 *
	 * @param array $cmds Command details as described above
	 * @return	string		Returns the new filename upon success
	 */
	function func_newfile($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$targetFolderObject = $this->getFileObject($cmds['target']);

		if (!($targetFolderObject instanceof t3lib_file_Folder)) {
			$this->writelog(8, 2, 104, 'Destination "%s" was not a directory', array($cmds['target']));
			return FALSE;
		}

		$resultObject = NULL;

		try {
			$fileName = $cmds['data'];
			$resultObject = $targetFolderObject->createFile($fileName);
			$this->writelog(8, 0, 1, 'File created: "%s"', array($fileName));
		} catch (t3lib_file_exception_InsufficientFolderWritePermissionsException $e) {
			$this->writelog(8, 1, 103, 'You are not allowed to create files!', '');
		} catch (t3lib_file_exception_NotInMountPointException $e) {
			$this->writelog(8, 1, 102, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier()));
		} catch (t3lib_file_exception_ExistingTargetFileNameException $e) {
			$this->writelog(8, 1, 101, 'File existed already in "%s"!', array($targetFolderObject->getIdentifier()));
		} catch (t3lib_file_exception_InvalidFileNameException $e) {
			$this->writelog(8, 1, 106, 'File name "%s" was not allowed!', $fileName);
		} catch (RuntimeException $e) {
			$this->writelog(8, 1, 100, 'File "%s" was not created! Write-permission problem in "%s"?', array($fileName, $targetFolderObject->getIdentifier()));
		}

		return $resultObject;
	}

	/**
	 * Editing textfiles or folders (action=9)
	 *
	 * @param	array		$cmds['data'] is the new content. $cmds['target'] is the target (file or dir)
	 * @return	boolean		Returns TRUE on success
	 */
	function func_edit($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

			// Example indentifier for $cmds['target'] => "4:mypath/tomyfolder/myfile.jpg"
			// for backwards compatibility: the combined file identifier was the path+filename
		$fileIdentifier = $cmds['target'];
		$fileObject = $this->getFileObject($fileIdentifier);

			// Example indentifier for $cmds['target'] => "2:targetpath/targetfolder/"
		$content = $cmds['data'];

		if (!$fileObject instanceof t3lib_file_File) {
			$this->writelog(9, 2, 123, 'Target "%s" was not a file!', array($fileIdentifier));
			return FALSE;
		}

		$extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
		if (!t3lib_div::inList($extList, $fileObject->getExtension())) {
			$this->writelog(9, 1, 102, 'File extension "%s" is not a textfile format! (%s)', array($fileObject->getExtension(), $extList));
			return FALSE;
		}

		try {
			$fileObject->setContents($content);
			clearstatcache();
			$this->writelog(9, 0, 1, 'File saved to "%s", bytes: %s, MD5: %s ', array($fileObject->getIdentifier(), $fileObject->getSize(), md5($content)));
			return TRUE;
		} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
			$this->writelog(9, 1, 104, 'You are not allowed to edit files!', '');
			return FALSE;
		} catch (t3lib_file_exception_InsufficientFileWritePermissionsException $e) {
			$this->writelog(9, 1, 100, 'File "%s" was not saved! Write-permission problem?', array($fileObject->getIdentifier()));
			return FALSE;
		}
	}

	/**
	 * Upload of files (action=1)
	 * when having multiple uploads (HTML5-style), the array $_FILES looks like this:
	 *	Array(
	 *		[upload_1] => Array(
	 *				[name] => Array(
	 *						[0] => GData - Content-Elemente und Media-Gallery.pdf
	 *						[1] => CMS Expo 2011.txt
	 *					)
	 *				[type] => Array(
	 *						[0] => application/pdf
	 *						[1] => text/plain
	 *					)
	 *				[tmp_name] => Array(
	 *						[0] => /Applications/MAMP/tmp/php/phpNrOB43
	 *						[1] => /Applications/MAMP/tmp/php/phpD2HQAK
	 *					)
	 *				[size] => Array(
	 *						[0] => 373079
	 *						[1] => 1291
	 *					)
	 *			)
	 *	)
	 * in HTML you'd need sth like this: <input type="file" name="upload_1[]" multiple="true" />
	 *
	 * @param	array		$cmds['data'] is the ID-number (points to the global var that holds the filename-ref  ($_FILES['upload_'.$id]['name']). $cmds['target'] is the target directory, $cmds['charset'] is the the character set of the file name (utf-8 is needed for JS-interaction)
	 * @return	string		Returns the new filename upon success
	 */
	function func_upload($cmds) {
		if (!$this->isInit) {
			return FALSE;
		}

		$uploadPosition = $cmds['data'];
		$uploadedFileData = $_FILES['upload_' . $uploadPosition];

		if (empty($uploadedFileData['name']) || (is_array($uploadedFileData['name']) && empty($uploadedFileData['name'][0]))) {
			$this->writelog(1, 2, 108, 'No file was uploaded!', '');
			return FALSE;
		}

			// Example indentifier for $cmds['target'] => "2:targetpath/targetfolder/"
		$targetFolderObject = $this->getFileObject($cmds['target']);

			// uploading with non HTML-5-style, thus, make an array out of it, so we can loop over it
		if (!is_array($uploadedFileData['name'])) {
			$uploadedFileData = array(
				'name' => array($uploadedFileData['name']),
				'type' => array($uploadedFileData['type']),
				'tmp_name' => array($uploadedFileData['tmp_name']),
				'size' => array($uploadedFileData['size']),
			);
		}

		$resultObjects = array();

		$numberOfUploadedFilesForPosition = count($uploadedFileData['name']);
			// loop through all uploaded files
		for ($i = 0; $i < $numberOfUploadedFilesForPosition; $i++) {
			$fileInfo = array(
				'name'     => $uploadedFileData['name'][$i],
				'type'     => $uploadedFileData['type'][$i],
				'tmp_name' => $uploadedFileData['tmp_name'][$i],
				'size'     => $uploadedFileData['size'][$i],
			);
			try {

					// @todo can be improved towards conflict mode naming
				if ($this->dontCheckForUnique) {
					$conflictMode = 'replace';
				} else {
					$conflictMode = 'cancel';
				}
				$resultObjects[] = $targetFolderObject->addUploadedFile($fileInfo, $conflictMode);
				$this->writelog(1, 0, 1, 'Uploading file "%s" to "%s"', array($fileInfo['name'], $targetFolderObject->getIdentifier()));

			} catch (t3lib_file_exception_UploadException $e) {
				$this->writelog(1, 2, 106, 'The upload has failed, no uploaded file found!', '');
			} catch (t3lib_file_exception_InsufficientUserPermissionsException $e) {
				$this->writelog(1, 1, 105, 'You are not allowed to upload files!', '');
			} catch (t3lib_file_exception_UploadSizeException $e) {
				$this->writelog(1, 1, 104, 'The uploaded file "%s" exceeds the size-limit', array($fileInfo['name']));
			} catch (t3lib_file_exception_InsufficientFolderWritePermissionsException $e) {
				$this->writelog(1, 1, 103, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_IllegalFileExtensionException $e) {
				$this->writelog(1, 1, 102, 'Extension of file name "%s" is not allowed in "%s"!', array($fileInfo['name'], $targetFolderObject->getIdentifier()));
			} catch (t3lib_file_exception_ExistingTargetFileNameException $e) {
				$this->writelog(1, 1, 101, 'No unique filename available in "%s"!', array($targetFolderObject->getIdentifier()));
			} catch (RuntimeException $e) {
				$this->writelog(1, 1, 100, 'Uploaded file could not be moved! Write-permission problem in "%s"?', array($targetFolderObject->getIdentifier()));
			}
		}

		return $resultObjects;
	}

	/**
	 * Unzipping file (action=7)
	 * This is permitted only if the user has fullAccess or if the file resides
	 *
	 * @param	array		$cmds['data'] is the zip-file. $cmds['target'] is the target directory. If not set we'll default to the same directory as the file is in.
	 * @return	boolean		Returns TRUE on success
	 */
	function func_unzip($cmds) {
		if (!$this->isInit || $this->dont_use_exec_commands) {
			return FALSE;
		}

		$theFile = $cmds['data'];
		if (!@is_file($theFile)) {
			$this->writelog(7, 2, 105, 'The file "%s" did not exist!', array($theFile));
			return FALSE;
		}
		$fI = t3lib_div::split_fileref($theFile);
		if (!isset($cmds['target'])) {
			$cmds['target'] = $fI['path'];
		}
			// Clean up destination directory
			// !!! Method has been put in the local driver, can be saftely removed
		$theDest = $this->is_directory($cmds['target']);
		if (!$theDest) {
			$this->writelog(7, 2, 104, 'Destination "%s" was not a directory', array($cmds['target']));
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
			$this->writelog(7, 1, 101, 'You don\'t have full access to the destination directory "%s"!', array($theDest));
			return FALSE;
		}
			// !!! Method has been put in the sotrage driver, can be saftely removed
		if ($this->checkPathAgainstMounts($theFile) && $this->checkPathAgainstMounts($theDest . '/')) {
				// No way to do this under windows.
			$cmd = $this->unzipPath . 'unzip -qq ' . escapeshellarg($theFile) . ' -d ' . escapeshellarg($theDest);
			t3lib_utility_Command::exec($cmd);
			$this->writelog(7, 0, 1, 'Unzipping file "%s" in "%s"', array($theFile, $theDest));
			return TRUE;
		} else {
			$this->writelog(7, 1, 100, 'File "%s" or destination "%s" was not within your mountpoints!', array($theFile, $theDest));
			return FALSE;
		}
	}
}

?>
