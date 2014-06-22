<?php
namespace TYPO3\CMS\Extbase\Object;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Interface for the TYPO3 Object Manager
 */
interface ObjectManagerInterface extends \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Returns TRUE if an object with the given name is registered
	 *
	 * @param string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 */
	public function isRegistered($objectName);

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @api
	 */
	public function get($objectName);

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This factory method can only create objects of the scope prototype.
	 * Singleton objects must be either injected by some type of Dependency Injection or
	 * if that is not possible, be retrieved by the get() method of the
	 * Object Manager
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @deprecated since 6.1
	 */
	public function create($objectName);

	/**
	 * Create an instance of $className without calling its constructor
	 *
	 * @param string $className
	 * @return object
	 * @api
	 */
	public function getEmptyObject($className);

	/**
	 * Returns the scope of the specified object.
	 *
	 * @param string $objectName The object name
	 * @return integer One of the Container::SCOPE_ constants
	 */
	public function getScope($objectName);
}
