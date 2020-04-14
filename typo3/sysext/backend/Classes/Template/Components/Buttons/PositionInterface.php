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
interface PositionInterface
{
    /**
     * Gets the button position.
     *
     * @return string
     */
    public function getPosition();

    /**
     * Gets the button group.
     *
     * @return int
     */
    public function getGroup();
}
