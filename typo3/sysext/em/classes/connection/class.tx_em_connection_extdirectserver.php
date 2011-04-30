<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper (info@sk-typo3.de)
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
 * Module: Extension manager, developer module
 *
 * This class handles all Ajax calls coming from ExtJS
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 */


class tx_em_Connection_ExtDirectServer {
	/**
	 * @var tx_em_Tools_XmlHandler
	 */
	var $xmlHandler;

	/**
	 * Class for printing extension lists
	 *
	 * @var tx_em_Extensions_List
	 */
	public $extensionList;

	/**
	 * Class for extension details
	 *
	 * @var tx_em_Extensions_Details
	 */
	public $extensionDetails;

	/**
	 * Keeps instance of settings class.
	 *
	 * @var tx_em_Settings
	 */
	static protected $objSettings;

	protected $globalSettings;

	/*********************************************************************/
	/* General                                                           */
	/*********************************************************************/

	/**
	 * Constructor
	 *
	 * @param boolean $createTemplateInstance: set to FALSE if no instance of template class needs to be created
	 * @return void
	 */
	public function __construct($createTemplateInstance = TRUE) {
		if ($createTemplateInstance) {
			$this->template = t3lib_div::makeInstance('template');
		}
		$this->globalSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['em']);
	}


	/**
	 * Method returns instance of settings class.
	 *
	 * @access  protected
	 * @return  em_settings  instance of settings class
	 */
	protected function getSettingsObject() {
		if (!is_object(self::$objSettings) && !(self::$objSettings instanceof tx_em_Settings)) {
			self::$objSettings = t3lib_div::makeInstance('tx_em_Settings');
		}
		return self::$objSettings;
	}


	/*********************************************************************/
	/* Local Extension List                                              */
	/*********************************************************************/


	/**
	 * Render local extension list
	 *
	 * @return string $content
	 */
	public function getExtensionList() {
		/** @var $list tx_em_Extensions_List */
		$list = t3lib_div::makeInstance('tx_em_Extensions_List');
		$extList = $list->getInstalledExtensions(TRUE);


		return array(
			'length' => count($extList),
			'data' => $extList
		);

	}

	public function getFlatExtensionList() {
		$list = $this->getExtensionList();
		$flatList = array();
		foreach ($list['data'] as $entry) {
			$flatList[$entry['extkey']] = array(
				'version' => $entry['version'],
				'intversion' => t3lib_div::int_from_ver($entry['version']),
				'installed' => $entry['installed'],
				'typeShort' => $entry['typeShort'],
			);
		}
		return array(
			'length' => count($flatList),
			'data' => $flatList
		);
	}

	/**
	 * Render extensionlist for languages
	 *
	 * @return unknown
	 */
	public function getInstalledExtkeys() {
		$list = $this->getExtensionList();
		$extList = $list['data'];

		$selectedLanguages = t3lib_div::trimExplode(',', $this->globalSettings['selectedLanguages']);

		$keys = array();
		$i = 0;
		foreach ($extList as $ext) {
			if ($ext['installed']) {
				$keys[$i] = array(
					'extkey' => $ext['extkey'],
					'icon' => $ext['icon'],
					'stype' => $ext['typeShort'],
				);
				$keys[$i]['lang'] = array();
				if (count($selectedLanguages)) {
					foreach ($selectedLanguages as $language) {
						$keys[$i]['lang'][] = $GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:lang_' . $language);
					}
				}
				$i++;
			}
		}

		return array(
			'length' => count($keys),
			'data' => $keys,
		);
	}

	/**
	 * Render module content
	 *
	 * @return string $content
	 */
	public function getExtensionDetails() {
		/** @var $list tx_em_Extensions_List */
		$list = t3lib_div::makeInstance('tx_em_Extensions_List');
		$extList = $list->getInstalledExtensions(TRUE);


		return array(
			'length' => count($extList),
			'data' => $extList
		);

	}

	/**
	 * Render extension update
	 *
	 * @var string $extKey
	 * @return string $content
	 */
	public function getExtensionUpdate($extKey) {
		if (isset($GLOBALS['TYPO3_LOADED_EXT'][$extKey])) {
			/** @var $install tx_em_Install */
			$install = t3lib_div::makeInstance('tx_em_Install');
			/** @var $extension tx_em_Extensions_List */
			$extension = t3lib_div::makeInstance('tx_em_Extensions_List');


			$extPath = t3lib_extMgm::extPath($extKey);
			$type = tx_em_Tools::getExtTypeFromPath($extPath);
			$typePath = tx_em_Tools::typePath($type);
			$extInfo = array();

			$extension->singleExtInfo($extKey, $typePath, $extInfo);
			$extInfo = $extInfo[0];
			$extInfo['type'] = $extInfo['typeShort'];
			$update = $install->checkDBupdates($extKey, $extInfo);
			if ($update) {
				$update = $GLOBALS['LANG']->getLL('ext_details_new_tables_fields_select') .
					$update .
					'<br /><input type="submit" name="write" id="update-submit-' . htmlspecialchars($extKey) . '" value="' .
						htmlspecialchars($GLOBALS['LANG']->getLL('updatesForm_make_updates')) . '" />';
			}
			return $update ? $update : $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_dbUpToDate');
		} else {
			return sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extNotInstalled') ,htmlspecialchars($extKey));
		}
	}


	/**
	 * Render extension configuration
	 *
	 * @var string $extKey
	 * @return string $content
	 */
	public function getExtensionConfiguration($extKey) {
		/** @var $extensionList tx_em_Extensions_List */
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		/** @var $install tx_em_Install */
		$install = t3lib_div::makeInstance('tx_em_Install');
		$install->setSilentMode(TRUE);

		$form = $install->updatesForm($extKey, $list[$extKey], 1, '', '', FALSE, TRUE);
		if (!$form) {
			return '<p>' . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_extNoConfiguration') . '</p>';
		} else {
			//$form = preg_replace('/<form([^>]+)>/', '', $form);
			//$form = str_replace('</form>', '', $form);
			return $form;
		}
	}

	/**
	 * Save extension configuration
	 *
	 * @formHandler
	 * @param array $parameter
	 * @return array
	 */
	public function saveExtensionConfiguration($parameter) {

		$extKey = $parameter['extkey'];
		$extType = $parameter['exttype'];
		$noSave = $parameter['noSave'];

		$absPath = tx_em_Tools::getExtPath($extKey, $extType);
		$relPath = tx_em_Tools::typeRelPath($extType) . $extKey . '/';

		/** @var $extensionList tx_em_Extensions_List */
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();


		$arr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);
		$arr = is_array($arr) ? $arr : array();

		/** @var $tsStyleConfig t3lib_tsStyleConfig */
		$tsStyleConfig = t3lib_div::makeInstance('t3lib_tsStyleConfig');
		$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;
		$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
			t3lib_div::getUrl($absPath . 'ext_conf_template.txt'),
			$relPath,
			$absPath,
			$GLOBALS['BACK_PATH']
		);

		$tsStyleConfig->ext_procesInput($parameter, array(), $theConstants, array());
		$arr = $tsStyleConfig->ext_mergeIncomingWithExisting($arr);


		/** @var $install tx_em_Install */
		$install = t3lib_div::makeInstance('tx_em_Install');
		$install->setSilentMode(TRUE);
		$install->install->INSTALL = $parameter['TYPO3_INSTALL'];
		$install->checkDBupdates($extKey, $list[$extKey]);

		$html = '';
		if ($noSave) {
			$html = $install->updatesForm($extKey, $list[$extKey], 1);
		} else {
			$install->writeTsStyleConfig($extKey, $arr);
		}

		return array(
			'success' => TRUE,
			'data' => $parameter['data'],
			'html' => $html,
		);
	}

	/**
	 * Cleans EMConf of extension
	 *
	 * @param  string  $extKey
	 * @return array
	 */
	public function cleanEmConf($extKey) {

		/** @var $extensionList tx_em_Extensions_List */
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		/** @var $extensionDetails tx_em_Extensions_Details */
		$this->extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);

		$result = $this->extensionDetails->updateLocalEM_CONF($extKey, $list[$extKey]);

		return array(
			'success' => TRUE,
			'extkey' => $extKey,
			'result' => $result
		);
	}

	/**
	 * Delete extension
	 *
	 * @param  $extKey
	 * @return array
	 */
	public function deleteExtension($extKey) {

		/** @var $extensionList tx_em_Extensions_List */
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		$type = $list[$extKey]['type'];
		$absPath = tx_em_Tools::getExtPath($extKey, $type);

		/** @var $extensionDetails tx_em_Install */
		$install = t3lib_div::makeInstance('tx_em_Install');
		$install->setSilentMode(TRUE);

		$res = $install->removeExtDirectory($absPath);
		$error = '';
		$success = TRUE;

		if ($res) {
			$res = nl2br($res);
			$error = sprintf($GLOBALS['LANG']->getLL('extDelete_remove_dir_failed'), $absPath);
			$success = FALSE;
		}
		return array(
			'success' => $success,
			'extkey' => $extKey,
			'result' => $res,
			'error' => $error
		);
	}

	/**
	 * genereates a file tree
	 *
	 * @param object $parameter
	 * @return array
	 */
	public function getExtFileTree($parameter) {
		$type = $parameter->typeShort;
		$node = substr($parameter->node, 0, 6) !== 'xnode-' ? $parameter->node : $parameter->baseNode;

		$path = PATH_site . $node;
		$fileArray = array();

		$dirs = t3lib_div::get_dirs($path);
		$files = t3lib_div::getFilesInDir($path, '', FALSE, '', '');



		if (!is_array($dirs) && !is_array($files)) {
			return array();
		}

		foreach ($dirs as $dir) {
			if ($dir{0} !== '.') {
				$fileArray[] = array(
					'id' => ($node == '' ? '' : $node . '/') . $dir,
					'text' => htmlspecialchars($dir),
					'leaf' => FALSE,
					'qtip' => ''
				);
			}
		}


		foreach ($files as $key => $file) {
			$fileInfo = $this->getFileInfo($file);

			$fileArray[] = array(
				'id' => $node . '/' . $file,
				'text' => $fileInfo[0],
				'leaf' => TRUE,
				'qtip' => $fileInfo[1],
				'iconCls' => $fileInfo[4],
				'fileType' => $fileInfo[3],
				'ext' => $fileInfo[2]
			);
		}

		return $fileArray;
	}


	/**
	 * Read extension file and send content
	 *
	 * @param string $path
	 * @return string file content
	 */
	public function readExtFile($path) {
		$path = PATH_site . $path;
		if (@file_exists($path)) {
			return t3lib_div::getURL($path);
		}
		return '';
	}

	/**
	 * Save extension file
	 *
	 * @param string $file
	 * @param string $content
	 * @return boolean success
	 */
	public function saveExtFile($file, $content) {
		$path = PATH_site . $file;
		$error = '';
		$fileSaveAllowed = $GLOBALS['TYPO3_CONF_VARS']['EXT']['noEdit'] == 0;
		$fileExists = @file_exists($path);
		$fileSaveable = is_writable($path);

		if ($fileExists && $fileSaveable && $fileSaveAllowed) {
			$success = t3lib_div::writeFile($path, $content);
		} else {
			$success = FALSE;
			$error = $fileSaveAllowed
					? ($fileSaveable
							? $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_fileNotExists', TRUE)
							: $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_fileWriteProtected', TRUE)
					)
					: $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_saving_disabled', TRUE);
		}

		if ($success) {
			$GLOBALS['BE_USER']->writelog(9, 0, 0, 0, sprintf('File "%s" has been modified', $file));
		}
		return array(
			'success' => $success,
			'path' => $path,
			'file' => basename($path),
			'content' => $content,
			'error' => $error
		);
	}

	/**
	 * Create a new file
	 *
	 * @param  string $folder
	 * @param  string $file
	 * @param  boolean $isFolder
	 * @return array result
	 */
	public function createNewFile($folder, $file, $isFolder) {
		$result = tx_em_Tools::createNewFile($folder, $file, $isFolder);

		$node = array();

		if ($result[0] === TRUE) {
			if ($isFolder) {
				$node = array(
					'id' => htmlspecialchars(substr($result[1], strlen(PATH_site))),
					'text' => htmlspecialchars(basename($result[1])),
					'leaf' => FALSE,
					'qtip' => ''
				);
			} else {
				$fileInfo = $this->getFileInfo($result[1]);
				$node = array(
					'id' => substr($fileInfo[0], strlen(PATH_site)),
					'text' => basename($fileInfo[0]),
					'leaf' => !$isFolder,
					'qtip' => $fileInfo[1],
					'iconCls' => $fileInfo[4],
					'fileType' => $fileInfo[3],
					'ext' => $fileInfo[2]
				);
			}
		}
		return array(
			'success' => $result[0],
			'created' => $result[1],
			'node' => $node,
			'error' => $result[2]
		);
	}

	/**
	 * Rename a file/folder
	 *
	 * @param  string $file
	 * @param  string $newName
	 * @param  boolean $isFolder
	 * @return array result
	 */
	public function renameFile($file, $newName, $isFolder) {
		$src = basename($file);
		$newFile = substr($file, 0, -1 * strlen($src)) . $newName;

		$success = tx_em_Tools::renameFile($file, $newFile);

		return array(
			'success' => $success,
			'oldFile' => $file,
			'newFile' => $newFile,
			'newFilename' => basename($newFile),
		);
	}


	/**
	 * Moves a file to new destination
	 *
	 * @param  string $file
	 * @param  string $destination
	 * @param  boolean $isFolder
	 * @return array
	 */
	public function moveFile($file, $destination, $isFolder) {
		return array(
			'success' => TRUE,
			'file' => $file,
			'destination' => $destination,
			'isFolder' => $isFolder
		);
	}

	/**
	 * Deletes a file/folder
	 *
	 * @param  string $file
	 * @param  boolean $isFolder
	 * @return array
	 */
	public function deleteFile($file, $isFolder) {

		$file = str_replace('//', '/', PATH_site . $file);
		$command['delete'][] = array(
			'data' => $file
		);
		$result = $this->fileOperation($command);

		return array(
			'success' => TRUE,
			'file' => $file,
			'isFolder' => $isFolder,
			'command' => $command,
			'result' => $result
		);
	}

	/**
	 * Shows a diff of content changes of a file
	 *
	 * @param  string $file
	 * @param  string $content
	 * @return array
	 */
	public function makeDiff($original, $content) {
		$diff = t3lib_div::makeInstance('t3lib_diff');
		$result = $diff->makeDiffDisplay($original, $content);
		//debug(array($original, $content, $result));
		return array(
			'success' => TRUE,
			'diff' => '<pre>' . $result . '</pre>'
		);
	}

	/**
	 * Load upload form for extension upload to TER
	 *
	 * @formcHandler
	 * @return array
	 */
	public function loadUploadExtToTer() {
		$settings = $this->getSettings();
		return array(
			'success' => TRUE,
			'data' => array(
				'fe_u' => $settings['fe_u'],
				'fe_p' => $settings['fe_p']
			)
		);
	}

	/**
	 * Upload extension to TER
	 *
	 * @formHandler
	 *
	 * @param string $parameter
	 * @return array
	 */
	public function uploadExtToTer($parameter) {
		$repository = $this->getSelectedRepository();
		$wsdlURL = $repository['wsdl_url'];

		$parameter['user']['fe_u'] = $parameter['fe_u'];
		$parameter['user']['fe_p'] = $parameter['fe_p'];
		$parameter['upload']['mode'] = $parameter['newversion'];
		$parameter['upload']['comment'] = $parameter['uploadcomment'];

		/** @var $extensionList tx_em_Extensions_List */
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		/** @var $extensionDetails tx_em_Extensions_Details */
		$this->extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);

		/** @var $terConnection  tx_em_Connection_Ter*/
		$terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
		$terConnection->wsdlURL = $wsdlURL;

		$parameter['extInfo'] = $list[$parameter['extKey']];
		$response = $terConnection->uploadToTER($parameter);

		if (!is_array($response)) {
			return array(
				'success' => FALSE,
				'error' => $response,
				'params' => $parameter,
			);
		}
		if ($response['resultCode'] == 10504) { //success
			$parameter['extInfo']['EM_CONF']['version'] = $response['version'];
			$response['resultMessages'][] = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:terCommunication_ext_version'),
				$response['version']
			);
			$response['resultMessages'][] = $this->extensionDetails->updateLocalEM_CONF($parameter['extKey'], $parameter['extInfo']);
		}

		return array(
			'success' => TRUE,
			'params' => $parameter,
			'response' => $response
		);
	}

	/**
	 * Prints developer information
	 *
	 * @param string $parameter
	 * @return string
	 */
	public function getExtensionDevelopInfo($extKey) {
		/** @var $extensionList  tx_em_Extensions_List*/
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		/** @var $extensionDetails tx_em_Extensions_Details */
		$extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);

		return $extensionDetails->extInformationarray($extKey, $list[$extKey]);
	}


