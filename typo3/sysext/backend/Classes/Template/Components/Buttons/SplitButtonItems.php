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
 * SplitButtonItems
 *
 * Type-safe container for split button items.
 *
 * A split button consists of one primary action button and an array of
 * option buttons shown in a dropdown menu. This DTO ensures type safety
 * and prevents the use of magic array keys.
 *
 * @internal This is a concrete implementation and not part of the TYPO3 Public API.
 */
final readonly class SplitButtonItems
{
    /**
     * @param AbstractButton $primary The primary action button
     * @param AbstractButton[] $options Array of option buttons for the dropdown
     */
    public function __construct(
        public AbstractButton $primary,
        public array $options,
    ) {}

    /**
     * Checks if the split button has a valid configuration.
     *
     * @return bool True if primary action exists and at least one option is available
     */
    public function isValid(): bool
    {
        return $this->primary->isValid() && count($this->options) > 0;
    }
}
