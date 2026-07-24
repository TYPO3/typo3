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

namespace TYPO3\CMS\Core\Attribute;

/**
 * Service tag to autoconfigure meta tag managers for the MetaTagManagerRegistry.
 *
 * Managers are ordered by their "before" and "after" constraints, referencing
 * the identifiers of other managers. A manager without constraints is ordered
 * before the "generic" manager shipped with TYPO3 Core, which handles every
 * property and therefore always comes last.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsMetaTagManager
{
    public const TAG_NAME = 'metatag.manager';

    /**
     * @param string[] $before
     * @param string[] $after
     */
    public function __construct(
        public string $identifier,
        public array $before = [],
        public array $after = [],
    ) {}
}
