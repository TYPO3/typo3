<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Steffen Kamper (info@sk-typo3.de)
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
 * $Id: class.em_develop.php 2082 2010-03-21 17:19:42Z steffenk $
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 */


class tx_em_Develop {

	/**
	 * Parent module object
	 *
	 * @var SC_mod_tools_em_index
	 */
	protected $parentObject;

	/**
	 * Develop commands
	 *
	 * @var string
	 */
	protected $command;
	/**
	 * Develop sub commands
	 *
	 * @var string
	 */
	protected $sub;
	/**
	 * Extension key
	 *
	 * @var string
	 */
	protected $extKey;

	/**
	 * Internal Timer
	 *
	 * @var float
	 */
	private $timer;

	/**
	 * Constructor
	 *
	 * @param SC_mod_tools_em_index $parentObject
	 */
	public function __construct(SC_mod_tools_em_index $parentObject) {
		$this->parentObject = $parentObject;

		$this->command = t3lib_div::_GP('devCmd');
		$this->sub = t3lib_div::_GP('sub');
		$this->extKey = t3lib_div::_GP('extkey');
	}

	/**
	 * Menu
	 *
	 * @return string $menu
	 */
	protected function devMenu() {
		$menu = '<input type="button" value="parse extension XML file" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'parseextensions'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="parse mirror XML file" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'parsemirrors'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="repository utility" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'repoutility'))) . '\';" />';
		$menu .= '<hr /><input type="button" value="get Extlist" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'extList'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="Single Ext" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'single','sub'=>'conf'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="Single Ext Update" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'single','sub'=>'update'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="Single Ext Config" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'single','sub'=>'config'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="Single Ext Files" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'single','sub'=>'files'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="Single Ext DevInfo" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'single','sub'=>'devinfo'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="get Settings" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'settings'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="get Labels" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'labels'))) . '\';" />';
		$menu .= '<hr />';
		$menu .= '<input type="button" value="get remote extlist" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'remoteext'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="get installed extkeys" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'instextkeys'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="search for \'temp\'" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'searchremote'))) . '\';" />';
		$menu .= '&nbsp;<input type="button" value="temp. test" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'temp'))) . '\';" />';
		$menu .= '<hr />';
		$menu .= '<input type="button" value="install extension" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'installext'))) . '\';" />';
		$menu .= '<input type="button" value="enable extension" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'enableext'))) . '\';" />';
		$menu .= '<input type="button" value="disable extension" onclick="document.location.href=\'' . htmlspecialchars(t3lib_div::linkThisScript(array('devCmd'=>'disableext'))) . '\';" />';
		$menu .= '<hr />';
		return $menu;
	}
	/**
	 * Render module content
	 *
	 * @return string $content
	 */
	public function renderModule() {

			// Override content output - we now do that ourselves:
			//prepare docheader
		$docHeaderButtons = $this->parentObject->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => $this->parentObject->getFuncMenu(),
		);

		$content = '';
		$this->parentObject->content .= $this->parentObject->doc->section('Welcome Dev\'s', $content, 0, 1);

			// Setting up the buttons and markers for docheader
		$content = $this->parentObject->doc->startPage('Extension Manager');
		$content .= $this->parentObject->doc->moduleBody($this->parentObject->pageinfo, $docHeaderButtons, $markers);
		$contentParts = explode('###CONTENT###', $content);

		echo $contentParts[0] . $this->parentObject->content;
		echo $this->devMenu();

		#


		switch ($this->command) {
			case 'parseextensions':
				$this->timerStart();

				echo("<h1>Extensions</h1><pre>");
				$objRepository = t3lib_div::makeInstance('tx_em_Repository');
				$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
				$mirrors = $objRepositoryUtility->updateExtList();

				echo("</pre><p>Will start import database only if required!</p>\r\n");

				$time = $this->timerStop();
				echo '<hr />Processing time: ' . $time . ' sec<br>';
					// backend memory overhead usually 4 MB in comparison to eID invocation
				if(function_exists('memory_get_peak_usage')) {
					$memSize = memory_get_peak_usage();
					echo 'Maximum memory usage for this PHP process: ' . t3lib_div::formatSize($memSize) . ' (' . $memSize . ')';
				}
			break;
			case 'parsemirrors':
				$this->timerStart();

				$objRepository = t3lib_div::makeInstance('tx_em_Repository');
				$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);
				$mirrors = $objRepositoryUtility->getMirrors(TRUE)->getMirror();
				echo("<h1>Mirrors</h1>");
				echo("<p>Mirror file fetched from repository</p>\r\n");
				echo("<h2>Currently selected mirror (non persisted)</h2>\r\n");
				echo("<p>");
				echo("<b>Title:</b> " . $mirrors['title'] . "<br>\r\n");
				echo("<b>Host:</b> " . $mirrors['host'] . "<br>\r\n");
				echo("</p>");

