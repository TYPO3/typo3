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

namespace TYPO3\CMS\Core\Utility;

/**
 * Class with helper functions for mathematical calculations
 */
class MathUtility
{
    /**
     * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is FALSE then the $defaultValue is applied.
     *
     * @param int $theInt Input value
     * @param int $min Lower limit
     * @param int $max Higher limit
     * @param int $defaultValue Default value if input is FALSE.
     * @return int The input value forced into the boundaries of $min and $max
     */
    public static function forceIntegerInRange($theInt, $min, $max = 2000000000, $defaultValue = 0)
    {
        // Returns $theInt as an integer in the integerspace from $min to $max
        $theInt = (int)$theInt;
        // If the input value is zero after being converted to integer,
        // defaultValue may set another default value for it.
        if ($defaultValue && !$theInt) {
            $theInt = $defaultValue;
        }
        if ($theInt < $min) {
            $theInt = $min;
        }
        if ($theInt > $max) {
            $theInt = $max;
        }
        return $theInt;
    }

    /**
     * Returns $theInt if it is greater than zero, otherwise returns zero.
     *
     * @param int $theInt Integer string to process
     * @return int
     */
    public static function convertToPositiveInteger($theInt)
    {
        $theInt = (int)$theInt;
        if ($theInt < 0) {
            $theInt = 0;
        }
        return $theInt;
    }

    /**
     * Tests if the input can be interpreted as integer.
     *
     * Note: Integer casting from objects or arrays is considered undefined and thus will return false.
     *
     * @see https://php.net/manual/en/language.types.integer.php#language.types.integer.casting.from-other
     * @param mixed $var Any input variable to test
     * @return bool Returns TRUE if string is an integer
     */
    public static function canBeInterpretedAsInteger($var)
    {
        if ($var === '' || is_object($var) || is_array($var)) {
            return false;
        }
        return (string)(int)$var === (string)$var;
    }

    /**
     * Tests if the input can be interpreted as float.
     *
     * Note: Float casting from objects or arrays is considered undefined and thus will return false.
     *
     * @see http://www.php.net/manual/en/language.types.float.php, section "Formally" for the notation
     * @param mixed $var Any input variable to test
     * @return bool Returns TRUE if string is a float
     */
    public static function canBeInterpretedAsFloat($var)
    {
        $pattern_lnum = '[0-9]+';
        $pattern_dnum = '([0-9]*[\.]' . $pattern_lnum . ')|(' . $pattern_lnum . '[\.][0-9]*)';
        $pattern_exp_dnum = '[+-]?((' . $pattern_lnum . '|' . $pattern_dnum . ')([eE][+-]?' . $pattern_lnum . ')?)';

        if ($var === '' || is_object($var) || is_array($var)) {
            return false;
        }

        $matches = preg_match('/^' . $pattern_exp_dnum . '$/', (string)$var);
        return $matches === 1;
    }

    /**
     * Calculates the input by +,-,*,/,%,^ with priority to + and -
     *
     * @param string $string Input string, eg "123 + 456 / 789 - 4
     * @return int Calculated value. Or error string.
     * @see \TYPO3\CMS\Core\Utility\MathUtility::calculateWithParentheses()
     */
    public static function calculateWithPriorityToAdditionAndSubtraction($string)
    {
        // Removing all whitespace
        $string = preg_replace('/[[:space:]]*/', '', $string);
        // Ensuring an operator for the first entrance
        $string = '+' . $string;
        $qm = '\\*\\/\\+-^%';
        $regex = '([' . $qm . '])([' . $qm . ']?[0-9\\.]*)';
        // Split the expression here:
        $reg = [];
        preg_match_all('/' . $regex . '/', $string, $reg);
        reset($reg[2]);
        $number = 0;
        $Msign = '+';
        $err = '';
        $buffer = (float)current($reg[2]);
        // Advance pointer
        $regSliced = array_slice($reg[2], 1, null, true);
        foreach ($regSliced as $k => $v) {
            $v = (float)$v;
            $sign = $reg[1][$k];
            if ($sign === '+' || $sign === '-') {
                $Msign === '-' ? ($number -= $buffer) : ($number += $buffer);
                $Msign = $sign;
                $buffer = $v;
            } else {
                if ($sign === '/') {
                    if ($v) {
                        $buffer /= $v;
                    } else {
                        $err = 'dividing by zero';
                    }
                }
                if ($sign === '%') {
                    if ($v) {
                        $buffer %= $v;
                    } else {
                        $err = 'dividing by zero';
                    }
                }
                if ($sign === '*') {
                    $buffer *= $v;
                }
                if ($sign === '^') {
                    $buffer = $buffer ** $v;
                }
            }
        }
        $number = $Msign === '-' ? ($number - $buffer) : ($number + $buffer);
        return $err ? 'ERROR: ' . $err : $number;
    }

    /**
     * Calculates the input with parenthesis levels
     *
     * @param string $string Input string, eg "(123 + 456) / 789 - 4
     * @return int Calculated value. Or error string.
     * @see calculateWithPriorityToAdditionAndSubtraction()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::stdWrap()
     */
    public static function calculateWithParentheses($string)
    {
        $securC = 100;
        do {
            $valueLenO = strcspn($string, '(');
            $valueLenC = strcspn($string, ')');
            if ($valueLenC == strlen($string) || $valueLenC < $valueLenO) {
                $value = self::calculateWithPriorityToAdditionAndSubtraction(substr($string, 0, $valueLenC));
                $string = $value . substr($string, $valueLenC + 1);
                return $string;
            }
            $string = substr($string, 0, $valueLenO) . self::calculateWithParentheses(substr($string, $valueLenO + 1));

            // Security:
            $securC--;
            if ($securC <= 0) {
                break;
            }
        } while ($valueLenO < strlen($string));
        return $string;
    }

    /**
     * Checks whether the given number $value is an integer in the range [$minimum;$maximum]
     *
     * @param int $value Integer value to check
     * @param int $minimum Lower boundary of the range
     * @param int $maximum Upper boundary of the range
     * @return bool
     */
    public static function isIntegerInRange($value, $minimum, $maximum)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => $minimum,
                'max_range' => $maximum,
            ],
        ]);
        $isInRange = is_int($value);
        return $isInRange;
    }
}
