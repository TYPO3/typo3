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
 *
 * @template T
 */
interface ObjectManagerInterface extends \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Returns TRUE if an object with the given name is registered
     *
     * @param string $objectName Name of the object
     * @return bool TRUE if the object has been registered, otherwise FALSE
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function isRegistered($objectName);

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * @param string|class-string<T> $objectName The name of the object to return an instance of
     * @param array ...$constructorArguments
     * @return object&T The object instance
     */
    public function get($objectName, ...$constructorArguments);

    /**
     * Create an instance of $className without calling its constructor
     *
     * @param string|class-string<T> $className
     * @return object&T
     */
    public function getEmptyObject($className);

    /**
     * Returns the scope of the specified object.
     *
     * @param string $objectName The object name
     * @return int One of the Container::SCOPE_ constants
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getScope($objectName);
}
