<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Driver_DriverRegistry implements t3lib_Singleton {
	/**
	 * @var string[]
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
		t3lib_div::sysLog(
			't3lib_file_Driver_DriverRegistry::__construct: ' . t3lib_utility_Debug::debugTrail(),
			't3lib_file_Driver_DriverRegistry'
		);

		$driverConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'];

		foreach ($driverConfigurations as $shortName => $driverConfig) {
			$shortName = ($shortName ? : $driverConfig['shortName']);
			$this->registerDriverClass(
				$driverConfig['class'],
				$shortName,
				$driverConfig['label'],
				$driverConfig['flexFormDS']
			);
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
		if (!class_exists($className)) {
			throw new InvalidArgumentException("Class $className does not exist.", 1314979197);
		}

		if ($shortName == '') {
			$shortName = $className;
		}

		if (array_key_exists($shortName, $this->drivers)) {
			throw new InvalidArgumentException("Driver $shortName is already registered.", 1314979451);
		}

		$this->drivers[$shortName] = $className;
		$this->driverConfigurations[$shortName] = array(
			'class' => $className,
			'shortName' => $shortName,
			'label' => $label,
			'flexFormDS' => $flexFormDataStructurePathAndFilename
		);

		t3lib_div::sysLog("Registered driver $shortName ($className) " . t3lib_utility_Debug::debugTrail(), 't3lib_file_Driver_DriverRegistry');

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

		foreach ($this->driverConfigurations as $driver) {
			$label = $driver['label'] ? : $driver['class'];

			t3lib_div::loadTCA('sys_file_storage');
			$driverFieldConfig = &$GLOBALS['TCA']['sys_file_storage']['columns']['driver']['config'];
			$driverFieldConfig['items'][] = array($label, $driver['shortName']);

			if ($driver['flexFormDS']) {
				$configurationFieldConfig = &$GLOBALS['TCA']['sys_file_storage']['columns']['configuration']['config'];
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
			throw new InvalidArgumentException('Desired storage is not in the list of available storages.', 1314085990);
		}

		return $this->drivers[$shortName];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/Driver/DriverRegistry.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/Driver/DriverRegistry.php']);
}

?>