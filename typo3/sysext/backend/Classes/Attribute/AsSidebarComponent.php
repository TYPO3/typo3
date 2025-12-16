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

namespace TYPO3\CMS\Backend\Attribute;

/**
 * Service tag to autoconfigure backend sidebar components.
 *
 * @internal
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsSidebarComponent
{
    public const TAG_NAME = 'backend.sidebar.component';

    /**
     * @param non-empty-string $identifier Unique identifier for the sidebar component
     * @param list<non-empty-string> $before List of component identifiers, which should appear before
     * @param list<non-empty-string> $after List of component identifiers, which should appear after
     */
    public function __construct(
        public string $identifier,
        public array $before = [],
        public array $after = [],
    ) {}
}
