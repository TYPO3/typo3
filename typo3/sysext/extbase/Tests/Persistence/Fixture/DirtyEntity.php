<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Persistence\Fixture;

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
 * @subpackage Persistence
 * @version $Id: DirtyEntity.php 2047 2009-03-24 23:53:16Z robert $
 */

/**
 * A model fixture used for testing the persistence manager
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id: DirtyEntity.php 2047 2009-03-24 23:53:16Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @entity
 */
class DirtyEntity implements \F3\FLOW3\AOP\ProxyInterface {

	/**
	 * Just a normal string
	 *
	 * @var string
	 */
	public $someString;

	/**
	 * @var integer
	 */
	public $someInteger;

	/**
	 * Returns the name of the class this proxy extends.
	 *
	 * @return string Name of the target class
	 */
	public function FLOW3_AOP_Proxy_getProxyTargetClassName() {
		return 'F3\FLOW3\Tests\Persistence\Fixture\DirtyEntity';
	}

	/**
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface: The join point
	 * @return mixed Result of the target (ie. original) method
	 */
	public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {

	}

	/**
	 * Returns the value of an arbitrary property.
	 * The method does not have to check if the property exists.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed Value of the property
	 */
	public function FLOW3_AOP_Proxy_getProperty($propertyName) {
		return $this->$propertyName;
	}

	/**
	 * Sets the value of an arbitrary property.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $propertyValue Value to set
	 * @return void
	 */
	public function FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue) {

	}

	/**
	 * Returns TRUE as this is a DirtyEntity
	 *
	 * @return boolean
	 */
	public function FLOW3_Persistence_isDirty() {
		return TRUE;
	}

	/**
	 * Dummy method for mock creation
	 * @return void
	 */
	public function FLOW3_Persistence_memorizeCleanState() {}
}
?>