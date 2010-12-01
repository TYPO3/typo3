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
 * File Abtraction Layer dataprovider for filelists
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id:  $
 */

class tx_fal_list_DataProvider {

	/**
	 * generates a file tree
	 *
	 * @todo: <rupert.germann>, 01.12.2010: should be in storage backend
	 *
	 * @param object $parameters
	 * @return array
	 */
	public function getExtFileTree($parameter) {
		$ext = array();
		$fileArray = array();
		$filemounts = $GLOBALS['BE_USER']->returnFilemounts();
		$filepermissions = $GLOBALS['BE_USER']->getFileoperationPermissions();
		// check be user file permissions
		$actionPerms['uploadFile'] = FALSE;
		$actionPerms['copyFile'] = FALSE;
		$actionPerms['moveFile'] = FALSE;
		$actionPerms['deleteFile'] = FALSE;
		$actionPerms['renameFile'] = FALSE;
		$actionPerms['editFile'] = FALSE;
		$actionPerms['newFile'] = FALSE;
		$actionPerms['unzipFile'] = FALSE;
		$actionPerms['moveFolder'] = FALSE;
		$actionPerms['deleteFolder'] = FALSE;
		$actionPerms['renameFolder'] = FALSE;
		$actionPerms['newFolder'] = FALSE;
		$actionPerms['copyFolder'] = FALSE;
		$actionPerms['deleteFolderRecursively'] = FALSE;


		if (($filepermissions &1) == 1)	{		// Files: Upload,Copy,Move,Delete,Rename
			$actionPerms['uploadFile'] = TRUE;
			$actionPerms['copyFile'] = TRUE;
			$actionPerms['moveFile'] = TRUE;
			$actionPerms['deleteFile'] = TRUE;
			$actionPerms['renameFile'] = TRUE;
			$actionPerms['editFile'] = TRUE;
			$actionPerms['newFile'] = TRUE;
		}
		if (($filepermissions &2) == 2)	{		// Files: Unzip
			$actionPerms['unzipFile'] = TRUE;
		}
		if (($filepermissions &4) == 4)	{		// Directory: Move,Delete,Rename,New
			$actionPerms['moveFolder'] = TRUE;
			$actionPerms['deleteFolder'] = TRUE;
			$actionPerms['renameFolder'] = TRUE;
			$actionPerms['newFolder'] = TRUE;
		}
		if (($filepermissions &8) == 8)	{		// Directory: Copy
			$actionPerms['copyFolder'] = TRUE;
		}
		if (($filepermissions &16) == 16)	{		// Directory: Delete recursively (rm -Rf)
			$actionPerms['deleteFolderRecursively'] = TRUE;
		}

		if($parameter->node === 'FILE_MOUNTS') {
			foreach ($filemounts as $mountIdent => $mountInfo) {
				$icon = 't3-icon t3-icon-apps t3-icon-apps-filetree t3-icon-filetree-mount';
				if ($mountInfo['path'] == PATH_site.'fileadmin/') {
					$icon = 't3-icon t3-icon-apps t3-icon-apps-pagetree t3-icon-pagetree-root';
				}
				$fileArray[] = array(
					'id' => $mountInfo['path'],
					'text' => htmlspecialchars($mountInfo['name']),
					'leaf' => FALSE,
					'permissions' => $actionPerms,
					'qtip' => '',
					'iconCls' => $icon,
				);
			}
		} else {
			$path = preg_replace('|/$|', '', $parameter->node);
			$dirs = t3lib_div::get_dirs($path);
			foreach ($dirs as $dir) {
				if ($dir{0} !== '.') {
					$fileArray[] = array(
						'id' => $path . '/' . $dir . '/',
						'text' => htmlspecialchars($dir),
						'leaf' => FALSE,
						'permissions' => $actionPerms,
						'qtip' => '',
						'iconCls' => 't3-icon t3-icon-apps t3-icon-apps-filetree t3-icon-filetree-folder-temp'
					);
				}
			}
		}
		return $fileArray;
	}


