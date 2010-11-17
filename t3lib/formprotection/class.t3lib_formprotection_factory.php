<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Oliver Klee <typo3-coding@oliverklee.de>
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
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class t3lib_formprotection_Factory.
 *
 * This class creates and manages instances of the various form protection
 * classes.
 *
 * This class provides only static methods. It can not be instantiated.
 *
 * Usage for the back-end form protection:
 *
 * <pre>
 * $formProtection = t3lib_formprotection_Factory::get(
 *     't3lib_formProtection_BackEnd'
 * );
 * </pre>
 *
 * Usage for the install tool form protection:
 *
 * <pre>
 * $formProtection = t3lib_formprotection_Factory::get(
 *     'tx_install_formprotection'
 * );
 * $formProtection->injectInstallTool($this);
 * </pre>
 *
 * $Id$
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
final class t3lib_formprotection_Factory {
	/**
	 * created instances of form protections using the type as array key
	 *
	 * @var array<t3lib_formProtectionAbstract>
	 */
	static protected $instances = array();

	/**
	 * Private constructor to prevent instantiation.
	 */
	private function __construct() {}

	/**
	 * Gets a form protection instance for the requested class $className.
	 *
	 * If there already is an existing instance of the requested $className, the
	 * existing instance will be returned.
	 *
	 * @param string $className
	 *        the name of the class for which to return an instance, must be
	 *        "t3lib_formProtection_BackEnd" or "t3lib_formprotection_InstallToolFormProtection"
	 *
	 * @return t3lib_formprotection_Abstract the requested instance
	 */
	static public function get($className) {
		if (!isset(self::$instances[$className])) {
			if (!class_exists($className, TRUE)) {
				throw new InvalidArgumentException(
					'$className must be the name of an existing class, but ' .
						'actually was "' . $className . '".',
					1285352962
				);
			}

			$instance = t3lib_div::makeInstance($className);
			if (!$instance instanceof t3lib_formprotection_Abstract) {
				throw new InvalidArgumentException(
					'$className must be a subclass of ' .
						't3lib_formprotection_Abstract, but actually was "' .
						$className . '".',
					1285353026
				);
			}
			self::$instances[$className] = $instance;
		}
		return self::$instances[$className];
	}

	/**
	 * Sets the instance that will be returned by get() for a specific class
	 * name.
	 *
	 * Note: This function is intended for testing purposes only.
	 *
	 * @param string $className
	 *        the name of the class for which to set an instance, must be
	 *        "t3lib_formProtection_BackEnd" or "t3lib_formprotection_InstallToolFormProtection"
	 * @param t3lib_formprotection_Abstract $instance
	 *        the instance to set
	 *
	 * @return void
	 */
	static public function set($className, t3lib_formprotection_Abstract $instance) {
		self::$instances[$className] = $instance;
	}

	/**
	 * Purges all existing instances.
	 *
	 * This function is particularly useful when cleaning up in unit testing.
	 *
	 * @return void
	 */
	static public function purgeInstances() {
		foreach (self::$instances as $key => $instance) {
			$instance->__destruct();
			unset(self::$instances[$key]);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_factory.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/formprotection/class.t3lib_formprotection_factory.php']);
}
?>