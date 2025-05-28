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
 * Defines API of configuration for a widget.
 * This is separated by concrete implementation of a widget (WidgetInterface).
 * The configuration is used to generate UX, and other stuff.
 */
interface WidgetConfigurationInterface
{
    /**
     * Returns the unique identifier of a widget
     */
    public function getIdentifier(): string;

    /**
     * Returns the service name providing the widget implementation
     */
    public function getServiceName(): string;

    /**
     * Returns array of group names associated to this widget
     */
    public function getGroupNames(): array;

    /**
     * Returns the title of a widget, this is used for the widget selector
     */
    public function getTitle(): string;

    /**
     * Returns the description of a widget, this is used for the widget selector
     */
    public function getDescription(): string;

    /**
     * Returns the icon identifier of a widget, this is used for the widget selector
     */
    public function getIconIdentifier(): string;

    /**
     * Returns the height of a widget (small, medium, large)
     */
    public function getHeight(): string;

    /**
     * Returns the width of a widget (small, medium, large)
     */
    public function getWidth(): string;
}
