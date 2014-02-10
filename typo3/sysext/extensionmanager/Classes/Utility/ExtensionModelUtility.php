<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <susanne.moog@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Utility for dealing with extension model related helper functions
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class ExtensionModelUtility {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Map a legacy extension array to an object
	 *
	 * @param array $extensionArray
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
	 */
	public function mapExtensionArrayToModel(array $extensionArray) {
		/** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension */
		$extension = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$extension->setExtensionKey($extensionArray['key']);
		if (isset($extensionArray['version'])) {
			$extension->setVersion($extensionArray['version']);
		}
		if (isset($extensionArray['constraints'])) {
			$extension->setDependencies($this->convertDependenciesToObjects(serialize($extensionArray['constraints'])));
		}
		return $extension;
	}

	/**
	 * Converts string dependencies to an object storage of dependencies
	 *
	 * @param string $dependencies
	 * @return \SplObjectStorage
	 */
	public function convertDependenciesToObjects($dependencies) {
		$unserializedDependencies = unserialize($dependencies);
		$dependenciesObject = new \SplObjectStorage();
		foreach ($unserializedDependencies as $dependencyType => $dependencyValues) {
			foreach ($dependencyValues as $dependency => $versions) {
				if ($dependencyType && $dependency) {
					$versionNumbers = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionsStringToVersionNumbers($versions);
					$lowest = $versionNumbers[0];
					if (count($versionNumbers) === 2) {
						$highest = $versionNumbers[1];
					} else {
						$highest = '';
					}
					/** @var $dependencyObject \TYPO3\CMS\Extensionmanager\Domain\Model\Dependency */
					$dependencyObject = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Dependency');
					$dependencyObject->setType($dependencyType);
					$dependencyObject->setIdentifier($dependency);
					$dependencyObject->setLowestVersion($lowest);
					$dependencyObject->setHighestVersion($highest);
					$dependenciesObject->attach($dependencyObject);
					unset($dependencyObject);
				}
			}
		}
		return $dependenciesObject;
	}
}
