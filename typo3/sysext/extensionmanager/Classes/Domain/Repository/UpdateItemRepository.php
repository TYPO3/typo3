<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * A repository for extension update script
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class UpdateItemRepository {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Inject configuration manager
	 *
	 * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Find configuration options by extension
	 *
	 * @param array $extension array with extension information
	 * @return \SplObjectStorage
	 */
	public function findByExtension(array $extension) {
		$updateObjectStorage = new \SplObjectStorage();
		if (file_exists(PATH_site . $extension['siteRelPath'] . '/class.ext_update.php')) {
			require_once(PATH_site . $extension['siteRelPath'] . '/class.ext_update.php');
			if (class_exists('ext_update')) {
				/** @var $updateItem \TYPO3\CMS\Extensionmanager\Domain\Model\UpdateItem */
				$updateItem = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\UpdateItem');
				$updateObject = new \ext_update;
				if (method_exists($updateObject, 'getName')) {
					$updateItem->setName($updateObject->getName());
				} else {
					$updateItem->setName(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extConfTemplate.updateTab', 'extensionmanager'));
				}

				$content = '';
				if (!method_exists($updateObject, 'access') || $updateObject->access()) {
					if (method_exists($updateObject, 'main')) {
						$content = $updateObject->main();
					}
				}
				if (empty($content)) {
					$content = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('extConfTemplate.noUpdateNecessary', 'extensionmanager');
				}
				$updateItem->setContent($content);
				$updateObjectStorage->attach($updateItem);
			}
		}

		return $updateObjectStorage;
	}
}

?>