				$time = $this->timerStop();
				echo '<hr />Processing time: ' . $time . ' sec<br>';
					// backend memory overhead usually 4 MB in comparison to eID invocation
				if(function_exists('memory_get_peak_usage')) {
					$memSize = memory_get_peak_usage();
					echo 'Maximum memory usage for this PHP process: ' . t3lib_div::formatSize($memSize) . ' (' . $memSize . ')';
				}
				break;
			case 'repoutility':
				$objRepository = t3lib_div::makeInstance('tx_em_Repository');
				$objRepositoryUtility = t3lib_div::makeInstance('tx_em_Repository_Utility', $objRepository);

				echo("<h1>Files</h1>");
				echo("<h2>Extension list</h2>");
				echo("<p>");
				echo("<b>Lokale Datei (Extension list):</b> " . $objRepositoryUtility->getLocalExtListFile() . "<br>\r\n");
				echo("<b>Enfernte Datei (Extension list):</b> " . $objRepositoryUtility->getRemoteExtListFile() . "<br>\r\n");
				echo("<b>Entfernte Hash-Datei:</b> " . $objRepositoryUtility->getRemoteExtHashFile() . "<br>\r\n");
				echo("</p>");

				echo("<h2>Mirrors</h2>");
				echo("<p>");
				echo("<b>Lokale Datei (Mirrors):</b> " . $objRepositoryUtility->getLocalMirrorListFile() . "<br>\r\n");
				echo("<b>Enfernte Datei (Mirrors):</b> " . $objRepositoryUtility->getRemoteMirrorListFile() . "<br>\r\n");
				echo("</p>");
				break;
			case 'extList':
				$extList = $this->parentObject->extensionList->getInstalledExtensions(TRUE);
				echo t3lib_utility_Debug::viewArray($extList);
				break;
			case 'settings':
				/* @var $settings em_settings */
				$settings = t3lib_div::makeInstance('tx_em_Settings');
				$s = $settings->getSettings();
				echo '<h2>Settings</h2>';
				echo t3lib_utility_Debug::viewArray($s);
				echo '<h2>Repositories</h2>';

				$selected = $settings->getSelectedRepository();
				echo print_r($selected, TRUE);

				$repos = $settings->getRegisteredRepositories();
				echo t3lib_utility_Debug::viewArray($repos);
				$repo = $settings->getSelectedRepository();
				print_r($repo);
				echo '<h2>Mirrors</h2>';
				echo t3lib_utility_Debug::viewArray(unserialize($s['extMirrors']));
				echo '<h2>Selected Languages</h2>';
				echo t3lib_utility_Debug::viewArray(unserialize($s['selectedLanguages']));
				break;
			case 'single':
				echo $this->singleInfo();
				break;
			case 'labels':
				$labels = tx_em_Tools::getArrayFromLocallang(t3lib_extMgm::extPath('em', 'language/locallang.xml'));
				echo t3lib_utility_Debug::viewArray($labels);
				break;
			default:
			case 'remoteext':
				$this->timerStart();
				$list = tx_em_Database::getExtensionListFromRepository(0);
				echo '<h1>Read Extensionlist from cache_extensions, Repository=1</h1>';
				echo count($list) . ' Extensions read in ' . $this->timerStop() . ' sec.';
				echo '<pre>' . print_r($list, TRUE) . '</pre>';
				break;
			case 'instextkeys':
				$list = t3lib_div::makeInstance('tx_em_Connection_ExtDirectServer');
				$keys = $list->getInstalledExtkeys();
				echo t3lib_utility_Debug::viewArray(tx_em_Database::getExtensionListFromRepository(1));
				echo t3lib_utility_Debug::viewArray($keys);
				break;
			case 'searchremote':
				$quotedSearch = $GLOBALS['TYPO3_DB']->escapeStrForLike(
					$GLOBALS['TYPO3_DB']->quoteStr('temp', 'cache_extensions'),
					'cache_extensions'
				);
				$where = ' AND (extkey LIKE \'%' . $quotedSearch . '%\' OR title LIKE \'%' . $quotedSearch . '%\')';
				$orderBy = '';
				$orderDir = '';
				$limit = '';
				$list = tx_em_Database::getExtensionListFromRepository(
					0,
					$where,
					$orderBy,
					$orderDir,
					$limit
				);
				echo t3lib_utility_Debug::viewArray($list);

