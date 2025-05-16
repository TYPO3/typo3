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
 * A Domain Object Interface. All domain objects which should be persisted need to implement this interface.
 *
 * Usually you will need to subclass `AbstractEntity` or `AbstractValueObject` instead of implementing this interface
 * directly, though.
 *
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 */
interface DomainObjectInterface
{
    public function getUid(): ?int;

    public function setPid(int $pid): void;

    public function getPid(): ?int;

    /**
     * Returns TRUE if the object is new (the uid was not set, yet).
     *
     * @internal
     */
    public function _isNew(): bool;

    /**
     * @return array<non-empty-string, mixed>
     *
     * @internal
     */
    public function _getProperties(): array;
}
