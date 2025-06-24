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
 * Capability to understand the flag within
 * - security.ignoreRootLevelRestriction
 */
final readonly class RootLevelCapability implements SchemaCapabilityInterface
{
    public const TYPE_ONLY_ON_PAGES = 0; // must be on a page (not pid=0)
    public const TYPE_ONLY_ON_ROOTLEVEL = 1; // only allowed on pid=0
    public const TYPE_BOTH = -1; // does not matter

    public function __construct(
        protected int $rootLevelType,
        protected bool $ignoreRootLevelRestriction
    ) {}

    public function getRootLevelType(): int
    {
        return $this->rootLevelType;
    }

    public function shallIgnoreRootLevelRestriction(): bool
    {
        return $this->ignoreRootLevelRestriction;
    }

    public function canExistOnRootLevel(): bool
    {
        return $this->rootLevelType === self::TYPE_BOTH || $this->rootLevelType === self::TYPE_ONLY_ON_ROOTLEVEL;
    }

    public function canExistOnPages(): bool
    {
        return $this->rootLevelType === self::TYPE_BOTH || $this->rootLevelType === self::TYPE_ONLY_ON_PAGES;
    }

    /**
     * Allows non-admin users to access records that on the root-level (page-id 0), thus bypassing this usual restriction.
     */
    public function canAccessRecordsOnRootLevel(): bool
    {
        return !$this->rootLevelType || $this->ignoreRootLevelRestriction;
    }
}
