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

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * Widget rendering result containing content and metadata.
 *
 * This readonly value object represents the result of widget rendering,
 * containing the rendered HTML content and additional metadata about
 * the widget's capabilities and state.
 *
 * The result includes:
 * - HTML content to be displayed in the dashboard
 * - Optional custom label overriding the widget's default title
 * - Refreshable flag indicating whether the widget supports refresh operations
 */
final readonly class WidgetResult
{
    public function __construct(
        public string $content,
        public ?string $label = null,
        public bool $refreshable = false,
    ) {}
}
