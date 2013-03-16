<?php
namespace TYPO3\CMS\Belog\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This class is a wrapper for WebInfo controller of belog.
 * It is registered in ext_tables.php with \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()
 * and called by the info extension via SCbase functionality.
 *
 * Extbase currently provides no way to register a "TBE_MODULES_EXT" module directly,
 * therefore we need to bootstrap extbase on our own here to jump to the WebInfo controller.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class BackendLogModuleBootstrap {

	/**
	 * Dummy method, called by SCbase external object handling
	 *
	 * @return void
	 */
	public function init() {

	}

	/**
	 * Dummy method, called by SCbase external object handling
	 *
	 * @return void
	 */
	public function checkExtObj() {

	}

	/**
	 * Bootstrap extbase and jump to WebInfo controller
	 *
	 * @return string
	 */
	public function main() {
		$configuration = array(
			'extensionName' => 'Belog',
			'pluginName' => 'tools_BelogLog',
			'vendorName' => 'TYPO3\CMS',
		);
		// Yeah, this is ugly. But currently, there is no other direct way
		// in extbase to force a specific controller in backend mode.
		// Overwriting $_GET was the most simple solution here until extbase
		// provides a clean way to solve this.
		$_GET['tx_belog_tools_beloglog']['controller'] = 'WebInfo';
		/** @var $extbaseBootstrap \TYPO3\CMS\Extbase\Core\Bootstrap */
		$extbaseBootstrap = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Core\\Bootstrap');
		return $extbaseBootstrap->run('', $configuration);
	}

}

?>