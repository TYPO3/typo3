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

namespace TYPO3\CMS\Extbase\Persistence;

/**
 * An interface how to monitor changes on an object and its properties. All domain objects which should be persisted need to implement the below interface.
 *
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 */
interface ObjectMonitoringInterface
{
    /**
     * Register an object's clean state, e.g. after it has been reconstituted
     * from the database
     *
     * @param non-empty-string|null $propertyName
     */
    public function _memorizeCleanState(string|null $propertyName = null): void;

    /**
     * Returns TRUE if the properties were modified after reconstitution
     *
     * @param non-empty-string|null $propertyName
     */
    public function _isDirty(string|null $propertyName = null): bool;
}