	/**
	 * returns all files and folders in a given path
	 *
	 * @todo: <rupert.germann>, 01.12.2010: should be in storage backend
	 *
	 * @param	object		$parameters		object of parameters
	 * @return	array		Array of file arrays
	 */
	public function getAllInPath($parameters){
		$files = t3lib_div::getFilesInDir($parameters->path, '', TRUE, '', '');
		$folders = t3lib_div::get_dirs($parameters->path);
		if (!is_array($folders)) {
			$folders = array();
		}
		$filesArray = array();
		foreach ($folders as $folder){
			if ($folder{0} !== '.') {
				$filesArray[] = array(
					'sys_files_id' => $parameters->path . $folder,
					'file_name' => $folder,
					'file_type' => 'DIR'
				);
			}
		}

		/** @var $repo tx_fal_Repository */
		$repo = t3lib_div::makeInstance('tx_fal_Repository');

		foreach ($files as $file) {
			$mount = tx_fal_Helper::getMountFromFilePath(dirname($file));
			try {
				$fileObject = $repo->getFileByPath($file,$mount);
			//should never be the case (index out of sync) - fix it:
			}catch(tx_fal_exception_FileNotFound $e){
				$uid = tx_fal_Indexer::addFileToIndex($mount, $file);
				$fileObject = $repo->getFileById($uid);
			}
			/* @var $fileObject tx_fal_File */
			$pI = pathinfo($file);
			$filesArray[] = array(
				'sys_files_id' => $fileObject->getUid(),
				'file_path' => PATH_site . 'fileadmin/' . $fileObject->getPath(),
				'file_name' => $fileObject->getName(),
				'file_size' => $fileObject->getSize(),
				'file_mtime' => filemtime($file),
				'file_type' => strtoupper($pI['extension'])
			);
		}
		return array('data' => $filesArray);
	}

