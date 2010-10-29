<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Update extension list task
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage em
 */
class em_tasks_UpdateExtensionList extends tx_scheduler_Task {
	/**
	 * Public method, usually called by scheduler.
	 *
	 * @return boolean True on success
	 */
	public function execute() {
			// Throws exceptions if something goes wrong
		$this->updateExtensionlist();

		return(TRUE);
	}

	/**
	 * Update extension list
	 *
	 * @throws em_connection_Exception if fetch from mirror fails
	 * @return void
	 */
	protected function updateExtensionlist() {
		require_once(PATH_typo3 . 'template.php');

		$extensionManager = t3lib_div::makeInstance('SC_mod_tools_em_index');
		$extensionManager->init();

		if (empty($extensionManager->MOD_SETTINGS['mirrorListURL'])) {
			$extensionManager->MOD_SETTINGS['mirrorListURL'] = $GLOBALS['TYPO3_CONF_VARS']['EXT']['em_mirrorListURL'];
		}

		if (is_file(PATH_site . 'typo3temp/extensions.xml.gz')) {
			$localMd5 = md5_file(PATH_site . 'typo3temp/extensions.xml.gz');
		}

		$mirror = $extensionManager->getMirrorURL();
		$remoteMd5 = t3lib_div::getURL($mirror . 'extensions.md5', 0, array(TYPO3_user_agent));
		if (!$remoteMd5) {
			throw new em_connection_Exception(
				'Unable to fetch extension list md5 file from remote mirror.',
				1288121556
			);
		}

		$localExtensionCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('extkey', 'cache_extensions');

		if ($localMd5 !== $remoteMd5 || $localExtensionCount === '0') {
			$remoteXml = t3lib_div::getURL($mirror . 'extensions.xml.gz', 0, array(TYPO3_user_agent));

			if (!$remoteXml) {
				throw new em_connection_Exception(
					'Unable to fetch extension list XML file from remote mirror.',
					1288121557
				);
			}

			t3lib_div::writeFile(PATH_site . 'typo3temp/extensions.xml.gz', $remoteXml);
			$extensionManager->xmlhandler->parseExtensionsXML(PATH_site . 'typo3temp/extensions.xml.gz');
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/em/classes/tasks/class.em_tasks_updateextensionlist.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/em/classes/tasks/class.em_tasks_updateextensionlist.php']);
}

?>
