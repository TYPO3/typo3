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

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

/**
 * Base interface for a subject that shall be handled during the sudo mode process.
 * A "subject" can be a resource, a route, a database record, anything.
 * Specific implementations of this interface provide the details and behavior.
 */
interface AccessSubjectInterface extends \JsonSerializable
{
    /**
     * Reconstitutes a subject object from its serialized representation.
     */
    public static function fromArray(array $data): static;

    /**
     * Provides a unique string identifier of the subject.
     */
    public function getIdentity(): string;

    /**
     * Provides the actual subject name (e.g. a route, an aspect, a resource, ...)
     */
    public function getSubject(): string;

    /**
     * If given, grants access to same-group sudo mode subjects.
     */
    public function getGroup(): ?string;

    /**
     * Provides a distinct lifetime type, e.g. XS, S, M, L, XL.
     */
    public function getLifetime(): AccessLifetime;
}
