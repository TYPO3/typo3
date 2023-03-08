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

use TYPO3\CMS\Extbase\Persistence\ObjectMonitoringInterface;

/**
 * A Domain Object Interface. All domain objects which should be persisted need to implement the below interface.
 * Usually you will need to subclass \TYPO3\CMS\Extbase\DomainObject\AbstractEntity and \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 * instead.
 *
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
interface DomainObjectInterface extends ObjectMonitoringInterface
{
    public function getUid(): ?int;

    public function setPid(int $pid);

    public function getPid(): ?int;

    /**
     * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
     */
    public function _isNew(): bool;

    /**
     * @param non-empty-string $propertyName
     */
    public function _hasProperty(string $propertyName): bool;

    /**
     * @param non-empty-string $propertyName
     */
    public function _setProperty(string $propertyName, mixed $value);

    /**
     * @param non-empty-string $propertyName
     */
    public function _getProperty(string $propertyName): mixed;

    /**
     * @return array<non-empty-string, mixed>
     */
    public function _getProperties(): array;

    /**
     * Returns the clean value of the given property. The returned value will be NULL if the clean state was not memorized before, or
     * if the clean value is NULL.
     *
     * @param non-empty-string $propertyName
     * @return mixed The clean property value or NULL
     */
    public function _getCleanProperty(string $propertyName): mixed;
}
