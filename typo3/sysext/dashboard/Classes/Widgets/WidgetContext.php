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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Settings\SettingsInterface;

/**
 * Widget context containing all necessary data for widget rendering.
 *
 * This readonly value object encapsulates all the context information needed
 * by widgets during rendering, including:
 * - Widget instance identifier and raw configuration data
 * - Widget configuration and settings
 * - Current HTTP request context
 *
 * The widget context is passed to widgets implementing WidgetRendererInterface
 * and provides a clean interface for accessing widget-specific data and settings.
 */
final readonly class WidgetContext
{
    public function __construct(
        public readonly string $identifier,
        public readonly array $rawData,
        public readonly WidgetConfigurationInterface $configuration,
        public readonly SettingsInterface $settings,
        public readonly ServerRequestInterface $request,
    ) {}
}
