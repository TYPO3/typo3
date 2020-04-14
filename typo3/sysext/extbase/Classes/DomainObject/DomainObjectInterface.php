<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\DomainObject;

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
    public function getUid(): ?int;

    /**
     * Setter for the pid.
     *
     * @param int $pid
     */
    public function setPid(int $pid);

    /**
     * Getter for the pid.
     *
     * @return int The pid or NULL if none set yet.
     */
    public function getPid(): ?int;

    /**
     * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
     *
     * @return bool
     * @internal
     */
    public function _isNew(): bool;

    /**
     * Reconstitutes a property. Only for internal use.
     *
     * @param string $propertyName
     * @param mixed $value
     * @internal
     */
    public function _setProperty(string $propertyName, $value);

    /**
     * Returns the property value of the given property name. Only for internal use.
     *
     * @param string $propertyName
     * @return mixed The propertyValue
     * @internal
     */
    public function _getProperty(string $propertyName);

    /**
     * Returns a hash map of property names and property values
     *
     * @return array The properties
     * @internal
     */
    public function _getProperties(): array;

    /**
     * Returns the clean value of the given property. The returned value will be NULL if the clean state was not memorized before, or
     * if the clean value is NULL.
     *
     * @param string $propertyName The name of the property to be memorized.
     * @return mixed The clean property value or NULL
     * @internal
     */
    public function _getCleanProperty(string $propertyName);
}
