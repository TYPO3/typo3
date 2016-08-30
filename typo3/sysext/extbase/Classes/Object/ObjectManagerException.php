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
 * Implementation of the default Extbase Object Manager
 */
class ObjectManagerException implements \TYPO3\CMS\Extbase\Object\ObjectManagerInterfaceException
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container
     */
    protected $objectContainer;

    /**
     * Constructs a new Object Manager
     */
    public function __construct()
    {
        $this->objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
    }

    /**
     * Serialization (sleep) helper.
     *
     * Removes properties of this object from serialization.
     * This action is necessary, since there might be closures used
     * in the accordant content objects (e.g. in FLUIDTEMPLATE) which
     * cannot be serialized. It's fine to reset $this->contentObjects
     * since elements will be recreated and are just a local cache,
     * but not required for runtime logic and behaviour.
     *
     * @see http://forge.typo3.org/issues/36820
     * @return array Names of the properties to be serialized
     */
    public function __sleep()
    {
        // Use get_objects_vars() instead of
        // a much more expensive Reflection:
        $properties = get_object_vars($this);
        unset($properties['objectContainer']);
        return array_keys($properties);
    }

    /**
     * Unserialization (wakeup) helper.
     *
     * Initializes the properties again that have been removed by
     * a call to the __sleep() method on serialization before.
     *
     * @see http://forge.typo3.org/issues/36820
     * @return void
     */
    public function __wakeup()
    {
        $this->__construct();
    }

    /**
     * Returns TRUE if an object with the given name is registered
     *
     * @param string $objectName Name of the object
     * @return bool TRUE if the object has been registered, otherwise FALSE
     */
    public function isRegistered($objectName)
    {
        return class_exists($objectName, true);
    }

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
    public function get($objectName)
    {
        $arguments = func_get_args();
        array_shift($arguments);
        return $this->objectContainer->getInstance($objectName, $arguments);
    }

    /**
     * Creates a fresh instance of the object specified by $objectName.
     *
     * This factory method can only create objects of the scope prototype.
     * Singleton objects must be either injected by some type of Dependency Injection or
     * if that is not possible, be retrieved by the get() method of the
     * Object Manager
     *
     * @param string $objectName The name of the object to create
     * @throws Exception\WrongScopeException
     * @return object The new object instance
     * @api
     */
    public function create($objectName)
    {
        $arguments = func_get_args();
        array_shift($arguments);
        if ($objectName === 'DateTime') {
            array_unshift($arguments, $objectName);
            $instance = call_user_func_array([\TYPO3\CMS\Core\Utility\GeneralUtility::class, 'makeInstance'], $arguments);
        } else {
            $instance = $this->objectContainer->getInstance($objectName, $arguments);
        }
        if ($instance instanceof \TYPO3\CMS\Core\SingletonInterface) {
            throw new \TYPO3\CMS\Extbase\Object\Exception\WrongScopeException('Object "' . $objectName . '" is of not of scope prototype, but only prototype is supported by create()', 1265203124);
        }
        return $instance;
    }

    /**
     * Create an instance of $className without calling its constructor
     *
     * @param string $className
     * @return object
     * @api
     */
    public function getEmptyObject($className)
    {
        return $this->objectContainer->getEmptyObject($className);
    }
}
