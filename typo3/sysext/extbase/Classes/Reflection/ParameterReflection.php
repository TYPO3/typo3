<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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
 * Extended version of the ReflectionParameter
 *
 * @package Extbase
 * @subpackage Reflection
 * @version $Id: ParameterReflection.php 1052 2009-08-05 21:51:32Z sebastian $
 */
class Tx_Extbase_Reflection_ParameterReflection extends ReflectionParameter {

	/**
	 * The constructor, initializes the reflection parameter
	 *
	 * @param  string $functionName: Name of the function
	 * @param  string $propertyName: Name of the property to reflect
	 * @return void
	 */
	public function __construct($function, $parameterName) {
		parent::__construct($function, $parameterName);
	}

	/**
	 * Returns the declaring class
	 *
	 * @return Tx_Extbase_Reflection_ClassReflection The declaring class
	 */
	public function getDeclaringClass() {
		return new Tx_Extbase_Reflection_ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Returns the parameter class
	 *
	 * @return Tx_Extbase_Reflection_ClassReflection The parameter class
	 */
	public function getClass() {
		try {
			$class = parent::getClass();
		} catch (Exception $e) {
			return NULL;
		}

		return is_object($class) ? new Tx_Extbase_Reflection_ClassReflection($class->getName()) : NULL;
	}

}

?>