	/**
	 * copies a file to clipboard
	 *
	 * @todo implement
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function copyFileToClipboard($parameter) {
		// @todo implement
		return '';
	}


	/**
	 * dispatcher for all move/rename operations for single and multiple files and folders
	 * is initiated either by clickmenu actions or by drag&drop
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	array		result array containing either success=true or succes=false and the reason why it failed
	 */
	public function updateFile($parameter) {

		/** @var $repo tx_fal_Repository */
		$repo = t3lib_div::makeInstance('tx_fal_Repository');

		$resultsArray = array();
		$sys_files_id = $parameter->data->sys_files_id;

		if (is_numeric($sys_files_id)) {
				// case: rename or move single file

			$fileObject = $repo->getFileById($sys_files_id);

			/**
			 * @todo: Rupert 25.11.2010
			 * how to detect if a file is moved between different mounts (=Storage engines)?
			 */

			if (isset($parameter->data->file_name)) {
					// rename file inside folder
				$resultsArray[$sys_files_id] = $fileObject->rename($parameter->data->file_name);
			} else {
					// move file to another folder
				$resultsArray[$sys_files_id] = $fileObject->moveFileToFolder($parameter->data->file_path);
			}

		} elseif (is_string($sys_files_id)) {
				// case: rename or move directory
			if (isset($parameter->data->file_name)) {
					// rename folder
				$resultsArray[$sys_files_id] = $this->renameFolder($sys_files_id, $parameter->data->file_name);
			} else {
					// move folder per Drag&drop
				/**
				 * @todo: <rupert.germann>, 30.11.2010
				 *
				 * implement
				 */

				t3lib_div::devLog('ToDo: implement moving of directories', __CLASS__, 2);
			}
		} elseif (is_array($parameter->data)) {
			// case: move multiple files or directories

			foreach ($parameter->data as $item) {
				if (is_numeric($item->sys_files_id)) {
					// case: move file
					$fileObject = $repo->getFileById($item->sys_files_id);
					if (isset($item->file_name)) {
							// rename file inside folder
						$resultsArray[$item->sys_files_id] = $fileObject->renameFile($item->file_name);
					} else {
							// move file to another folder
						$resultsArray[$item->sys_files_id] = $fileObject->moveFileToFolder($item->file_path);
					}

				} elseif (is_string($item->sys_files_id)) {
					// case: move directory

					if (isset($item->file_name)) {
						$resultsArray[$item->sys_files_id] = $this->renameFolder($item->sys_files_id, $item->file_name);
					} else {
						/**
						 * @todo: <rupert.germann>, 30.11.2010
						 *
						 * implement
						 */

						t3lib_div::devLog('ToDo: implement moving of multiple directories', __CLASS__, 2);
					}
				}
			}

		} else {
			t3lib_div::devLog('ERROR: unhandled case' . __FUNCTION__, 'fal', 3, array(
				'$parameter' => $parameter
			));
		}


		if (count($resultsArray)) {
			$msg = array();
			$success = TRUE;
			foreach ($resultsArray as $k => $v) {
				if ($v['success'] == FALSE) {
					$msg[] = array('uid' => $k, 'msg' => $v['msg']);
					$success = FALSE;
				}
			}
			if ($success == FALSE) {
				$result = array('data' => array('success' => false, 'msg' => $msg));
			} else {
				$result = array('data' => array('success' => true));
			}
		}


/**



case: move file via drag&drop
stdClass('data'=>stdClass('file_path' => '/var/www/_USR_DEV/rupi/fal/www/fileadmin/default/', 'sys_files_id'=>'236'))

case: rename file
stdClass('data'=>stdClass('file_name'=>'Tulips.jpg', 'sys_files_id'=>'236'))

case: rename directory
stdClass('data'=>stdClass('file_name'=>'igz7', 'sys_files_id'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/igz7+'))

case: move multiple files
stdClass('data'=>array(
	'0'=>stdClass('file_path'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/user_upload/', 'sys_files_id'=>'294'),
	'1'=>stdClass('file_path'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/user_upload/', 'sys_files_id'=>'295')
))

case: move multiple directories
stdClass('data'=>array(
	'0'=>stdClass('file_path'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/user_upload/', 'sys_files_id'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/default'),
	'1'=>stdClass('file_path'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/user_upload/', 'sys_files_id'=>'/var/www/_USR_DEV/rupi/fal/www/fileadmin/igz7+')
))

*/


	/**
	 * all methdos in dataprovider must return an answer like this:
	 *
	 * return array('data' => array('success' => true));
	 *
	 * or:
	 *
	  return array(
	  	'data' => array(
	  		'success' => false,
	  		array(
	  			'uid' => 123,
	  			'msg' => 'EPIC FAIL!'
	  		)
	  	)
	  );
	 *
	 */
//t3lib_div::devLog('$result', __CLASS__, 1, $result);
		return $result;
	}

	/**
	 * delete a file
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	array		result array containing either success=true or succes=false and the reason why it failed
	 */
	public function deleteFile($parameter) {


/**

case: delete a single file
stdClass Object (
	[data] => 4
)


case: deleting multiple files
stdClass Object (
	[data] => Array(
		[0] => 5
		[1] => 17
		[2] => 9
	)
)


*/
		/** @var $repo tx_fal_Repository */
		$repo = t3lib_div::makeInstance('tx_fal_Repository');
		$resultsArray = array();

		if (is_array($parameter->data)) {
			foreach ($parameter->data as $sys_files_id) {
				$fileObject = $repo->getFileById($sys_files_id);
				$resultsArray[] = $fileObject->delete();
			}
		} else {
			$fileObject = $repo->getFileById($parameter->data);
			$resultsArray[] = $fileObject->delete();
		}


//		t3lib_div::devLog('deleteFile ', __CLASS__, 1, array($fileObject,$sys_files_id));

		if (count($resultsArray)) {
			$msg = array();
			$success = TRUE;
			foreach ($resultsArray as $k => $v) {
				if ($v['success'] == FALSE) {
					$msg[] = array('uid' => $k, 'msg' => $v['msg']);
					$success = FALSE;
				}
			}
			if ($success == FALSE) {
				$result = array('data' => array('success' => false, 'msg' => $msg));
			} else {
				$result = array('data' => array('success' => true));
			}
		}

//		t3lib_div::devLog('$result', __CLASS__, 1, $result);
		return $result;
	}