/**
	 * Prints backupdelete
	 *
	 * @param string $parameter
	 * @return string
	 */
	public function getExtensionBackupDelete($extKey) {
		$content='';
	   /** @var $extensionList  tx_em_Extensions_List*/
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		/** @var $extensionDetails tx_em_Extensions_Details */
		$extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details');
		/** @var $extensionDetails tx_em_Connection_Ter */
		$terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
		/** @var $extensionDetails tx_em_Install */
		$install = t3lib_div::makeInstance('tx_em_Install');
		/** @var $api tx_em_API */
		$api = t3lib_div::makeInstance('tx_em_API');


		list($list,) = $extensionList->getInstalledExtensions();
		$uploadArray = $extensionDetails->makeUploadarray($extKey, $list[$extKey]);

		if (is_array($uploadArray)) {
			$backUpData = $terConnection->makeUploadDataFromarray($uploadArray);
			$filename = 'T3X_' . $extKey . '-' . str_replace('.', '_', $list[$extKey]['EM_CONF']['version']) . '-z-' . date('YmdHi') . '.t3x';

			$techInfo = $install->makeDetailedExtensionAnalysis($extKey, $list[$extKey]);
			$lines = array();

			// Backup
			$lines[] = '<tr class="t3-row-header"><td colspan="2">' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_backup') . '</td></tr>';
			$lines[] = '<tr class="bgColor4"><td><strong>' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_files') . '</strong></td><td>' .
					'<a class="t3-link" href="' . htmlspecialchars(t3lib_div::linkThisScript(array(
				'CMD[doBackup]' => 1,
				'CMD[showExt]' => $extKey,
				'SET[singleDetails]' => 'backup'
			))) .
					'">' . sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_download'),
				$extKey
			) . '</a><br />
				(' . $filename . ', <br />' .
					t3lib_div::formatSize(strlen($backUpData)) . ', <br />' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_md5') . ' ' . md5($backUpData) . ')
				<br /></td></tr>';


			if (is_array($techInfo['tables'])) {
				$lines[] = '<tr class="bgColor4"><td><strong>' . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_data_tables') .
						'</strong></td><td>' .
							tx_em_Database::dumpDataTablesLine($techInfo['tables'], $extKey, array('SET[singleDetails]' => 'backup')) .
						'</td></tr>';
			}
			if (is_array($techInfo['static'])) {
				$lines[] = '<tr class="bgColor4"><td><strong>' . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extBackup_static_tables') .
						'</strong></td><td>' .
							tx_em_Database::dumpDataTablesLine($techInfo['static'], $extKey, array('SET[singleDetails]' => 'backup')) .
						'</td></tr>';
			}

			// Delete
			if (!t3lib_extMgm::isLoaded($extKey)) {
					// check ext scope
				if (tx_em_Tools::deleteAsType($list[$extKey]['type']) && t3lib_div::inList('G,L', $list[$extKey]['type'])) {
					$lines[] = '<tr class="t3-row-header"><td colspan="2">' .
							$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_delete') . '</td></tr>';
					$lines[] = '<tr class="bgColor4"><td colspan="2">' . $install->extDelete($extKey, $list[$extKey], '') . '</td></tr>';
				}
			}
			// EM_CONF
			$lines[] = '<tr class="t3-row-header"><td colspan="2">' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_update_em_conf') . '</td></tr>';


			$updateEMConf = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extUpdateEMCONF_file');
			$lines[] = '<tr class="bgColor4"><td colspan="2">' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extUpdateEMCONF_info_changes') . '<br />
						' . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extUpdateEMCONF_info_reset') .
					'<br /><br />' .
					'<a class="t3-link emconfLink" href="#"><strong>' . $updateEMConf . '</strong> ' .
					sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:extDelete_from_location'),
						$api->typeLabels[$list[$extKey]['type']],
						substr(tx_em_Tools::getExtPath($extKey, $list[$extKey]['type']['type']), strlen(PATH_site))
					) . '</a>'
					. '</td></tr>';


			// mod menu for singleDetails
			$modMenu = $GLOBALS['TBE_MODULES_EXT']['tools_em']['MOD_MENU']['singleDetails'];
			if (isset($modMenu) && is_array($modMenu)) {
				$lines[] = '<tr class="t3-row-header"><td colspan="2">' .
					$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_externActions') . '</td></tr>';
				$menuLinks = '';
				foreach ($modMenu as $menuEntry) {
					$onClick = htmlspecialchars('jumpToUrl(\'mod.php?&id=0&M=tools_em&SET[singleDetails]=' . $menuEntry['name'] . '&CMD[showExt]=' . $extKey . '\');');
					$menuLinks .= '<a class="t3-link" href="#" onclick="' . $onClick . '" >' .
							$GLOBALS['LANG']->sL($menuEntry['title'], TRUE) . '</a><br />';
				}
				$lines[] = '<tr class="bgColor4"><td colspan="2"><p>' . $menuLinks . '</p></td></tr>';
			}

			$content = '<table border="0" cellpadding="2" cellspacing="2">' . implode('', $lines) . '</table>';



			return $this->replaceLinks($content);
		}
	}

	/**
	 * Execute update script
	 *
	 * @param  $extkey
	 * @return array
	 */
	public function getExtensionUpdateScript($extkey) {
		$updateScript = t3lib_extMgm::extPath($extkey) . 'class.ext_update.php';
		require_once($updateScript);
		$updateObj = new ext_update;
		$access = FALSE;
		if ($updateObj->access()) {
			$access = TRUE;
		}

		return array(
			'success' => $access,
		);

	}
	/*********************************************************************/
	/* Remote Extension List                                             */
	/*********************************************************************/


	/**
	 * Render remote extension list
	 *
	 * @param object $parameters
	 * @return string $content
	 */
	public function getRemoteExtensionList($parameters) {
		$repositoryId = $parameters->repository;
		$mirrorUrl = $this->getMirrorUrl($repositoryId);

		$list = $this->getFlatExtensionList();
		$localList = $list['data'];

		$search = htmlspecialchars($parameters->query);
		$limit = htmlspecialchars($parameters->start . ', ' . $parameters->limit);
		$orderBy = htmlspecialchars($parameters->sort);
		$orderDir = htmlspecialchars($parameters->dir);
		if ($orderBy == '') {
			$orderBy = 'relevance';
			$orderDir = 'ASC';
		}
		if ($orderBy === 'statevalue') {
			$orderBy = 'cache_extensions.state ' . $orderDir;
		} elseif ($orderBy === 'relevance') {
			$orderBy = 'relevance ' . $orderDir . ', cache_extensions.title ' . $orderDir;
		} else {
			$orderBy = 'cache_extensions.' . $orderBy . ' ' . $orderDir;
		}
		$installedOnly = $parameters->installedOnly;

		$where = $addFields = '';

		if ($search === '' && !$installedOnly) {
			return array(
				'length' => 0,
				'data' => array(),
			);
		} elseif ($search === '*') {

		} else {
			$quotedSearch = $GLOBALS['TYPO3_DB']->escapeStrForLike(
				$GLOBALS['TYPO3_DB']->quoteStr($search, 'cache_extensions'),
				'cache_extensions'
			);
			$addFields = '
				(CASE WHEN cache_extensions.extkey =  "' . $search . '" THEN 100 ELSE 5 END) +
				(CASE WHEN cache_extensions.title = "' . $search . '" THEN 80 ELSE 5 END) +
				(CASE WHEN cache_extensions.extkey LIKE \'%' . $quotedSearch . '%\' THEN 60 ELSE 5 END) +
				(CASE WHEN cache_extensions.title LIKE \'%' . $quotedSearch . '%\' THEN 40 ELSE 5 END)
			 AS relevance';

			if (t3lib_extMgm::isLoaded('dbal')) {
				// as dbal can't use the sum, make it more easy for dbal
				$addFields = 'CASE WHEN cache_extensions.extkey =  \'' . $search . '\' THEN 100 ELSE 10 END AS relevance';
			}
			$where = ' AND (cache_extensions.extkey LIKE \'%' . $quotedSearch . '%\' OR cache_extensions.title LIKE \'%' . $quotedSearch . '%\')';

		}
	    	// check for filter
		$where .= $this->makeFilterQuery(get_object_vars($parameters));

		if ($installedOnly) {
			$temp = array();
			foreach ($localList as $key => $value) {
				if ($value['installed']) {
					$temp[] = '"' . $key . '"';
				}
			}
			$where .= ' AND cache_extensions.extkey IN(' . implode(',', $temp) . ')';
			$limit = '';
		}


		$list = tx_em_Database::getExtensionListFromRepository(
			$repositoryId,
			$addFields,
			$where,
			$orderBy,
			$limit
		);

		$updateKeys = array();

			// transform array
		foreach ($list['results'] as $key => $value) {
			$list['results'][$key]['dependencies'] = unserialize($value['dependencies']);
			$extPath = t3lib_div::strtolower($value['extkey']);
			$list['results'][$key]['statevalue'] = $value['state'];
			$list['results'][$key]['state'] = tx_em_Tools::getDefaultState(intval($value['state']));
			$list['results'][$key]['stateCls'] = 'state-' . $list['results'][$key]['state'];
			$list['results'][$key]['version'] = tx_em_Tools::versionFromInt($value['maxintversion']);
			$list['results'][$key]['icon'] = '<img alt="" src="' . $mirrorUrl . $extPath{0} . '/' . $extPath{1} . '/' . $extPath . '_' . $list['results'][$key]['version'] . '.gif" />';

			$list['results'][$key]['exists'] = 0;
			$list['results'][$key]['installed'] = 0;
			$list['results'][$key]['versionislower'] = 0;
			$list['results'][$key]['existingVersion'] = '';
			if (isset($localList[$value['extkey']])) {
				$isUpdatable = ($localList[$value['extkey']]['intversion'] < $value['maxintversion']);
				$list['results'][$key]['exists'] = 1;
				$list['results'][$key]['installed'] = $localList[$value['extkey']]['installed'];
				$list['results'][$key]['versionislower'] = $isUpdatable;
				$list['results'][$key]['existingVersion'] =  $localList[$value['extkey']]['version'];
				if ($isUpdatable) {
					$updateKeys[] = $key;
				}
			}
		}
			// updatable only
		if ($installedOnly == 2) {
			$temp = array();
			if (count($updateKeys)) {
				foreach ($updateKeys as $key) {
					$temp[]= $list['results'][$key];
				}
			}
			$list['results'] = $temp;
			$list['count'] -= count($updateKeys);
		}

		return array(
			'length' => $list['count'],
			'data' => $list['results'],
		);

	}


	/**
	 * Loads repositories
	 *
	 * @return array
	 */
	public function getRepositories() {
		$settings = $this->getSettings();
		$repositories = tx_em_Database::getRepositories();
		$data = array();

		foreach ($repositories as $uid => $repository) {
			$data[] = array(
				'title' => $repository['title'],
				'uid' => $repository['uid'],
				'description' => $repository['description'],
				'wsdl_url' => $repository['wsdl_url'],
				'mirror_url' => $repository['mirror_url'],
				'count' => $repository['extCount'],
				'updated' => $repository['lastUpdated'] ? date('d/m/Y H:i', $repository['lastUpdated']) : 'never',
				'selected' => $repository['uid'] === $settings['selectedRepository'],
			);
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);
	}


	/**
	 * Get Mirrors for selected repository
	 *
	 * @param  object $parameter
	 * @return array
	 */
	public function getMirrors($parameter) {
		$data = array();
		/** @var $objRepository tx_em_Repository */
		$objRepository = t3lib_div::makeInstance('tx_em_Repository', $parameter->repository);

		if ($objRepository->getMirrorListUrl()) {
			$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
			$mirrors = $objRepositoryUtility->getMirrors(TRUE)->getMirrors();


			if (count($mirrors)) {
				$data = array(
					array(
						'title' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:mirror_use_random'),
						'country' => '',
						'host' => '',
						'path' => '',
						'sponsor' => '',
						'link' => '',
						'logo' => '',
					)
				);
				foreach ($mirrors as $mirror) {
					$data[] = array(
						'title' => $mirror['title'],
						'country' => $mirror['country'],
						'host' => $mirror['host'],
						'path' => $mirror['path'],
						'sponsor' => $mirror['sponsorname'],
						'link' => $mirror['sponsorlink'],
						'logo' => $mirror['sponsorlogo'],
					);
				}
			}
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);

	}

	/**
	 * Edit / Create repository
	 *
	 * @formHandler
	 * @param array $parameter
	 * @return array
	 */
	public function repositoryEditFormSubmit($parameter) {
		$repId = intval($parameter['rep']);

		/** @var $repository tx_em_Repository */
		$repository = t3lib_div::makeInstance('tx_em_Repository', $repId);
		$repository->setTitle($parameter['title']);
		$repository->setDescription($parameter['description']);
		$repository->setWsdlUrl($parameter['wsdl_url']);
		$repository->setMirrorListUrl($parameter['mirror_url']);
		$repositoryData = array(
			'title' => $repository->getTitle(),
			'description' => $repository->getDescription(),
			'wsdl_url' => $repository->getWsdlUrl(),
			'mirror_url' => $repository->getMirrorListUrl(),
			'lastUpdated' => $repository->getLastUpdate(),
			'extCount' => $repository->getExtensionCount(),
		);

		if ($repId === 0) {
				// create a new repository
			$id = tx_em_Database::insertRepository($repository);
			return array(
				'success' => TRUE,
				'newId' => $id,
				'params' => $repositoryData
			);

		} else {
			tx_em_Database::updateRepository($repository);
			return array(
				'success' => TRUE,
				'params' => $repositoryData
			);
		}
	}


	/**
	 * Delete repository
	 *
	 * @param  int $uid
	 * @return array
	 */
	public function deleteRepository($uid) {
		if (intval($uid) < 2) {
			return array(
				'success' => FALSE,
				'error' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:repository_main_nodelete')
			);
		}
		$repository = t3lib_div::makeInstance('tx_em_Repository', intval($uid));
		tx_em_Database::deleteRepository($repository);
		return array(
				'success' => TRUE,
				'uid' => intval($uid)
			);
	}
	/**
	 * Update repository
	 *
	 * @param array $parameter
	 * @return array
	 */
	public function repositoryUpdate($repositoryId) {

		if (!intval($repositoryId)) {
			return array(
				'success' => FALSE,
				'errors' => 'no repository choosen',
				'rep' => 0
			);
		}

		/** @var $objRepository tx_em_Repository */
		$objRepository = t3lib_div::makeInstance('tx_em_Repository', intval($repositoryId));
		/** @var $objRepositoryUtility tx_em_Repository_Utility */
		$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
		$count = $objRepositoryUtility->updateExtList();
		$time = $GLOBALS['EXEC_TIME'];

		if ($count) {
			$objRepository->setExtensionCount($count);
			$objRepository->setLastUpdate($time);
			tx_em_Database::updateRepository($objRepository);
			return array(
				'success' => TRUE,
				'data' => array(
					'count' => $count,
					'updated' => date('d/m/Y H:i', $time)
				),
				'rep' =>  intval($repositoryId)
			);
		} else {
			return array(
				'success' => FALSE,
				'errormsg' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:repository_upToDate'),
				'rep' =>  intval($repositoryId)
			);
		}
	}


	/*********************************************************************/
	/* Translation Handling                                              */
	/*********************************************************************/


	/**
	 * Gets the system languages
	 *
	 * @return array
	 */
	public function getLanguages() {
		$this->globalSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['em']);
		$selected = t3lib_div::trimExplode(',', $this->globalSettings['selectedLanguages'], TRUE);

		$theLanguages = t3lib_div::trimExplode('|', TYPO3_languages);
			//drop default
		array_shift($theLanguages);
		$lang = $meta = array();
		foreach ($theLanguages as $language) {
			$label = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:lang_' . $language));
			$lang[] = array(
				'label' => $label,
				'lang' => $language,
				'selected' => is_array($selected) && in_array($language, $selected) ? 1 : 0
			);
			$meta[] = array(
				'hidden' => is_array($selected) && in_array($language, $selected) ? 'false' : 'true',
				'header' => $language,
				'dataIndex' =>  $language,
				'width' => '100',
				'fixed' => TRUE,
				'sortable' => FALSE,
				'hidable' => FALSE,
				'menuDisabled' => TRUE,
			);
		}
		return array(
			'length' => count($lang),
			'data' => $lang,
			'meta' => $meta,
		);

	}

	/**
	 * Saves language selection
	 *
	 * @param array $parameter
	 * @return string
	 */
	public function saveLanguageSelection($parameter) {
		$this->globalSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['em']);
		$selected = t3lib_div::trimExplode(',', $this->globalSettings['selectedLanguages'], TRUE);

		$dir = count($parameter) - count($selected);
		$diff = $dir < 0 ? array_diff($selected, $parameter) : array_diff($parameter, $selected);
		$type = tx_em_Tools::getExtTypeFromPath(t3lib_extMgm::extPath('em'));

		$params = array(
			'extkey' => 'em',
			'exttype' => $type,
			'data' => array(
				'selectedLanguages' => implode(',', $parameter)
			)
		);
		$this->saveExtensionConfiguration($params);

		return array(
			'success' => TRUE,
			'dir' => $dir,
			'diff' => implode('', $diff)
		);
	}


	/**
	 * Fetches translation from server
	 *
	 * @param string $extkey
	 * @param string $type
	 * @param array $selection
	 * @return array
	 */
	public function fetchTranslations($extkey, $type, $selection) {
		$result = array();
		if (is_array($selection) && count($selection)) {
			$terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
			$this->xmlHandler = t3lib_div::makeInstance('tx_em_Tools_XmlHandler');
			$this->xmlHandler->emObj = $this;
			$mirrorURL = $this->getSettingsObject()->getMirrorURL();

			$infoIcon = '<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info">&nbsp;</span>';
			$updateIcon = '<span class="t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-update">&nbsp;</span>';
			$newIcon = '<span class="t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-import">&nbsp;</span>';
			$okIcon = '<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-checked">&nbsp;</span>';
			$errorIcon = '<span class="t3-icon t3-icon-status t3-icon-status-status t3-icon-status-permission-denied">&nbsp;</span>';

			foreach ($selection as $lang) {
				$fetch = $terConnection->fetchTranslationStatus($extkey, $mirrorURL);

				$localmd5 = '';
				if (!isset($fetch[$lang])) {
						//no translation available
					$result[$lang] = $infoIcon . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:translation_n_a');
				} else {
					$zip = PATH_site . 'typo3temp/' . $extkey . '-l10n-' . $lang . '.zip';
					if (is_file($zip)) {
						$localmd5 = md5_file($zip);
					}
					if ($localmd5 !== $fetch[$lang]['md5']) {
						if ($type) {
								//fetch translation
							$ret = $terConnection->updateTranslation($extkey, $lang, $mirrorURL);

							$result[$lang] = $ret
									? $okIcon . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_updated')
									: $errorIcon . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_failed');
						} else {
								//translation status
							$result[$lang] = $localmd5 !== ''
									? $updateIcon . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:translation_status_update')
									: $newIcon . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:translation_status_new');
						}
					} else {
							//translation is up to date
						$result[$lang] = $okIcon . $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:translation_status_uptodate');;
					}
				}


			}
		}
		return $result;
	}


	/*********************************************************************/
	/* Settings                                                          */
	/*********************************************************************/

	/**
	 * Returns settings object.
	 *
	 * @access  public
	 * @return  tx_em_Settings  instance of settings object
	 */
	public function getSettings() {
		return $this->getSettingsObject()->getSettings();
	}

	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return boolean
	 */
	public function saveSetting($name, $value) {
		$this->getSettingsObject()->saveSetting($name, $value);
		return TRUE;
	}

	/**
	 * Load form values for settings form
	 *
	 * @return array FormValues
	 */
	public function settingsFormLoad() {
		$settings = $this->getSettings();

		return array(
			'success' => TRUE,
			'data' => array(
				'display_unchecked' => $settings['display_unchecked'],
				'fe_u' => $settings['fe_u'],
				'fe_p' => $settings['fe_p'],
				'selectedMirror' => $settings['selectedMirror'],
				'selectedRepository' => $settings['selectedRepository'],
			)
		);
	}

	/**
	 * Save settings from form submit
	 *
	 * @formHandler
	 * @param array $parameter
	 * @return array
	 */
	public function settingsFormSubmit($parameter) {
		$settings = $this->getSettingsObject()->saveSettings(array(
			'display_unchecked' => isset($parameter['display_unchecked']),
			'fe_u' => $parameter['fe_u'],
			'fe_p' => $parameter['fe_p'],
			'selectedMirror' => $parameter['selectedMirror'],
			'selectedRepository' => $parameter['selectedRepository'],
		));
		return array(
			'success' => TRUE,
			'data' => $parameter,
			'settings' => $settings
		);
	}


	/*********************************************************************/
	/* EM Tools                                                          */
	/*********************************************************************/

	/**
	 * Upload an extension
	 *
	 * @formHandler
	 *
	 * @access  public
	 * @param $parameter composed parameter from $POST and $_FILES
	 * @return  array status
	 */
	public function uploadExtension($parameter) {
		$uploadedTempFile = isset($parameter['extfile']) ? $parameter['extfile'] : t3lib_div::upload_to_tempfile($parameter['extupload-path']['tmp_name']);
		$location = ($parameter['loc'] === 'G' || $parameter['loc'] === 'S') ? $parameter['loc'] : 'L';
		$uploadOverwrite = $parameter['uploadOverwrite'] ? TRUE : FALSE;

		$install = t3lib_div::makeInstance('tx_em_Install', $this);
		$this->extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		$this->extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);

		$upload = $install->uploadExtensionFile($uploadedTempFile, $location, $uploadOverwrite);

		if ($upload[0] === FALSE) {
			return array(
				'success' => FALSE,
				'error' => $upload[1]
			);
		}

		$extKey = $upload[1][0]['extKey'];
		$version = '';
		$dontDelete = TRUE;
		$result = $install->installExtension($upload[1], $location, $version, $uploadedTempFile, $dontDelete);
		return array(
			'success' => TRUE,
			'data' => $result,
			'extKey' => $extKey
		);

	}

	/**
	 * Enables an extension
	 *
	 * @param  $extensionKey
	 * @return void
	 */
	public function enableExtension($extensionKey) {
		$this->extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		$install = t3lib_div::makeInstance('tx_em_Install', $this);

		list($installedList,) = $this->extensionList->getInstalledExtensions();
		$newExtensionList = $this->extensionList->addExtToList($extensionKey, $installedList);

		$install->writeNewExtensionList($newExtensionList);
		tx_em_Tools::refreshGlobalExtList();
		$install->forceDBupdates($extensionKey, $installedList[$extensionKey]);
	}

	/**
	 * Reset all states for current user
	 *
	 * @return void
	 */
	public function resetStates() {
		unset($GLOBALS['BE_USER']->uc['moduleData']['tools_em']['States']);
		$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
		return array('success' => TRUE);
	}

	/**
	 * Gets the mirror url from selected mirror
	 *
	 * @param  $repositoryId
	 * @return string
	 */
	protected function getMirrorUrl($repositoryId) {
		$settings = $this->getSettings();
		/** @var $objRepository  tx_em_Repository */
		$objRepository = t3lib_div::makeInstance('tx_em_Repository', $repositoryId);
		/** @var $objRepositoryUtility  tx_em_Repository_Utility */
		$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
		$mirrors = $objRepositoryUtility->getMirrors(TRUE)->getMirrors();


		if ($settings['selectedMirror'] == '') {
			$randomMirror = array_rand($mirrors);
			$mirrorUrl = $mirrors[$randomMirror]['host'] . $mirrors[$randomMirror]['path'];
		} else {
			foreach($mirrors as $mirror) {
				if ($mirror['host'] == $settings['selectedMirror']) {
					$mirrorUrl = $mirror['host'] . $mirror['path'];
					break;
				}
			}
		}

		return 'http://' . $mirrorUrl;
	}

	/**
	 * Resolves the filter settings from repository list and makes a whereClause
	 *
	 * @param  array  $parameter
	 * @return string additional whereClause
	 */
	protected function makeFilterQuery($parameter) {
		$where = '';
		$filter = $found = array();

		foreach ($parameter as $key => $value) {
			if (substr($key, 0, 6) === 'filter') {
				eval('$' . $key . ' = \'' . $value . '\';');
			}
		}


		if (count($filter)) {
			foreach ($filter as $value) {
				switch ($value['data']['type']) {
					case 'list':
						if ($value['field'] === 'statevalue') {
							$where .= ' AND cache_extensions.state IN(' . htmlspecialchars($value['data']['value']) . ')';
						}
						if ($value['field'] === 'category') {
							$where .= ' AND cache_extensions.category IN(' . htmlspecialchars($value['data']['value']) . ')';
						}
					break;
					default:
						$quotedSearch = $GLOBALS['TYPO3_DB']->escapeStrForLike(
							$GLOBALS['TYPO3_DB']->quoteStr($value['data']['value'], 'cache_extensions'),
							'cache_extensions'
						);
						$where .= ' AND cache_extensions.' . htmlspecialchars($value['field']) . ' LIKE "%' . $quotedSearch . '%"';
				}
			}
		}
		return $where;
	}

	/**
	 * Replace links that are created with t3lib_div::linkThisScript to point to module
	 *
	 * @param  string  $string
	 * @return string
	 */
	protected function replaceLinks($string) {
		 return str_replace(
			'ajax.php?ajaxID=ExtDirect%3A%3Aroute&amp;namespace=TYPO3.EM',
			'mod.php?M=tools_em',
			$string
		 );
	}

	/**
	 * Get the selected repository
	 *
	 * @return array
	 */
	protected function getSelectedRepository() {
		$settings = $this->getSettings();
		$repositories = tx_em_Database::getRepositories();
		$selectedRepository = array();

		foreach ($repositories as $uid => $repository) {
			if ($repository['uid'] == $settings['selectedRepository']) {
				$selectedRepository = array(
					'title' => $repository['title'],
					'uid' => $repository['uid'],
					'description' => $repository['description'],
					'wsdl_url' => $repository['wsdl_url'],
					'mirror_url' => $repository['mirror_url'],
					'count' => $repository['extCount'],
					'updated' => $repository['lastUpdated'] ? date('d/m/Y H:i', $repository['lastUpdated']) : 'never',
					'selected' => $repository['uid'] === $settings['selectedRepository'],
				);
			}
		}

		return $selectedRepository;
	}

	/**
	 * Gets file info for ExtJs tree node
	 *
	 * @param  $file
	 * @return array
	 */
	protected function getFileInfo($file) {
		$unknownType = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_file_unknownType');
		$imageType = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_file_imageType');
		$textType = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_file_textType');
		$extType = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:ext_details_file_extType');

		$editTypes = explode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext']);
		$imageTypes = array('gif', 'jpg', 'png');

		$fileExt = '';
		$type = '';
		$cls = t3lib_iconWorks::mapFileExtensionToSpriteIconClass('');
		if (strrpos($file, '.') !== FALSE) {
			$fileExt = strtolower(substr($file, strrpos($file, '.') + 1));
		}

		if ($fileExt && in_array($fileExt, $imageTypes) || in_array($fileExt, $editTypes)) {
			$cls = t3lib_iconWorks::mapFileExtensionToSpriteIconClass($fileExt);
			$type = in_array($fileExt, $imageTypes) ? 'image' : 'text';
		}

		if (t3lib_div::strtolower($file) === 'changelog') {
			$cls = t3lib_iconWorks::mapFileExtensionToSpriteIconClass('txt');
			$type = 'text';
		}

		switch($type) {
			CASE 'image':
				$label = $imageType;
			break;
			CASE 'text':
				$label = $textType;
			break;
			default:
				$label = $fileExt ? sprintf($extType, $fileExt) : $unknownType;
		}

		return array(
			htmlspecialchars($file),
			$label,
			htmlspecialchars($fileExt),
			$type,
			$cls
		);

	}


	/**
	 * File operations like delete, copy, move
	 * @param  $file commandMap, @see
	 * @return
	 */
	protected function fileOperation($file) {
		$mount = array(0 => array(
			'name' => 'root',
			'path' => PATH_site,
			'type' => ''
		));
		$files = array(0 => array(
			'webspace' => array('allow' => '*', 'deny' => ''),
			'ftpspace' => array('allow' => '*', 'deny' => '')
		));
		$fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$fileProcessor->init($mount, $files);
		$fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
		$fileProcessor->dontCheckForUnique = 0;

			// Checking referer / executing:
		$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost != $refInfo['host']
			&& $this->vC != $GLOBALS['BE_USER']->veriCode()
			&& !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
			&& $GLOBALS['CLIENT']['BROWSER'] != 'flash') {
			$fileProcessor->writeLog(0, 2, 1, 'Referer host "%s" and server host "%s" did not match!', array($refInfo['host'], $httpHost));
		} else {
			$fileProcessor->start($file);
			$fileData = $fileProcessor->processData();
		}

		return $fileData;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connectionextdirectserver.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/connection/class.tx_em_connection_extdirectserver.php']);
}

?>