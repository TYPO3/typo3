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

namespace TYPO3\CMS\Core\Security\PermissionSet;

/**
 * Provides workspace and user-specific configuration during data processing operations.
 *
 * Contains context information needed for permission evaluation and data manipulation,
 * primarily used by DataHandler and related components. Includes the current workspace
 * ID for versioning support and user-specific configuration (TSconfig, preferences)
 * that may affect permission checks and data handling behavior.
 *
 * @internal
 */
final class ProcessingContext
{
    /**
     * @param int $workspaceId The workspace ID for the current operation (mutable for DataHandler compatibility)
     * @param array<string, mixed> $userTsConfig User TSconfig configuration array
     * @param array<string, mixed> $userPreferences User preferences array
     */
    public function __construct(
        // @todo still required writable due to `DataHandler`
        public int $workspaceId = 0,
        public readonly array $userTsConfig = [],
        public readonly array $userPreferences = [],
    ) {}
}
