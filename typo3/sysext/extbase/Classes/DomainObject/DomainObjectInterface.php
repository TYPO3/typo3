<?php
namespace TYPO3\CMS\Extbase\DomainObject;

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
 * A Domain Object Interface. All domain objects which should be persisted need to implement the below interface.
 * Usually you will need to subclass \TYPO3\CMS\Extbase\DomainObject\AbstractEntity and \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 * instead.
 *
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 */
interface DomainObjectInterface
{
    /**
     * Getter for uid.
     *
     * @return int The uid or NULL if none set yet.
     */
    public function getUid();

    /**
     * Setter for the pid.
     *
     * @param int $pid
     */
    public function setPid($pid);

    /**
     * Getter for the pid.
     *
     * @return int The pid or NULL if none set yet.
     */
    public function getPid();

    /**
     * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
     *
     * @return bool
     */
    public function _isNew();

    /**
     * Reconstitutes a property. Only for internal use.
     *
     * @param string $propertyName
     * @param string $value
     */
    public function _setProperty($propertyName, $value);

    /**
     * Returns the property value of the given property name. Only for internal use.
     *
     * @param string $propertyName
     * @return mixed The propertyValue
     */
    public function _getProperty($propertyName);

    /**
     * Returns a hash map of property names and property values
     *
     * @return array The properties
     */
    public function _getProperties();

    /**
     * Returns the clean value of the given property. The returned value will be NULL if the clean state was not memorized before, or
     * if the clean value is NULL.
     *
     * @param string $propertyName The name of the property to be memorized.
     * @return mixed The clean property value or NULL
     */
    public function _getCleanProperty($propertyName);
}
