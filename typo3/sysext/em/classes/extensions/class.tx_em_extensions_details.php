<?php
/* **************************************************************
*  Copyright notice
*
*  (c) webservices.nl
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
*  (c) 2010 Steffen Kamper <steffen@typo3.org>
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
/* $Id: class.tx_em_extensions_details.php 2058 2010-03-17 09:39:15Z steffenk $ */

/**
 * This class handles extension details
 *
 */

class tx_em_Extensions_Details {

	protected $maxUploadSize = 31457280;
	protected $descrTable = '_MOD_tools_em';
	protected $parentObject;

	protected $categories;
	protected $states;

	/**
	 * Instance of EM API
	 *
	 * @var tx_em_API
	 */
	protected $api;

	/**
	 * Class for install extensions
	 *
	 * @var em_install
	 */
	public $install;

	/**
	 * Constructor
	 *
	 * @param object $parentObject
	 */
	public function __construct($parentObject = NULL) {
		$this->parentObject = $parentObject;
		$this->api = t3lib_div::makeInstance('tx_em_API');
		$this->install = t3lib_div::makeInstance('tx_em_Install', $this);
		$GLOBALS['LANG']->includeLLFile(t3lib_extMgm::extPath('em') . 'language/locallang.xml');
		$this->categories = array(
			'be' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_BE'),
			'module' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_BE_modules'),
			'fe' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_FE'),
			'plugin' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_FE_plugins'),
			'misc' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_miscellanous'),
			'services' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_services'),
			'templates' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_templates'),
			'example' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_examples'),
			'doc' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_tools_em.xml:category_documentation')
		);
		$this->states = tx_em_Tools::getStates();
	}


	/**
	 * Reads $confFilePath (a module $conf-file) and returns information on the existence of TYPO3_MOD_PATH definition and MCONF_name
	 *
	 * @param	string		Absolute path to a "conf.php" file of a module which we are analysing.
	 * @return	array		Information found.
	 * @see writeTYPO3_MOD_PATH()
	 */
	function modConfFileAnalysis($confFilePath) {
		$lines = explode(LF, t3lib_div::getUrl($confFilePath));
		$confFileInfo = array();
		$confFileInfo['lines'] = $lines;
		$reg = array();

		foreach ($lines as $k => $l) {
			$line = trim($l);

			unset($reg);
			if (preg_match('/^define[[:space:]]*\([[:space:]]*["\']TYPO3_MOD_PATH["\'][[:space:]]*,[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*\)[[:space:]]*;/', $line, $reg)) {
				$confFileInfo['TYPO3_MOD_PATH'] = array($k, $reg);
			}

			unset($reg);
			if (preg_match('/^\$MCONF\[["\']?name["\']?\][[:space:]]*=[[:space:]]*["\']([[:alnum:]_]+)["\'];/', $line, $reg)) {
				$confFileInfo['MCONF_name'] = array($k, $reg);
			}
		}
		return $confFileInfo;
	}

