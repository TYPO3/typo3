<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A general purpose configuration manager used in backend mode.
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
class Tx_Extbase_Configuration_BackendConfigurationManager extends Tx_Extbase_Configuration_AbstractConfigurationManager implements t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $typoScriptSetupCache = NULL;

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	public function loadTypoScriptSetup() {
		if ($this->typoScriptSetupCache === NULL) {
			$template = t3lib_div::makeInstance('t3lib_TStemplate');
				// do not log time-performance information
			$template->tt_track = 0;
			$template->init();
				// Get the root line
			$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');
				// get the rootline for the current page
			$rootline = $sysPage->getRootLine($this->getCurrentPageId());
				// This generates the constants/config + hierarchy info for the template.
			$template->runThroughTemplates($rootline, 0);
			$template->generateConfig();
			$this->typoScriptSetupCache = $template->setup;
		}
		return $this->typoScriptSetupCache;
	}

	/**
	 * Returns the page uid of the current page.
	 * If no page is selected, we'll return the uid of the first root page.
	 *
	 * @return integer current page id. If no page is selected current root page id is returned
	 */
	protected function getCurrentPageId() {
		$pageId = (integer)t3lib_div::_GP('id');
		if ($pageId > 0) {
			return $pageId;
		}

			// get root template
		$rootTemplates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1');
		if (count($rootTemplates) > 0) {
			return $rootTemplates[0]['pid'];
		}

			// get current site root
		$rootPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
		if (count($rootPages) > 0) {
			return $rootPages[0]['uid'];
		}

			// fallback
		return self::DEFAULT_BACKEND_STORAGE_PID;
	}

	/**
	 * We do not want to override anything in the backend.
	 * @return array
	 */
	protected function getContextSpecificFrameworkConfiguration($frameworkConfiguration) {
		return $frameworkConfiguration;
	}
}
?>
