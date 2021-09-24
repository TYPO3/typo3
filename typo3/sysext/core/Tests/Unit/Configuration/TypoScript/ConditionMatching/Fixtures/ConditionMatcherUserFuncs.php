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

namespace {
    function user_testFunctionWithNoArgument(): bool
    {
        return count(func_get_args()) === 0;
    }

    function user_testFunctionWithSingleArgument(): bool
    {
        return func_num_args() === 1;
    }

    function user_testFunctionWithThreeArguments(): bool
    {
        return func_num_args() === 3;
    }

    function user_testFunctionWithThreeArgumentsSpaces(...$arguments): bool
    {
        $result = true;
        foreach ($arguments as $argument) {
            $result &= (trim($argument) == $argument);
        }
        return $result;
    }

    function user_testFunctionWithSpaces($value): bool
    {
        return $value === ' 3, 4, 5, 6 ';
    }

    function user_testFunction(): bool
    {
        return true;
    }

    function user_testFunctionFalse(): bool
    {
        return false;
    }

    function user_testFunctionWithQuoteMissing($value): bool
    {
        return $value === 'value "';
    }

    function user_testQuotes($value): bool
    {
        return $value === '1 " 2';
    }

    class ConditionMatcherUserFunctions
    {
        /**
         * @param mixed $value
         * @return bool
         */
        public static function isTrue($value): bool
        {
            return (bool)$value;
        }
    }
}