	/**
	 * delete a folder
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	array		result array containing either success=true or succes=false and the reason why it failed
	 */
	public function deleteFolder($parameter) {

	}

	/**
	 * copy a file
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	array		result array containing either success=true or succes=false and the reason why it failed
	 */
	public function copyFile($parameter) {

	}


	/**
	 * helper function to rename a folder in the filesystem
	 * updates the index of a concerned files
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: move this to the storage backend
 	 *
	 *
	 * @param	string		$sys_files_id		path to folder which is renamed
	 * @param	string		$file_name		new name of the folder
	 * @return	array		result array containing either success=true or succes=false and the reason why it failed
	 */
	protected function renameFolder($sys_files_id, $file_name) {

		/**
		 * @todo: <rupert.germann>, 29.11.2010
		 * how to get the mount?
		 */

		$mount = 'fileadmin/';
		$mountUid = 0;

			// get affected folders
		$folders = t3lib_div::getAllFilesAndFoldersInPath(array(), $sys_files_id . '/', '', 1);
		if (!is_array($folders)) {
			$folders = array();
		}

		$oldpath_array = t3lib_div::trimExplode('/', str_replace(PATH_site . $mount, '', $sys_files_id),1);

		$pos = count($oldpath_array) - 1;

		foreach ($folders as $folder) {

			/**
			 * @todo: <rupert.germann>, 30.11.2010
			 * use the delimiter constant (windows compatibility)
			 */

				// check. if entry is a folder
			if ($folder{strlen($folder)-1} == '/') {

				$file_path = str_replace(PATH_site . $mount, '', $folder);

				$path_array = t3lib_div::trimExplode('/', $file_path,1);
				$path_array[$pos] = $file_name;
				$newFolder = implode('/', $path_array) . '/';

				$table = 'sys_files';
				$where = 'file_path = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($file_path, $table) . '
							AND mount = ' . $mountUid . ' AND deleted=0';
				$updateFields = array('file_path' => $newFolder);

				$error = FALSE;

				if ($GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $where, $updateFields)) {

				} else {
					t3lib_div::devLog('ERROR: Database update failed' . __FUNCTION__, 'fal', 3, array(
						'oldfolder'=>$file_path,
						'newFolder' => $updateFields
					));
					$error = TRUE;
				}


				t3lib_div::devLog('', __CLASS__, 1, array(
					'oldfolder'=>$file_path,
					$path_array,
					'$newFolder'=>$updateFields,
					));
			}
		}

		if (!$error) {
			// rename directory

			$altName = 0; // do not create new files if the file already exists
			$FILE = array(
				'rename' => array(
					array(
						'data' =>  $file_name,
						'target' => $sys_files_id,
						'altName' => $altName
					)
				)
			);


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



		} else {
			$flashMessages = array('SQL Error while updating index.');
		}


		if (empty($flashMessages)) {
			$result = array('success' => true);
		} else {
			$result = array(
					'success' => false,
 					'msg' => $flashMessages
 			);
		}

		return $result;
	}

	/**
	 * move/rename a file
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: currently not needed because moving files is handled by function updateFile()
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function moveFile($parameter) {

	}

	/**
	 * move/rename a folder
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: currently not needed because moving folders is handled by function updateFile()
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function moveFolder($parameter) {

	}

	/**
	 * upload a file
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function upload($parameters) {

	}

	/**
	 * edit a file
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function editFile($parameters) {

	}

	/**
	 * creates a folder
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function addFolder($parameters) {

	}

	/**
	 * creates a file
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function addFile($parameters) {

	}

	/**
	 * unzips an uploaded zip file
 	 *
 	 * @todo <rupert.germann>, 01.12.2010: implement
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	void
	 */
	public function unzip($parameters) {

	}


	/**
	 * Enter description here ...
 	 *
 	 *
	 *
	 * @param	object		$parameter		object of parameters
	 * @return	object		object of parameters
	 */
	public function getDetails($parameters){
		return $parameters;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/dataprovider/class.tx_fal_list_dataprovider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/dataprovider/class.tx_fal_list_dataprovider.php']);
}
?>