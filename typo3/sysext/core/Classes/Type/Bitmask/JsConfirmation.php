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

namespace TYPO3\CMS\Core\Type\Bitmask;

use TYPO3\CMS\Core\Type\Enumeration;
use TYPO3\CMS\Core\Type\Exception;

/**
 * A class providing constants for bitwise operations on javascript confirmation popups
 */
final class JsConfirmation extends Enumeration
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
    const ALL = 255;

    /**
     * @var int
     */
    const __default = self::ALL;

    /**
     * Bitmask of allowed values beside 255
     *
     * @var int
     */
    protected static $allowedValues = self::TYPE_CHANGE | self::COPY_MOVE_PASTE | self::DELETE | self::FE_EDIT | self::OTHER;

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

    /**
     * Set the Enumeration value to the associated enumeration value by a loose comparison.
     * The value, that is used as the enumeration value, will be of the same type like defined in the enumeration
     *
     * @param mixed $value
     * @throws Exception\InvalidEnumerationValueException
     */
    protected function setValue($value)
    {
        if ($this->isValid($value)) {
            $this->value = $value;
        } else {
            parent::setValue($value);
        }
    }

    /**
     * Check if the value on this enum is a valid value for the enum
     *
     * @param mixed $value
     * @return bool
     */
    protected function isValid($value)
    {
        if ($value < 255) {
            // Check for combined bitmask or bitmask with bits unset from self::ALL
            $unsetValues = (self::ALL ^ $value);
            return ($value & self::$allowedValues) === $value || $unsetValues === ($unsetValues & self::$allowedValues);
        }
        return parent::isValid($value);
    }
}
