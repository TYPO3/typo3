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

namespace TYPO3\CMS\Core\Cache\Flush;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Describes on whose behalf caches are flushed: the workspace the changes
 * were made in, and whether flushing the "pages" group or all caches is
 * permitted. The default context is unrestricted and meant for code running
 * without a backend user, such as scheduler tasks or custom commands.
 */
final readonly class CacheFlushingContext
{
    public function __construct(
        public int $workspaceId = 0,
        public bool $canFlushPagesGroup = true,
        public bool $canFlushAllCaches = true,
    ) {}

    /**
     * Admins may flush the "pages" group and all caches, unless User TSconfig
     * "options.clearCache.all = 0" restricts the latter. Other users need
     * "options.clearCache.pages = 1" or "options.clearCache.all = 1".
     */
    public static function fromBackendUser(BackendUserAuthentication $backendUser): self
    {
        $clearCacheOptions = $backendUser->getTSConfig()['options.']['clearCache.'] ?? [];
        return new self(
            workspaceId: (int)$backendUser->workspace,
            canFlushPagesGroup: $backendUser->isAdmin() || (bool)($clearCacheOptions['pages'] ?? false),
            canFlushAllCaches: (bool)($clearCacheOptions['all'] ?? false)
                || ($backendUser->isAdmin() && (bool)($clearCacheOptions['all'] ?? true)),
        );
    }
}
