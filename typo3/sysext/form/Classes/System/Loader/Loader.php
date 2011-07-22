<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
require_once(t3lib_extMgm::extPath('form') . 'Classes/Exception/class.tx_form_exception_loader.php');

/**
 * Static methods for loading classes
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_loader {

	/**
	 * Loads a file from a classname
	 * Underscores are converted to slashes for directories
	 *
	 * @param $class Classname
	 * @return $className
	 */
	public static function loadClass($class, $directory) {
		$class = strtolower((string) $class);
		$directory = (string) $directory;

		if(empty($class)) {
			$directoryForClassName = trim(strtolower($directory), '/');
		} else {
			$directoryForClassName = strtolower($directory);
		}
		$className = 'tx_form_' . str_replace('/', '_', $directoryForClassName) . $class;

		if (class_exists($className, FALSE)) {
			return $className;
		}

		$file = 'Classes/' . $directory . ucfirst($class) . '.php';
		include_once(t3lib_extMgm::extPath('form') . $file);

		if (!class_exists($className, FALSE)) {
			throw new tx_form_exception_loader ('File "' . $file . '" not found. Class "' . $className . '" does not exist.');
		}

		return $className;
	}
}
?>