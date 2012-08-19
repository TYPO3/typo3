<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Sebastian Fischer <typo3@evoweb.de>
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
 * Extension repository
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 * @package lang
 * @subpackage ExtensionRepository
 */
class Tx_Lang_Domain_Repository_ExtensionRepository {
	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extensionmanager_Utility_List
	 */
	protected $listUtility;

	/**
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Injects the object manager
	 *
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_List $listUtility
	 * @return void
	 */
	public function injectListUtility(Tx_Extensionmanager_Utility_List $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @return array
	 */
	public function findAll() {
		if (!count($this->extensions)) {
			$availableExtensions = $this->listUtility->getAvailableExtensions();
			$availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensions($availableExtensions);
			$availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation(
				$availableAndInstalledExtensions
			);

			foreach ($availableAndInstalledExtensions as $entry) {
				/** @var $extension Tx_Lang_Domain_Model_Extension */
				$extension = $this->objectManager->create(
					'Tx_Lang_Domain_Model_Extension',
					$entry['key'],
					$entry['title'],
					'../' . $entry['siteRelPath'] . '/ext_icon.gif'
				);
				$extension->setVersionFromString($entry['version']);
				$this->extensions[$entry['key']] = $extension;
			}

				// Sort the list by extension key
			ksort($this->extensions);
		}

		return $this->extensions;
	}
}

?>