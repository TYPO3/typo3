<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Nicole Cordes <nicole.cordes@googlemail.com>
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
 * This interface defines the methods provided by TYPO3\CMS\Core\Tests\TestCase::getAccessibleMock.::
 *
 * @author Nicole Cordes <nicole.cordes@googlemail.com>
 */
interface AccessibleObjectInterface {
	/**
	 * Calls the method $method using call_user_func* and returns its return value.
	 *
	 * @param string $methodName name of method to call, must not be empty
	 *
	 * @return mixed the return value from the method $methodName
	 */
	public function _call($methodName);

	/**
	 * Calls the method $method without using call_user_func* and returns its return value.
	 *
	 * @param string $methodName name of method to call, must not be empty
	 * @param mixed &$arg1 first argument given to method $methodName
	 * @param mixed &$arg2 second argument given to method $methodName
	 * @param mixed &$arg3 third argument given to method $methodName
	 * @param mixed &$arg4 fourth argument given to method $methodName
	 * @param mixed &$arg5 fifth argument given to method $methodName
	 * @param mixed &$arg6 sixth argument given to method $methodName
	 * @param mixed &$arg7 seventh argument given to method $methodName
	 * @param mixed &$arg8 eighth argument given to method $methodName
	 * @param mixed &$arg9 ninth argument given to method $methodName
	 *
	 * @return mixed the return value from the method $methodName
	 */
	public function _callRef(
		$methodName, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL, &$arg5= NULL, &$arg6 = NULL, &$arg7 = NULL,
		&$arg8 = NULL, &$arg9 = NULL
	);

	/**
	 * Sets the value of a property.
	 *
	 * @param string $propertyName name of property to set value for, must not be empty
	 * @param mixed $value the new value for the property defined in $propertyName
	 *
	 * @return void
	 */
	public function _set($propertyName, $value);

	/**
	 * Sets the value of a property by reference.
	 *
	 * @param string $propertyName name of property to set value for, must not be empty
	 * @param mixed &$value the new value for the property defined in $propertyName
	 *
	 * @return void
	 */
	public function _setRef($propertyName, &$value);

	/**
	 * Sets the value of a static property.
	 *
	 * @param string $propertyName name of property to set value for, must not be empty
	 * @param mixed $value the new value for the property defined in $propertyName
	 *
	 * @return void
	 */
	public function _setStatic($propertyName, $value);

	/**
	 * Gets the value of the given property.
	 *
	 * @param string $propertyName name of property to return value of, must not be empty
	 *
	 * @return mixed the value of the property $propertyName
	 */
	public function _get($propertyName);

	/**
	 * Gets the value of the given static property.
	 *
	 * @param string $propertyName name of property to return value of, must not be empty
	 *
	 * @return mixed the value of the static property $propertyName
	 */
	public function _getStatic($propertyName);
}
?>