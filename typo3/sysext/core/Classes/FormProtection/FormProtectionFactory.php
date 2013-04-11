<?php
namespace TYPO3\CMS\Core\FormProtection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Oliver Klee <typo3-coding@oliverklee.de>
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
 * This class creates and manages instances of the various form protection
 * classes.
 *
 * This class provides only static methods. It can not be instantiated.
 *
 * Usage for the back-end form protection:
 *
 * <pre>
 * $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
 * </pre>
 *
 * Usage for the install tool form protection:
 *
 * <pre>
 * $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
 * $formProtection->injectInstallTool($this);
 * </pre>
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Ernesto Baschny <ernst@cron-it.de>
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class FormProtectionFactory {

	/**
	 * created instances of form protections using the type as array key
	 *
	 * @var array<AbstracFormtProtection>
	 */
	static protected $instances = array();

	/**
	 * Private constructor to prevent instantiation.
	 */
	private function __construct() {

	}

	/**
	 * Gets a form protection instance for the requested class $className.
	 *
	 * If there already is an existing instance of the requested $className, the
	 * existing instance will be returned. If no $className is provided, the factory
	 * detects the scope and returns the appropriate form protection object.
	 *
	 * @param string $className
	 * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection the requested instance
	 */
	static public function get($className = NULL) {
		if ($className === NULL) {
			$className = self::getClassNameByState();
		}
		if (!isset(self::$instances[$className])) {
			self::createAndStoreInstance($className);
		}
		return self::$instances[$className];
	}

	/**
	 * Returns the class name depending on TYPO3_MODE and
	 * active backend session.
	 *
	 * @return string
	 */
	static protected function getClassNameByState() {
		switch (TRUE) {
		case self::isInstallToolSession():
			$className = 'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection';
			break;
		case self::isBackendSession():
			$className = 'TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection';
			break;
		case self::isFrontendSession():

		default:
			$className = 'TYPO3\\CMS\\Core\\FormProtection\\DisabledFormProtection';
		}
		return $className;
	}

	/**
	 * Check if we are in the install tool
	 *
	 * @return boolean
	 */
	static protected function isInstallToolSession() {
		return defined(TYPO3_enterInstallScript) && TYPO3_enterInstallScript;
	}

	/**
	 * Checks if a user is logged in and the session is active.
	 *
	 * @return boolean
	 */
	static protected function isBackendSession() {
		return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof \TYPO3\CMS\Core\Authentication\BackendUserAuthentication && isset($GLOBALS['BE_USER']->user['uid']) && !(TYPO3_MODE === 'FE');
	}

	/**
	 * Checks if a frontend user is logged in and the session is active.
	 *
	 * @return boolean
	 */
	static protected function isFrontendSession() {
		return is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user instanceof \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication && isset($GLOBALS['TSFE']->fe_user->user['uid']) && TYPO3_MODE === 'FE';
	}

	/**
	 * Creates an instance for the requested class $className
	 * and stores it internally.
	 *
	 * @param string $className
	 * @throws \InvalidArgumentException
	 */
	static protected function createAndStoreInstance($className) {
		if (!class_exists($className, TRUE)) {
			throw new \InvalidArgumentException('$className must be the name of an existing class, but ' . 'actually was "' . $className . '".', 1285352962);
		}
		$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
		if (!$instance instanceof \TYPO3\CMS\Core\FormProtection\AbstractFormProtection) {
			throw new \InvalidArgumentException('$className must be a subclass of ' . 'TYPO3\\CMS\\Core\\FormProtection\\AbstractFormProtection, but actually was "' . $className . '".', 1285353026);
		}
		self::$instances[$className] = $instance;
	}

	/**
	 * Sets the instance that will be returned by get() for a specific class
	 * name.
	 *
	 * Note: This function is intended for testing purposes only.
	 *
	 * @access private
	 * @param string $className
	 * @param \TYPO3\CMS\Core\FormProtection\AbstractFormProtection $instance
	 * @return void
	 */
	static public function set($className, \TYPO3\CMS\Core\FormProtection\AbstractFormProtection $instance) {
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


?>