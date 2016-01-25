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

namespace {
    function user_testFunctionWithNoArgument()
    {
        return count(func_get_args()) === 0;
    }

    function user_testFunctionWithSingleArgument()
    {
        return count(func_get_args()) === 1;
    }

    function user_testFunctionWithThreeArguments()
    {
        return count(func_get_args()) === 3;
    }

    function user_testFunctionWithThreeArgumentsSpaces()
    {
        $result = true;
        foreach (func_get_args() as $argument) {
            $result &= (trim($argument) == $argument);
        }
        return $result;
    }

    function user_testFunctionWithSpaces($value)
    {
        return $value === ' 3, 4, 5, 6 ';
    }

    function user_testFunction()
    {
        return true;
    }

    function user_testFunctionFalse()
    {
        return false;
    }

    function user_testFunctionWithQuoteMissing($value)
    {
        return $value === 'value "';
    }

    function user_testQuotes($value)
    {
        return $value === '1 " 2';
    }

    class ConditionMatcherUserFunctions
    {
        /**
         * @param mixed $value
         * @return bool
         */
        public static function isTrue($value)
        {
            return (bool)$value;
        }
    }
}
