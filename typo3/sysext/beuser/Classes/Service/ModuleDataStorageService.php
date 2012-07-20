<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 * Module data storage service.
 * Used to store and retrieve module state (eg. checkboxes, selections).
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @author Nikolas Hagelstein <nikolas.hagelstein@gmail.com>
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_Service_ModuleDataStorageService implements t3lib_Singleton {

	/**
	 * @var string
	 */
	const KEY = 'tx_beuser';

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Loads module data for user settings or returns a fresh object initially
	 *
	 * @return Tx_Beuser_Domain_Model_ModuleData
	 */
	public function loadModuleData() {
		$moduleData = $GLOBALS['BE_USER']->getModuleData(self::KEY);

		if (empty($moduleData) || !$moduleData) {
			$moduleData = $this->objectManager->create('Tx_Beuser_Domain_Model_ModuleData');
		} else {
			$moduleData = @unserialize($moduleData);
		}

		return $moduleData;
	}

	/**
	 * Persists serialized module data to user settings
	 *
	 * @param Tx_Beuser_Domain_Model_ModuleData $moduleData
	 * @return void
	 */
	public function persistModuleData(Tx_Beuser_Domain_Model_ModuleData $moduleData) {
		$GLOBALS['BE_USER']->pushModuleData(self::KEY, serialize($moduleData));
	}
}
?>