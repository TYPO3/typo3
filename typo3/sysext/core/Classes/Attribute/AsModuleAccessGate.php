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
 * Service tag to autoconfigure module access gates.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsModuleAccessGate
{
    public const string TAG_NAME = 'backend.module_access_gate';

    /**
     * @param non-empty-string $identifier
     * @param list<non-empty-string> $before
     * @param list<non-empty-string> $after
     */
    public function __construct(
        public string $identifier,
        public array $before = [],
        public array $after = [],
    ) {}
}
