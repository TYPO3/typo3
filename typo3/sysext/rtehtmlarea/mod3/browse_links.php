<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Adapted for htmlArea RTE by Stanislas Rolland
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:rtehtmlarea/mod3/locallang.xml');
$LANG->includeLLFile('EXT:rtehtmlarea/htmlarea/locallang_dialogs.xml');

/**
 * Script class for the Element Browser window.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_rtehtmlarea_SC_browse_links {
	public $mode = 'rte';
	public $button = 'link';
	protected $content = '';

	/**
	 * Main function, rendering the element browser in RTE mode.
	 *
	 * @return	void
	 */
	function main()	{
			// Setting alternative web browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
		$altMountPoints = trim($GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));

		// Clear temporary DB mounts
		$tmpMount = t3lib_div::_GET('setTempDBmount');
		if (isset($tmpMount)) {
			$GLOBALS['BE_USER']->setAndSaveSessionData('pageTree_temporaryMountPoint', intval($tmpMount));
		}

		// Set temporary DB mounts
		$tempDBmount = intval($GLOBALS['BE_USER']->getSessionData('pageTree_temporaryMountPoint'));
		if ($tempDBmount) {
	 		$altMountPoints = $tempDBmount;
		}

		if ($altMountPoints) {
			$GLOBALS['BE_USER']->groupData['webmounts'] = implode(',', array_unique(t3lib_div::intExplode(',', $altMountPoints)));
			$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
		}
			// Setting alternative file browsing mounts (ONLY local to browse_links.php this script so they stay "read-only")
		$altMountPoints = trim($GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.altElementBrowserMountPoints'));
		if ($altMountPoints) {
			$altMountPoints = t3lib_div::trimExplode(',', $altMountPoints);
			foreach ($altMountPoints as $filePathRelativeToFileadmindir) {
				$GLOBALS['BE_USER']->addFileMount('', $filePathRelativeToFileadmindir, $filePathRelativeToFileadmindir, 1, 'readonly');
			}
			$GLOBALS['FILEMOUNTS'] = $GLOBALS['BE_USER']->returnFilemounts();
		}
			// Render type by user function
		$browserRendered = false;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering'] as $classRef) {
				$browserRenderObj = t3lib_div::getUserObj($classRef);
				if (is_object($browserRenderObj) && method_exists($browserRenderObj, 'isValid') && method_exists($browserRenderObj, 'render'))	{
					if ($browserRenderObj->isValid($this->mode, $this)) {
						$this->content .=  $browserRenderObj->render($this->mode, $this);
						$browserRendered = TRUE;
						break;
					}
				}
			}
		}
			// If type was not rendered, use default rendering functions
		if (!$browserRendered) {
			$GLOBALS['SOBE']->browser = t3lib_div::makeInstance('tx_rtehtmlarea_browse_links');
			$GLOBALS['SOBE']->browser->init();
			$modData = $GLOBALS['BE_USER']->getModuleData('browse_links.php','ses');
			list($modData, $store) = $GLOBALS['SOBE']->browser->processSessionData($modData);
			$GLOBALS['BE_USER']->pushModuleData('browse_links.php',$modData);
			$this->content = $GLOBALS['SOBE']->browser->main_rte();
		}
	}

	/**
	 * Print module content
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/browse_links.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/browse_links.php']);
}

	// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_SC_browse_links');
$SOBE->main();
$SOBE->printContent();

?>