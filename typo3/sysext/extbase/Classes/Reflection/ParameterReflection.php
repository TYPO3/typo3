<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: ParameterReflection.php 1811 2009-01-28 12:04:49Z robert $
 */

/**
 * Extended version of the ReflectionParameter
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: ParameterReflection.php 1811 2009-01-28 12:04:49Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_ExtBase_Reflection_ParameterReflection extends ReflectionParameter {

	/**
	 * The constructor, initializes the reflection parameter
	 *
	 * @param  string $functionName: Name of the function
	 * @param  string $propertyName: Name of the property to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($function, $parameterName) {
		parent::__construct($function, $parameterName);
	}

	/**
	 * Returns the declaring class
	 *
	 * @return Tx_ExtBase_Reflection_ClassReflection The declaring class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClass() {
		return new Tx_ExtBase_Reflection_ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Returns the parameter class
	 *
	 * @return Tx_ExtBase_Reflection_ClassReflection The parameter class
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getClass() {
		try {
			$class = parent::getClass();
		} catch (Exception $e) {
			return NULL;
		}

		return is_object($class) ? new Tx_ExtBase_Reflection_ClassReflection($class->getName()) : NULL;
	}

}

?>