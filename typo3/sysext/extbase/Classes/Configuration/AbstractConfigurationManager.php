<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Abstract base class for a general purpose configuration manager
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
abstract class Tx_Extbase_Configuration_AbstractConfigurationManager implements t3lib_Singleton {

	/**
	 * Default backend storage PID
	 */
	const DEFAULT_BACKEND_STORAGE_PID = 0;

	/**
	 * Storage of the raw TypoScript configuration
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * 1st level configuration cache
	 *
	 * @var array
	 */
	protected $configurationCache = array();

	/**
	 * @param Tx_Extbase_Object_ManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param tslib_cObj $contentObject
	 * @return void
	 */
	public function setContentObject(tslib_cObj $contentObject = NULL) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @return tslib_cObj
	 */
	public function getContentObject() {
		return $this->contentObject;
	}

	/**
	 * Sets the specified raw configuration coming from the outside.
	 * Note that this is a low level method and only makes sense to be used by Extbase internally.
	 *
	 * @param array $configuration The new configuration
	 * @return void
	 */
	public function setConfiguration(array $configuration = array()) {
		// reset 1st level cache
		$this->configurationCache = array();
		$this->configuration = $configuration;
	}


	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	abstract protected function getTypoScriptSetup();

	/**
	 * Resolves the TypoScript reference for $pluginConfiguration[$setting].
	 * In case the setting is a string and starts with "<", we know that this is a TypoScript reference which
	 * needs to be resolved separately.
	 *
	 * @param array $pluginConfiguration The whole plugin configuration
	 * @param string $setting The key inside the $pluginConfiguration to check
	 * @return array The modified plugin configuration
	 */
	protected function resolveTyposcriptReference($pluginConfiguration, $setting) {
		if (is_string($pluginConfiguration[$setting]) && substr($pluginConfiguration[$setting], 0, 1) === '<') {
			$key = trim(substr($pluginConfiguration[$setting], 1));
			$setup = $this->getTypoScriptSetup();
			list(, $newValue) = $this->typoScriptParser->getVal($key, $setup);

			unset($pluginConfiguration[$setting]);
			$pluginConfiguration[$setting . '.'] = $newValue;
		}
		return $pluginConfiguration;
	}

}
?>