	/**
	 * Check if upload folder / "createDir" directories should be created.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		HTML content.
	 */
	function checkUploadFolder($extKey, $extInfo) {

		// Checking for upload folder:
		$uploadFolder = PATH_site . tx_em_Tools::uploadFolder($extKey);
		if ($extInfo['EM_CONF']['uploadfolder'] && !@is_dir($uploadFolder)) {
			if (t3lib_div::_POST('_uploadfolder')) { // CREATE dir:
				t3lib_div::mkdir($uploadFolder);
				$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
	<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=../../">
</HEAD>
</HTML>';
				t3lib_div::writeFile($uploadFolder . 'index.html', $indexContent);
			} else { // Show checkbox / HTML for creation:
				$content .= '
					<br /><h3>' . $GLOBALS['LANG']->getLL('checkUploadFolder_create_upload_folder') . '</h3>
					<p>' . sprintf($GLOBALS['LANG']->getLL('checkUploadFolder_upload_folder_needed'),
					tx_em_Tools::uploadFolder($extKey)
				) . '<br />
						<label for="check_uploadfolder">' . sprintf($GLOBALS['LANG']->getLL('checkUploadFolder_create_dir'),
					tx_em_Tools::uploadFolder($extKey)
				) . '</label>
						<input type="checkbox" name="_uploadfolder" id="check_uploadfolder" checked="checked" value="1" /><br />
					</p>
				';
			}
		}

		// Additional directories that should be created:
		if ($extInfo['EM_CONF']['createDirs']) {
			$createDirs = array_unique(t3lib_div::trimExplode(',', $extInfo['EM_CONF']['createDirs'], 1));

			foreach ($createDirs as $crDir) {
				if (!@is_dir(PATH_site . $crDir)) {
					if (t3lib_div::_POST('_createDir_' . md5($crDir))) { // CREATE dir:

						// Initialize:
						$crDirStart = '';
						$dirs_in_path = explode('/', preg_replace('/\/$/', '', $crDir));

						// Traverse each part of the dir path and create it one-by-one:
						foreach ($dirs_in_path as $dirP) {
							if (strcmp($dirP, '')) {
								$crDirStart .= $dirP . '/';
								if (!@is_dir(PATH_site . $crDirStart)) {
									t3lib_div::mkdir(PATH_site . $crDirStart);
									$finalDir = PATH_site . $crDirStart;
								}
							} else {
								throw new RuntimeException(
									'TYPO3 Fatal Error: ' . sprintf($GLOBALS['LANG']->getLL('checkUploadFolder_error'), PATH_site . $crDir),
									1270853982
								);
							}
						}
						if ($finalDir) {
							$indexContent = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<HTML>
<HEAD>
	<TITLE></TITLE>
<META http-equiv=Refresh Content="0; Url=/">
</HEAD>
</HTML>';
							t3lib_div::writeFile($finalDir . 'index.html', $indexContent);
						}
					} else { // Show checkbox / HTML for creation:
						$md5CrDir = md5($crDir);
						$content .= '
							<br />
							<h3>' . $GLOBALS['LANG']->getLL('checkUploadFolder_create_folder') . '</h3>
							<p>' . sprintf($GLOBALS['LANG']->getLL('checkUploadFolder_folder_needed'),
							$crDir
						) . '<br />
								<label for="check_createDir_' . $md5CrDir . '">' . sprintf($GLOBALS['LANG']->getLL('checkUploadFolder_create_dir'),
							$crDir
						) . '</label>
								<input type="checkbox" name="_createDir_' . $md5CrDir . '" id="check_createDir_' . $md5CrDir . '" checked="checked" value="1" /><br />
							</p>
						';
					}
				}
			}
		}

		return $content;
	}


	/**
	 * Processes return-data from online repository.
	 * Currently only the returned emconf array is written to extension.
	 *
	 * @param	array		Command array returned from TER
	 * @return	string		Message
	 */
	function uploadExtensionToTER($em) {
		$msg = '';
		$response = $this->parentObject->terConnection->uploadToTER($em);

		if (!is_array($response)) {
			return $response;
		}

		if ($response['resultCode'] == TX_TER_RESULT_EXTENSIONSUCCESSFULLYUPLOADED) {
			$em['extInfo']['EM_CONF']['version'] = $response['version'];
			$response['resultMessages'][] = sprintf($GLOBALS['LANG']->getLL('terCommunication_ext_version'),
				$response['version']
			);
			$response['resultMessages'][] = $this->updateLocalEM_CONF($em['extKey'], $em['extInfo']);
		}

		$msg = '<ul><li>' . implode('</li><li>', $response['resultMessages']) . '</li></ul>';
		return $msg;
	}

	/**
	 * Forces update of local EM_CONF. This will renew the information of changed files.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	string		Status message
	 */
	function updateLocalEM_CONF($extKey, $extInfo) {
		$extInfo['EM_CONF']['_md5_values_when_last_written'] = serialize($this->serverExtensionMD5array($extKey, $extInfo));
		$emConfFileContent = $this->construct_ext_emconf_file($extKey, $extInfo['EM_CONF']);

		$absPath = tx_em_Tools::getExtPath($extKey, $extInfo['type']);
		$emConfFileName = $absPath . 'ext_emconf.php';
		if ($emConfFileContent) {

			if (@is_file($emConfFileName)) {
				if (t3lib_div::writeFile($emConfFileName, $emConfFileContent) === true) {
					return sprintf($GLOBALS['LANG']->getLL('updateLocalEM_CONF_ok'),
						substr($emConfFileName, strlen($absPath)));
				} else {
					return '<strong>' . sprintf($GLOBALS['LANG']->getLL('updateLocalEM_CONF_not_writable'),
						$emConfFileName) . '</strong>';
				}
			} else {
				return ('<strong>' . sprintf($GLOBALS['LANG']->getLL('updateLocalEM_CONF_not_found'),
					$emConfFileName) . '</strong>');
			}
		} else {
			return sprintf($GLOBALS['LANG']->getLL('updateLocalEM_CONF_no_content'),
				substr($emConfFileName, strlen($absPath)));
		}
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	array		MD5-keys
	 */
	function serverExtensionMD5array($extKey, $conf) {

		// Creates upload-array - including filelist.
		$mUA = $this->makeUploadarray($extKey, $conf);

		$md5Array = array();
		if (is_array($mUA['FILES'])) {

			// Traverse files.
			foreach ($mUA['FILES'] as $fN => $d) {
				if ($fN != 'ext_emconf.php') {
					$md5Array[$fN] = substr($d['content_md5'], 0, 4);
				}
			}
		} else {
			debug(array($mUA, $conf), 'serverExtensionMD5Array:' . $extKey);
		}
		return $md5Array;
	}

	/*******************************************
	 *
	 * Compiling upload information, emconf-file etc.
	 *
	 *******************************************/

	/**
	 * Compiles the ext_emconf.php file
	 *
	 * @param	string		Extension key
	 * @param	array		EM_CONF array
	 * @return	string		PHP file content, ready to write to ext_emconf.php file
	 */
	function construct_ext_emconf_file($extKey, $EM_CONF) {

		// clean version number:
		$vDat = tx_em_Tools::renderVersion($EM_CONF['version']);
		$EM_CONF['version'] = $vDat['version'];

		$code = '<?php

########################################################################
# Extension Manager/Repository config file for ext "' . $extKey . '".
#
# Auto generated ' . date('d-m-Y H:i') . '
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = ' . tx_em_Tools::arrayToCode($EM_CONF, 0) . ';

?>';
		return str_replace(CR, '', $code);
	}

	/**
	 * Make upload array out of extension
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @return	mixed		Returns array with extension upload array on success, otherwise an error string.
	 */
	function makeUploadarray($extKey, $conf) {
		$extPath = tx_em_Tools::getExtPath($extKey, $conf['type']);

		if ($extPath) {

			// Get files for extension:
			$fileArr = array();
			$fileArr = t3lib_div::getAllFilesAndFoldersInPath($fileArr, $extPath, '', 0, 99, $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging']);

			// Calculate the total size of those files:
			$totalSize = 0;
			foreach ($fileArr as $file) {
				$totalSize += filesize($file);
			}

			// If the total size is less than the upper limit, proceed:
			if ($totalSize < $this->maxUploadSize) {

				// Initialize output array:
				$uploadArray = array();
				$uploadArray['extKey'] = $extKey;
				$uploadArray['EM_CONF'] = $conf['EM_CONF'];
				$uploadArray['misc']['codelines'] = 0;
				$uploadArray['misc']['codebytes'] = 0;

				$uploadArray['techInfo'] = $this->install->makeDetailedExtensionAnalysis($extKey, $conf, 1);

				// Read all files:
				foreach ($fileArr as $file) {
					$relFileName = substr($file, strlen($extPath));
					$fI = pathinfo($relFileName);
					if ($relFileName != 'ext_emconf.php') { // This file should be dynamically written...
						$uploadArray['FILES'][$relFileName] = array(
							'name' => $relFileName,
							'size' => filesize($file),
							'mtime' => filemtime($file),
							'is_executable' => (TYPO3_OS == 'WIN' ? 0 : is_executable($file)),
							'content' => t3lib_div::getUrl($file)
						);
						if (t3lib_div::inList('php,inc', strtolower($fI['extension']))) {
							$uploadArray['FILES'][$relFileName]['codelines'] = count(explode(LF, $uploadArray['FILES'][$relFileName]['content']));
							$uploadArray['misc']['codelines'] += $uploadArray['FILES'][$relFileName]['codelines'];
							$uploadArray['misc']['codebytes'] += $uploadArray['FILES'][$relFileName]['size'];

							// locallang*.php files:
							if (substr($fI['basename'], 0, 9) == 'locallang' && strstr($uploadArray['FILES'][$relFileName]['content'], '$LOCAL_LANG')) {
								$uploadArray['FILES'][$relFileName]['LOCAL_LANG'] = tx_em_Tools::getSerializedLocalLang($file, $uploadArray['FILES'][$relFileName]['content']);
							}
						}
						$uploadArray['FILES'][$relFileName]['content_md5'] = md5($uploadArray['FILES'][$relFileName]['content']);
					}
				}

				// Return upload-array:
				return $uploadArray;
			} else {
				return sprintf($GLOBALS['LANG']->getLL('makeUploadArray_error_size'),
					$totalSize, t3lib_div::formatSize($this->maxUploadSize));
			}
		} else {
			return sprintf($GLOBALS['LANG']->getLL('makeUploadArray_error_path'),
				$extKey);
		}
	}


	/**
	 * Prints a table with extension information in it.
	 *
	 * @param	string		Extension key
	 * @param	array		Extension information array
	 * @param	boolean		If set, the information array shows information for a remote extension in TER, not a local one.
	 * @return	string		HTML content.
	 */
	function extInformationarray($extKey, $extInfo, $remote = 0) {
		$emConf = $extInfo['EM_CONF'];

		$lines = array();
		$lines[] = '
			<tr class="t3-row-header"><td colspan="2"><strong>' . $GLOBALS['LANG']->getLL('extInfoArray_general_info') . '</strong></td></tr>';

		// row for the extension title
		$key = 'title';
		$dataCol = $emConf['_icon'] . $emConf[$key];
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the extension description
		$key = 'description';
		$dataCol = nl2br(htmlspecialchars($emConf[$key]));
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the extension author
		$key = 'author';
		$dataCol = tx_em_Tools::wrapEmail($emConf['author'] . ($emConf['author_email'] ? ' <' . $emConf['author_email'] . '>' : ''), $emConf['author_email']);
		if ($emConf['author_company']) {
			$dataCol .= ', ' . $emConf['author_company'];
		}
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the version
		$key = 'version';
		$dataCol = $emConf[$key];
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the category
		$key = 'category';
		$dataCol = $this->categories[$emConf[$key]];
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the state
		$key = 'state';
		$dataCol = $this->states[$emConf[$key]];
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the shy state
		$key = 'shy';
		if ($emConf[$key]) {
			$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
		} else {
			$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
		}
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the internal state
		$key = 'internal';
		if ($emConf[$key]) {
			$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
		} else {
			$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
		}
		$lines[] = array(
			$this->headerCol($key),
			$dataCol
		);

		// row for the dependencies
		$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_depends_on');
		$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_dependencies', $headerCol);
		$dataCol = tx_em_Tools::depToString($emConf['constraints']);
		$lines[] = array(
			$headerCol,
			$dataCol
		);

		// row for the conflicts
		$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_conflicts_with');
		$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_conflicts', $headerCol);
		$dataCol = tx_em_Tools::depToString($emConf['constraints'], 'conflicts');
		$lines[] = array(
			$headerCol,
			$dataCol
		);

		// row for the suggestions
		$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_suggests');
		$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_conflicts', $headerCol);
		$dataCol = tx_em_Tools::depToString($emConf['constraints'], 'suggests');
		$lines[] = array(
			$this->headerCol('suggests'),
			$dataCol
		);

		if (!$remote) {

			$key = 'priority';
			$lines[] = array(
				$this->headerCol($key),
				$emConf[$key]
			);


			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_clear_cache');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_clearCacheOnLoad', $headerCol);
			if ($emConf['clearCacheOnLoad']) {
				$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
			} else {
				$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
			}
			$lines[] = array(
				$headerCol,
				$dataCol
			);

			$key = 'module';
			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_incl_modules');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_module', $headerCol);
			$lines[] = array(
				$headerCol,
				$emConf[$key]
			);

			$key = 'lockType';
			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_lock_type');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_lockType', $headerCol);
			$lines[] = array(
				$headerCol,
				($emConf[$key] ? $emConf[$key] : '')
			);

			$key = 'doNotLoadInFE';
			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_load_in_frontend');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_doNotLoadInFE', $headerCol);
			if (!$emConf['doNotLoadInFE']) {
				$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
			} else {
				$dataCol = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
			}
			$lines[] = array(
				$headerCol,
				$dataCol
			);

			$key = 'modify_tables';
			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_modifies_tables');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_modify_tables', $headerCol);
			$lines[] = array(
				$headerCol,
				$emConf[$key]
			);


			// Installation status:
			$techInfo = $this->install->makeDetailedExtensionAnalysis($extKey, $extInfo, 1);
			$lines[] = array('<tr><td colspan="2">&nbsp;</td></tr>');
			$lines[] = array('<tr class="t3-row-header"><td colspan="2"><strong>' . $GLOBALS['LANG']->getLL('extInfoArray_inst_status') . '</strong></td></tr>');


			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_inst_type');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_type', $headerCol);
			$dataCol = $this->api->typeLabels[$extInfo['type']] . ' - <em>' . $this->api->typeDescr[$extInfo['type']] . '</em>';
			$lines[] = array($headerCol, $dataCol);


			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_inst_twice');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_doubleInstall', $headerCol);
			$dataCol = $this->extInformationArray_dbInst($extInfo['doubleInstall'], $extInfo['type']);
			$lines[] = array($headerCol, $dataCol);


			if (is_array($extInfo['files'])) {
				sort($extInfo['files']);
				$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_root_files');
				$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_rootfiles', $headerCol);
				$dataCol = implode('<br />', $extInfo['files']);
				$lines[] = array($headerCol, $dataCol);
			}

			if ($techInfo['tables'] || $techInfo['static'] || $techInfo['fields']) {
				if (!$remote && t3lib_extMgm::isLoaded($extKey)) {
					$tableStatus = tx_em_Tools::rfw(($techInfo['tables_error'] ?
							'<strong>' . $GLOBALS['LANG']->getLL('extInfoArray_table_error') . '</strong><br />' .
									$GLOBALS['LANG']->getLL('extInfoArray_missing_fields') : '') .
							($techInfo['static_error'] ?
									'<strong>' . $GLOBALS['LANG']->getLL('extInfoArray_static_table_error') . '</strong><br />' .
											$GLOBALS['LANG']->getLL('extInfoArray_static_tables_missing_empty') : ''));
				} else {
					$tableStatus = $techInfo['tables_error'] || $techInfo['static_error'] ?
							$GLOBALS['LANG']->getLL('extInfoArray_db_update_needed') : $GLOBALS['LANG']->getLL('extInfoArray_tables_ok');
				}
			}

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_db_requirements');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_dbReq', $headerCol);
			$dataCol = $this->extInformationArray_dbReq($techInfo, 1);
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_db_status');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_dbStatus', $headerCol);
			$lines[] = array($headerCol, $tableStatus);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_flags');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_flags', $headerCol);
			$dataCol = (is_array($techInfo['flags']) ? implode('<br />', $techInfo['flags']) : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_config_template');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_conf', $headerCol);
			$dataCol = ($techInfo['conf'] ? $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes') : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_typoscript_files');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_TSfiles', $headerCol);
			$dataCol = (is_array($techInfo['TSfiles']) ? implode('<br />', $techInfo['TSfiles']) : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_language_files');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_locallang', $headerCol);
			$dataCol = (is_array($techInfo['locallang']) ? implode('<br />', $techInfo['locallang']) : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_upload_folder');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_uploadfolder', $headerCol);
			$dataCol = ($techInfo['uploadfolder'] ? $techInfo['uploadfolder'] : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_create_directories');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_createDirs', $headerCol);
			$dataCol = (is_array($techInfo['createDirs']) ? implode('<br />', $techInfo['createDirs']) : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_module_names');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_moduleNames', $headerCol);
			$dataCol = (is_array($techInfo['moduleNames']) ? implode('<br />', $techInfo['moduleNames']) : '');
			$lines[] = array($headerCol, $dataCol);

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_class_names');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_classNames', $headerCol);
			$dataCol = (is_array($techInfo['classes']) ? implode('<br />', $techInfo['classes']) : '');
			$lines[] = array($headerCol, $dataCol);

			$currentMd5Array = $this->serverExtensionMD5array($extKey, $extInfo);

			$msgLines = array();
			if (strcmp($extInfo['EM_CONF']['_md5_values_when_last_written'], serialize($currentMd5Array))) {
				$msgLines[] = tx_em_Tools::rfw('<br /><strong>' . $GLOBALS['LANG']->getLL('extInfoArray_difference_detected') . '</strong>');
				$affectedFiles = tx_em_Tools::findMD5ArrayDiff($currentMd5Array, unserialize($extInfo['EM_CONF']['_md5_values_when_last_written']));
				if (count($affectedFiles)) {
					$msgLines[] = '<br /><strong>' . $GLOBALS['LANG']->getLL('extInfoArray_modified_files') . '</strong><br />' .
							tx_em_Tools::rfw(implode('<br />', $affectedFiles));
				}
			}

			$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_files_changed');
			$headerCol = t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_filesChanged', $headerCol);
			$dataCol = implode('<br />', $msgLines);
			$lines[] = array($headerCol, $dataCol);
		}

		$output = '';
		foreach ($lines as $cols) {
			// if it's just one line, we assume it's a headline,
			// thus no need to wrap it in HTML table tags
			if (count($cols) == 1) {
				$output .= $cols[0];
			} else {
				$output .= '
					<tr class="bgColor4">
						<td>' . $cols[0] . '</td>
						<td>' . $cols[1] . '</td>
					</tr>';
			}
		}


		return '<table border="0" cellpadding="1" cellspacing="2">
					' . $output . '
				</table>';
	}

	/**
	 * Returns HTML with information about database requirements
	 *
	 * @param	array		Technical information array
	 * @param	boolean		Table header displayed
	 * @return	string		HTML content.
	 */
	function extInformationArray_dbReq($techInfo, $tableHeader = 0) {
		return nl2br(trim((is_array($techInfo['tables']) ?
				($tableHeader ?
						"\n\n<strong>" . $GLOBALS['LANG']->getLL('extDumpTables_tables') . "</strong>\n" : '') .
						implode(LF, $techInfo['tables']) : '') .
				(is_array($techInfo['static']) ?
						"\n\n<strong>" . $GLOBALS['LANG']->getLL('extBackup_static_tables') . "</strong>\n" .
								implode(LF, $techInfo['static']) : '') .
				(is_array($techInfo['fields']) ?
						"\n\n<strong>" . $GLOBALS['LANG']->getLL('extInfoArray_additional_fields') . "</strong>\n" .
								implode('<hr />', $techInfo['fields']) : '')));
	}

	/**
	 * Double install warning.
	 *
	 * @param	string		Double-install string, eg. "LG" etc.
	 * @param	string		Current scope, eg. "L" or "G" or "S"
	 * @return	string		Message
	 */
	function extInformationArray_dbInst($dbInst, $current) {
		if (strlen($dbInst) > 1) {
			$others = array();
			for ($a = 0; $a < strlen($dbInst); $a++) {
				if (substr($dbInst, $a, 1) != $current) {
					$others[] = '"' . $this->api->typeLabels[substr($dbInst, $a, 1)] . '"';
				}
			}
			return tx_em_Tools::rfw(
				sprintf($GLOBALS['LANG']->getLL('extInfoArray_double_installation_infotext'),
					implode(' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:and') . ' ', $others),
					$this->api->typeLabels[$current]
				)
			);
		} else {
			return '';
		}
	}


	/**
	 * Returns help text if applicable.
	 *
	 * @param	string		Help text key
	 * @return	string		HTML table cell
	 * @deprecated since TYPO3 4.5, will be removed in TYPO3 4.7
	 */
	function helpCol($key) {
		global $BE_USER;
		if ($BE_USER->uc['edit_showFieldHelp']) {
			if (empty($key)) {
				return '<td>&nbsp;</td>';
			}
			else {
				return t3lib_BEfunc::cshItem($this->descrTable, 'emconf_' . $key, $GLOBALS['BACK_PATH'], '<td>|</td>');
			}
		}
		else {
			return '';
		}
	}

	/**
	 * Returns the header column (for the extension details item), and applies help text if available
	 *
	 * @param	string	field key
	 * @return	string	HTML ready to go
	 */
	function headerCol($key) {
		$headerCol = $GLOBALS['LANG']->getLL('extInfoArray_' . $key);
		return t3lib_BEfunc::wrapInHelp($this->descrTable, 'emconf_' . $key, $headerCol);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/extensions/class.tx_em_extensions_details.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/extensions/class.tx_em_extensions_details.php']);
}

?>