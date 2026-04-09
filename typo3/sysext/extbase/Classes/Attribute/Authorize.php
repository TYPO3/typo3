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

namespace TYPO3\CMS\Extbase\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
readonly class Authorize
{
    public function __construct(
        public string|array|null $callback = null,
        public bool $requireLogin = false,
        public array $requireGroups = [],
    ) {
        if ($callback === null && !$requireLogin && $requireGroups === []) {
            throw new \InvalidArgumentException(
                'Authorize attribute requires at least one of: callback, requireLogin, or requireGroups',
                1761287265
            );
        }
    }
}
