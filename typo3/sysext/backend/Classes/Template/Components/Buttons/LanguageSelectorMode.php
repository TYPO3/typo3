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
 * Defines how the language selector dropdown should behave.
 *
 * @internal
 */
enum LanguageSelectorMode
{
    /**
     * Single language selection using radio buttons.
     * Used in Page Module's "Layout" view where only one language is displayed at a time.
     */
    case SINGLE_SELECT;

    /**
     * Multiple language selection using checkboxes/toggles.
     * Used in Page Module's "Comparison" view and records module where multiple languages can be viewed simultaneously.
     */
    case MULTI_SELECT;
}