				break;
			case 'temp':
				$list = t3lib_div::makeInstance('tx_em_Connection_ExtDirectServer');
				$tmp = $list->fetchTranslations('cms', 0, array('de','fr','ru'));
				echo t3lib_utility_Debug::viewArray($tmp);
				break;
			case 'installext':


				$em = t3lib_div::makeinstance('tx_em_Connection_ExtDirectServer');
				$param['loc'] = 'L'; //local
			    $param['extfile'] = PATH_site . 'fileadmin/test.t3x';
				$param['uploadOverwrite'] = TRUE;
				echo t3lib_utility_Debug::viewArray($em->uploadExtension($param));
				break;
			case 'enableext':
				$em = t3lib_div::makeinstance('tx_em_Connection_ExtDirectServer');
				$em->enableExtension('sktertest');
				echo 'sktertest was enabled';
			default:
				echo 'select a command';
		}


		return;
	}


	/**
	 *
	 */
	protected function singleInfo() {
		$content = '<form action="' . $this->script . '" method="post">
		Extkey: <input type="text" name="extkey" value="' . htmlspecialchars($this->extKey) . '" />
		<input type="submit" value="get" /></form><br />';

		if ($this->extKey && t3lib_extMgm::extPath($this->extKey)) {
			$info = array();
			$extpath = t3lib_extMgm::extPath($this->extKey);
			$path = substr($extpath, 0, -1 * (strlen($this->extKey) + 1));
			$extlist = t3lib_div::makeInstance('tx_em_Extensions_List');
			$extlist->singleExtInfo($this->extKey, $path, $info);
			$info = $info[0];
			$content .= 'Path: ' . $extpath . ' (' . $path . ')<br />';
			$content .= t3lib_utility_Debug::viewArray($info);

			$files = $info['files'];
			$fileArray = array();
			foreach ($files as $file) {
				$fileExt = strtolower(substr($file, strrpos($file, '.') + 1));
				$fileArray[] = array(
					'id' => $file,
					'text' => htmlspecialchars($file),
					'leaf' => true
				);
			}
			$content .= t3lib_utility_Debug::viewArray($fileArray);

			switch ($this->sub) {
				case 'conf':
				case 'update':

					$install = t3lib_div::makeInstance('tx_em_Install');
					$content .= $install->checkDBupdates($this->extKey, $info);
					break;
				case 'config':
					break;
				case 'files':
					$path = $path . $this->extKey . '/';
					$files = t3lib_div::getAllFilesAndFoldersInPath(array(), $path, '', 1, 20, '.svn');
					$content = '<h2>Files from ' . $this->extKey . ' (' . $path . ')</h2>' . t3lib_utility_Debug::viewArray($files);
					break;
				case 'devinfo':
					$extensionDetails = t3lib_div::makeInstance('tx_em_Extensions_Details');
					$content = '<h2>DevInfo from ' . $this->extKey . ' (' . $path . ')</h2>' . $extensionDetails->extInformationarray($this->extKey, $info);
			}
		}

		return $content;
	}

	/**
	 * Start internal timer, used for time measure
	 */
	protected function timerStart() {
		$this->timer = microtime(true);
	}

	/**
	 * Stop internal timer and return difference, used for time measure
	 *
	 * @return float timer difference
	 */
	protected function timerStop() {
		return microtime(true) - $this->timer;
	}
}
//NO XCLASS
?>
