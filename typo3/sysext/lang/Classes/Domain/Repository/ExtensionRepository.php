<?php
namespace TYPO3\CMS\Lang\Domain\Repository;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Sebastian Fischer <typo3@evoweb.de>
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
 */
class ExtensionRepository {
	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

	/**
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Inject the list utility
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 * @return void
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
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
				/** @var $extension \TYPO3\CMS\Lang\Domain\Model\Extension */
				$extension = $this->objectManager->get(
					'TYPO3\CMS\Lang\Domain\Model\Extension',
					$entry['key'],
					$entry['title'],
					$this->getExtensionIconWithPath($entry)
				);
				$extension->setVersionFromString($entry['version']);
				$this->extensions[$entry['key']] = $extension;
			}

				// Sort the list by extension key
			ksort($this->extensions);
		}

		return $this->extensions;
	}

	/**
	 * @param array $extensionEntry
	 * @return string
	 */
	protected function getExtensionIconWithPath($extensionEntry) {
		$extensionIcon = $GLOBALS['TYPO3_LOADED_EXT'][$extensionEntry['key']]['ext_icon'];
		if (empty($extensionIcon)) {
			$extensionIcon = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionIcon(PATH_site . $extensionEntry['siteRelPath'] . '/');
		}

		if (empty($extensionIcon)) {
			$extensionIcon = '/typo3/clear.gif';
		} else {
			$extensionIcon = '../' . $extensionEntry['siteRelPath'] . '/' . $extensionIcon;
		}

		return $extensionIcon;
	}
}

?>