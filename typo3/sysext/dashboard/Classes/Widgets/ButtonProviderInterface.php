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
 * In case a widget should have a button in the footer of the widget, this button must implement this interface.
 */
interface ButtonProviderInterface
{
    /**
     * This method should return the title that will be shown as the text on the button. As the title will be
     * translated within the template, you can also return a localization string like
     * 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:button'
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Return the link
     *
     * @return string
     */
    public function getLink(): string;

    /**
     * Specify the target of the link like '_blank'
     *
     * @return string
     */
    public function getTarget(): string;
}
