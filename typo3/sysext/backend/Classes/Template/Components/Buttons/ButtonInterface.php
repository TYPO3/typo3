<?php

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
 * Interface for buttons
 */
interface ButtonInterface extends \Stringable
{
    /**
     * Validates all set parameters of a button.
     *
     * @return bool
     */
    public function isValid();

    /**
     * Returns the fully qualified class name of the button as a string
     *
     * @return string
     */
    public function getType();

    /**
     * Renders the markup for the button
     *
     * @return string
     */
    public function render();
}
