<?php
namespace TYPO3\CMS\Extbase\Object;

/*
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
interface ObjectManagerInterfaceException extends \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Returns TRUE if an object with the given name is registered
     *
     * @param string $objectName Name of the object
     * @return bool TRUE if the object has been registered, otherwise FALSE
     */
    public function isRegistered($objectName);

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * Important:
     *
     * If possible, instances of Prototype objects should always be created with the
     * Object Manager's create() method and Singleton objects should rather be
     * injected by some type of Dependency Injection.
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
     * @throws \TYPO3\CMS\Extbase\Object\Exception\WrongScopeException if the created object is not of scope prototype
     * @api
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
}
