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

namespace TYPO3\CMS\Core\SystemResource\Publishing;

/**
 * Options for system resource URI generation.
 * These might change, by adding more options,
 * which means the variable names MUST be kept
 * (or properly deprecated) as they are public API.
 * Also, this object MUST be crated using named arguments.
 */
final readonly class UriGenerationOptions
{
    /**
     * Variable names are explicitly public API
     * for named variable access
     */
    public function __construct(
        public ?string $uriPrefix = null,
        public bool $absoluteUri = false,
    ) {}
}
