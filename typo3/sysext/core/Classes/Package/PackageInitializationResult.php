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

namespace TYPO3\CMS\Core\Package;

/**
 * Dto for package initialization result data. Used by listeners to the PackageInitializationEvent
 */
final readonly class PackageInitializationResult
{
    public function __construct(
        private string $identifier,
        private mixed $result
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }
}
