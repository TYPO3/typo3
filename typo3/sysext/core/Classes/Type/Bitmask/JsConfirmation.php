<?php
namespace TYPO3\CMS\Core\Type\Bitmask;

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

/**
 * A class providing constants for bitwise operations on javascript confirmation popups
 */
class JsConfirmation extends \TYPO3\CMS\Core\Type\Enumeration
{
    /**
     * @var int
     */
    const TYPE_CHANGE = 0b00000001;

    /**
     * @var int
     */
    const COPY_MOVE_PASTE = 0b00000010;

    /**
     * @var int
     */
    const DELETE = 0b00000100;

    /**
     * @var int
     */
    const FE_EDIT = 0b00001000;

    /**
     * @var int
     */
    const OTHER = 0b10000000;

    /**
     * @var int
     */
    const __default = 255;

    /**
     * Returns TRUE if a given value matches the internal value
     *
     * @param JsConfirmation $value Value to check
     * @return bool
     */
    public function matches(JsConfirmation $value)
    {
        $value = (int)(string)$value;
        $thisValue = (int)(string)$this;

        return ($value & $thisValue) == $thisValue;
    }
}
