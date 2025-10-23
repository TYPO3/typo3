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

namespace TYPO3\CMS\Backend\Template\Components\Buttons;

/**
 * Interface for buttons that define their own fixed position and group.
 *
 * Buttons implementing this interface will automatically override the position
 * and group parameters passed to ButtonBar::addButton(), ensuring they always
 * appear in their designated location regardless of what the developer specifies.
 *
 * This is useful for buttons that should always appear in a consistent location
 * across the backend, such as the ShortcutButton which always appears in the
 * top right corner.
 *
 * Example implementation:
 *
 * ```
 * class MyButton implements ButtonInterface, PositionInterface
 * {
 *     public function getPosition(): string
 *     {
 *         return ButtonBar::BUTTON_POSITION_RIGHT;
 *     }
 *
 *     public function getGroup(): int
 *     {
 *         return 90;
 *     }
 * }
 * ```
 *
 * Usage:
 *
 * ```
 * $button = $buttonBar->makeMyButton();
 * // Position and group are ignored - button defines its own
 * $buttonBar->addButton($button);
 * ```
 */
interface PositionInterface
{
    /**
     * Returns the position where this button should be rendered.
     *
     * @return string Either ButtonBar::BUTTON_POSITION_LEFT or ButtonBar::BUTTON_POSITION_RIGHT
     */
    public function getPosition(): string;

    /**
     * Returns the group number for this button.
     *
     * Groups determine the visual grouping and order of buttons within a position.
     * Lower numbers appear first.
     *
     * @return int The group number (e.g., 1, 10, 90, 91)
     */
    public function getGroup(): int;
}
