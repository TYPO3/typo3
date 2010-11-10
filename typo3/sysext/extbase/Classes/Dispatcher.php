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
 * This class was the main entry point for extbase extensions before v1.3.0. It was replaced by the class
 * Tx_Extbase_Bootstrap in combination with the class Tx_Extbase_MVC_Dispatcher to separate responsibilities.
 *
 * The use of static functions is deprecated since 1.3.0 and will be removed in 1.5.0.
 *
 * @package Extbase
 * @version $ID:$
 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0
 * @see Tx_Extbase_Bootstrap, Tx_Extbase_MVC_Dispatcher
 */
class Tx_Extbase_Dispatcher {

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected static $configurationManager;

	/**
	 * @var Tx_Extbase_Persistence_Manager
	 */
	protected static $persistenceManager;

	/**
	 * Injects the Configuration Manager
	 *
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface An instance of the Configuration Manager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		self::$configurationManager = $configurationManager;
	}

	/**
	 * Injects the Persistence Manager
	 *
	 * @param Tx_Extbase_Persistence_Manager An instance of the Persistence Manager
	 * @return void
	 */
	public function injectPersistenceManager(Tx_Extbase_Persistence_Manager $persistenceManager) {
		self::$persistenceManager = $persistenceManager;
	}

	/**
	 * Returns the Configuration Manager.
	 *
	 * @return Tx_Extbase_Configuration_Manager An instance of the Configuration Manager
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0
	 */
	public static function getConfigurationManager() {
		t3lib_div::logDeprecatedFunction();
		return self::$configurationManager;
	}

	/**
	 * Returns the Persistance Manager
	 *
	 * @return Tx_Extbase_Persistence_Manager An instance of the Persistence Manager
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0
	 */
	public static function getPersistenceManager() {
		t3lib_div::logDeprecatedFunction();
		return self::$persistenceManager;
	}

	/**
	 * Returns the settings of Extbase
	 *
	 * @return array The configuration for the Extbase framework
	 * @deprecated since Extbase 1.3.0; will be removed in Extbase 1.5.0
	 */
	public static function getExtbaseFrameworkConfiguration() {
		t3lib_div::logDeprecatedFunction();
		return self::$configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
	}

}
?>