<?php
namespace TYPO3\CMS\Core\Resource\Driver;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Registry for driver classes.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class DriverRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $drivers = array();

	/**
	 * @var array
	 */
	protected $driverConfigurations = array();

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$driverConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'];
		foreach ($driverConfigurations as $shortName => $driverConfig) {
			$shortName = $shortName ?: $driverConfig['shortName'];
			$this->registerDriverClass($driverConfig['class'], $shortName, $driverConfig['label'], $driverConfig['flexFormDS']);
		}
	}

	/**
	 * Registers a driver class with an optional short name.
	 *
	 * @param string $className
	 * @param string $shortName
	 * @param string $label
	 * @param string $flexFormDataStructurePathAndFilename
	 * @return boolean TRUE if registering succeeded
	 */
	public function registerDriverClass($className, $shortName = NULL, $label = NULL, $flexFormDataStructurePathAndFilename = NULL) {
		// check if the class is available for TYPO3 before registering the driver
		if (!class_exists($className)) {
			throw new \InvalidArgumentException('Class ' . $className . ' does not exist.', 1314979197);
		}
		if ($shortName === '') {
			$shortName = $className;
		}
		if (array_key_exists($shortName, $this->drivers)) {
				// Return immediately without changing configuration
			if ($this->drivers[$shortName] === $className) {
				return TRUE;
			} else {
				throw new \InvalidArgumentException('Driver ' . $shortName . ' is already registered.', 1314979451);
			}
		}
		$this->drivers[$shortName] = $className;
		$this->driverConfigurations[$shortName] = array(
			'class' => $className,
			'shortName' => $shortName,
			'label' => $label,
			'flexFormDS' => $flexFormDataStructurePathAndFilename
		);
		return TRUE;
	}

	/**
	 * @return void
	 */
	public function addDriversToTCA() {
		// Add driver to TCA of sys_file_storage
		if (TYPO3_MODE !== 'BE') {
			return;
		}
		$driverFieldConfig = &$GLOBALS['TCA']['sys_file_storage']['columns']['driver']['config'];
		$configurationFieldConfig = &$GLOBALS['TCA']['sys_file_storage']['columns']['configuration']['config'];
		foreach ($this->driverConfigurations as $driver) {
			$label = $driver['label'] ?: $driver['class'];
			$driverFieldConfig['items'][] = array($label, $driver['shortName']);
			if ($driver['flexFormDS']) {
				$configurationFieldConfig['ds'][$driver['shortName']] = $driver['flexFormDS'];
			}
		}
	}

	/**
	 * Returns a class name for a given class name or short name.
	 *
	 * @param string $shortName
	 * @return string The class name
	 */
	public function getDriverClass($shortName) {
		if (class_exists($shortName) && in_array($shortName, $this->drivers)) {
			return $shortName;
		}
		if (!array_key_exists($shortName, $this->drivers)) {
			throw new \InvalidArgumentException('Desired storage is not in the list of available storages.', 1314085990);
		}
		return $this->drivers[$shortName];
	}

	/**
	 * Checks if the given driver exists
	 *
	 * @param string $shortName Name of the driver
	 * @return boolean TRUE if the driver exists, FALSE otherwise
	 */
	public function driverExists($shortName) {
		return array_key_exists($shortName, $this->drivers);
	}
}


?>