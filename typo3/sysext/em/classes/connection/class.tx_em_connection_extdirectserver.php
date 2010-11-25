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
 * $Id: class.tx_em_Connection_ExtDirectServer.php 2083 2010-03-22 00:48:31Z steffenk $
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 */


class tx_em_Connection_ExtDirectServer {
	/**
	 * @var tx_em_Tools_XmlHandler
	 */
	var $xmlhandler;

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


	/*********************************************************************/
	/* General                                                           */
	/*********************************************************************/

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {

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
		$list = t3lib_div::makeInstance('tx_em_Extensions_List');
		$extList = $list->getInstalledExtensions(TRUE);


		return array(
			'length' => count($extList),
			'data' => $extList
		);

	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function getInstalledExtkeys() {
		$list = t3lib_div::makeInstance('tx_em_Extensions_List');
		$extList = $list->getInstalledExtensions(TRUE);


		$temp = $this->getSettings();
		$selectedLanguage = unserialize($temp['selectedLanguages']);


		$keys = array();
		$i = 0;
		foreach ($extList as $ext) {
			if ($ext['installed']) {
				$keys[$i] = array(
					'extkey' => $ext['extkey'],
					'icon' => $ext['icon'],
					'stype' => $ext['typeShort'],
				);
				foreach ($selectedLanguage as $language) {
					$keys[$i]['lang'][] = $GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:lang_' . $language);
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
			$path = t3lib_extMgm::extPath($extKey);
			$ext = array();

			$install = t3lib_div::makeInstance('tx_em_Install');
			$extension = t3lib_div::makeInstance('tx_em_Extensions_List');
			$extension->singleExtInfo($extKey, $path, $ext);
			$ext = $ext[0];
			$update = $install->checkDBupdates($extKey, $ext);
			return $update ? $update : 'Extension is up to date.';
		} else {
			return 'Extension "' . htmlspecialchars($extKey) . '" is not installed.';
		}
	}


	/**
	 * Render extension configuration
	 *
	 * @var string $extKey
	 * @return string $content
	 */
	public function getExtensionConfiguration($extKey) {
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		$install = t3lib_div::makeInstance('tx_em_Install');
		$form = $install->updatesForm($extKey, $list[$extKey], 1);
		if (!$form) {
			return '<p>' . 'This extension has no configuration.' . '</p>';
		} else {
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
		return array(
			'success' => true,
			'data' => $parameter
		);
	}

	/**
	 * genereates a file tree
	 *
	 * @param object $parameter
	 * @return array
	 */
	public function getExtFileTree($parameter) {
		$ext = array();
		$extKey = $parameter->extkey;
		$type = $parameter->typeShort;
		$node = strpos($parameter->node, '/') !== FALSE ? $parameter->node : $parameter->baseNode;

		$path = PATH_site . $node;
		$fileArray = array();

		$dirs = t3lib_div::get_dirs($path);
		$files = t3lib_div::getFilesInDir($path, '', FALSE, '', '');

		$editTypes = explode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext']);
		$imageTypes = array('gif', 'jpg', 'png');

		if (!is_array($files) || !count($files)) {
			return array();
		}

		foreach ($dirs as $dir) {
			if ($dir{0} !== '.') {
				$fileArray[] = array(
					'id' => $node . '/' . $dir,
					'text' => htmlspecialchars($dir),
					'leaf' => false,
					'qtip' => ''
				);
			}
		}


		foreach ($files as $key => $file) {
			$fileExt = strtolower(substr($file, strrpos($file, '.') + 1));
			if (in_array($fileExt, $imageTypes)) {
				$cls = 'tree-image';
			} elseif (in_array($fileExt, $editTypes)) {
				$cls = 'tree-edit';
			} else {
				$cls = 'tree-unknown';
			}
			$fileArray[] = array(
				'id' => $node . '/' . $file,
				'text' => htmlspecialchars($file),
				'leaf' => true,
				'qtip' => $fileExt . ' - file',
				'iconCls' => $cls
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
			//TODO: charset conversion
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
		$path = PATH_site . $path;
		if (@file_exists($path)) {
			//TODO: save only if saving was enabled
			return t3lib_div::writeFile($path, $content);
		}
		return FALSE;
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
		return array(
			'success' => TRUE,
			'fe_p' => $parameter['fe_p'],
			'fe_u' => $parameter['fe_u'],
			'newversion' => $parameter['newversion'],
			'uploadcomment' => $parameter['uploadcomment']
		);
	}

	/**
	 * Prints developer information
	 *
	 * @param string $parameter
	 * @return array
	 */
	public function getExtensionDevelopInfo($extKey) {
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List', $this);
		list($list,) = $extensionList->getInstalledExtensions();
		$extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details', $this);
		return $extensionDetails->extInformationarray($extKey, $list[$extKey]);
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
		$repositoryId = $this->getSettingsObject()->getSelectedRepository();

		$search = $parameters->query;
		$limit = $parameters->start . ', ' . $parameters->limit;
		$orderBy = $parameters->sort;
		$orderDir = $parameters->dir;

		if ($search == '*') {
			$where = '';
		} else {
			$quotedSearch = $GLOBALS['TYPO3_DB']->escapeStrForLike(
				$GLOBALS['TYPO3_DB']->quoteStr($search, 'cache_extensions'),
				'cache_extensions'
			);
			$where = ' AND (extkey LIKE \'%' . $quotedSearch . '%\' OR title LIKE \'%' . $quotedSearch . '%\')';
		}

		$list = tx_em_Database::getExtensionListFromRepository(
			$repositoryId,
			$where,
			$orderBy,
			$orderDir,
			$limit
		);


		return array(
			'length' => $list['count'],
			'data' => $list['results']
		);

	}


	/**
	 * Loads repositories
	 *
	 * @return array
	 */
	public function repositoryUpdateLoad() {
		$settings = $this->getSettings();
		$repositories = tx_em_Database::getRepositories();
		foreach ($repositories as $uid => $repository) {
			$data[] = array(
				'title' => $repository['title'],
				'value' => $repository['uid'],
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
	 * Edit / Create repository
	 *
	 * @formHandler
	 * @param array $parameter
	 * @return array
	 */
	public function repositoryEditFormSubmit($parameter) {
		return array(
			'success' => TRUE,
			'data' => array(
				'params' => $parameter
			),
		);
	}

	/**
	 * Update repository
	 *
	 * @formHandler
	 *
	 * @param array $parameter
	 * @return array
	 */
	public function repositoryUpdate($parameter) {
		//debug($parameter);
		if (!intval($parameter['rep'])) {
			return array(
				'success' => FALSE,
				'errors' => 'no repository choosen'
			);
		}

		$objRepository = t3lib_div::makeInstance('tx_em_Repository', intval($parameter['rep']));
		$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
		$count = $objRepositoryUtility->updateExtList();


		if ($count) {
			$objRepository->setExtensionCount($count);
			$objRepository->setLastUpdate(time());
			tx_em_Database::updateRepository($objRepository);
			return array(
				'success' => TRUE,
				'data' => array(
					'count' => $count
				)
			);
		} else {
			return array(
				'success' => FALSE,
				'errormsg' => 'Your repository is up to date.'
			);
		}
	}


	/*********************************************************************/
	/* Translation Handling                                              */
	/*********************************************************************/


	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function getLanguages() {

		$temp = $this->getSettings();
		$selected = unserialize($temp['selectedLanguages']);
		$theLanguages = t3lib_div::trimExplode('|', TYPO3_languages);
		//drop default
		array_shift($theLanguages);
		$lang = array();
		foreach ($theLanguages as $language) {
			$label = htmlspecialchars($GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:lang_' . $language));
			$flag = @is_file(PATH_typo3 . 'gfx/flags/' . $language . '.gif') ? 'gfx/flags/' . $language . '.gif' : 'gfx/flags/unknown.gif';
			$lang[] = array(
				'label' => $label,
				'lang' => $language,
				'flag' => $flag,
				'selected' => in_array($language, $selected) ? 1 : 0
			);
		}
		return array(
			'length' => count($lang),
			'data' => $lang,
		);

	}

	/**
	 * Saves language selection
	 *
	 * @param array $parameter
	 * @return string
	 */
	public function saveLanguageSelection($parameter) {
		$this->saveSetting('selectedLanguages', serialize($parameter));
			//TODO: use language label
		return 'Saved languages: ' . implode(', ', $parameter);
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
			$this->xmlhandler = t3lib_div::makeInstance('tx_em_Tools_XmlHandler');
			$this->xmlhandler->emObj = $this;
			$mirrorURL = $this->getSettingsObject()->getMirrorURL();

			foreach ($selection as $lang) {
				$fetch = $terConnection->fetchTranslationStatus($extkey, $mirrorURL);
				$localmd5 = '';
				if (!isset($fetch[$lang])) {
					//no translation available
					$result[0][$lang] = 'N/A';
				} else {
					$localmd5 = '';
					if (is_file(PATH_site . 'typo3temp/' . $extkey . '-l10n-' . $lang . '.zip')) {
						$localmd5 = md5_file(PATH_site . 'typo3temp/' . $extkey . '-l10n-' . $lang . '.zip');
					}
					if ($localmd5 !== $fetch[$lang]['md5']) {
						if ($type) {
							//fetch translation
							$ret = $terConnection->updateTranslation($extkey, $lang, $mirrorURL);
							$result[0][$lang] = $ret ? 'updated' : 'failed';
						} else {
							//translation status
							$result[0][$lang] = $localmd5 !== '' ? 'update' : 'new';
						}
					} else {
						//translation is up to date
						$result[0][$lang] = 'ok';
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
		$install->forceDBupdates($extensionKey, $newExtensionList[$extensionKey]);
	}
}

?>