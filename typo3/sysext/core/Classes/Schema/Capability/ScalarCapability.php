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

namespace TYPO3\CMS\Core\Schema\Capability;

/**
 * Primitive capability that just contains a fixed value.
 * Examples:
 * - default_sortby
 * - versioningWS
 * - adminOnly
 * - readOnly
 * - hideAtCopy
 * - hideTable
 * - prependAtCopy
 */
final readonly class ScalarCapability implements SchemaCapabilityInterface
{
    public function __construct(
        protected bool|string|int|array|float|null $value = null
    ) {}
    public function getValue(): bool|string|int|array|float|null
    {
        return $this->value;
    }
}
