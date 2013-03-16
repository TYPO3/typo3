<?php
namespace TYPO3\CMS\Beuser\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix-source@phorax.com>
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
 */
class ModuleDataStorageService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	const KEY = 'tx_beuser';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Loads module data for user settings or returns a fresh object initially
	 *
	 * @return \TYPO3\CMS\Beuser\Domain\Model\ModuleData
	 */
	public function loadModuleData() {
		$moduleData = $GLOBALS['BE_USER']->getModuleData(self::KEY);
		if (empty($moduleData) || !$moduleData) {
			$moduleData = $this->objectManager->get('TYPO3\\CMS\\Beuser\\Domain\\Model\\ModuleData');
		} else {
			$moduleData = @unserialize($moduleData);
		}
		return $moduleData;
	}

	/**
	 * Persists serialized module data to user settings
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\ModuleData $moduleData
	 * @return void
	 */
	public function persistModuleData(\TYPO3\CMS\Beuser\Domain\Model\ModuleData $moduleData) {
		$GLOBALS['BE_USER']->pushModuleData(self::KEY, serialize($moduleData));
	}

}

?>
