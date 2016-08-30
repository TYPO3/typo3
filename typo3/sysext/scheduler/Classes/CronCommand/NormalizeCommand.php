<?php
namespace TYPO3\CMS\Scheduler\CronCommand;

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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Validate and normalize a cron command.
 *
 * Special fields like three letter weekdays, ranges and steps are substituted
 * to a comma separated list of integers. Example:
 * '2-4 10-40/10 * mar * fri'  will be normalized to '2,4 10,20,30,40 * * 3 1,2'
 */
class NormalizeCommand
{
    /**
     * Main API method: Get the cron command and normalize it.
     *
     * If no exception is thrown, the resulting cron command is validated
     * and consists of five whitespace separated fields, which are either
     * the letter '*' or a sorted, unique comma separated list of integers.
     *
     * @api
     * @throws \InvalidArgumentException cron command is invalid or out of bounds
     * @param string $cronCommand The cron command to normalize
     * @return string Normalized cron command
     */
    public static function normalize($cronCommand)
    {
        $cronCommand = trim($cronCommand);
        $cronCommand = self::convertKeywordsToCronCommand($cronCommand);
        $cronCommand = self::normalizeFields($cronCommand);
        return $cronCommand;
    }

    /**
     * Accept special cron command keywords and convert to standard cron syntax.
     * Allowed keywords: @yearly, @annually, @monthly, @weekly, @daily, @midnight, @hourly
     *
     * @param string $cronCommand Cron command
     * @return string Normalized cron command if keyword was found, else unchanged cron command
     */
    protected static function convertKeywordsToCronCommand($cronCommand)
    {
        switch ($cronCommand) {
            case '@yearly':
            case '@annually':
                $cronCommand = '0 0 1 1 *';
                break;
            case '@monthly':
                $cronCommand = '0 0 1 * *';
                break;
            case '@weekly':
                $cronCommand = '0 0 * * 0';
                break;
            case '@daily':

            case '@midnight':
                $cronCommand = '0 0 * * *';
                break;
            case '@hourly':
                $cronCommand = '0 * * * *';
                break;
        }
        return $cronCommand;
    }

    /**
     * Normalize cron command field to list of integers or *
     *
     * @param string $cronCommand cron command
     * @return string Normalized cron command
     */
    protected static function normalizeFields($cronCommand)
    {
        $fieldArray = self::splitFields($cronCommand);
        $fieldArray[0] = self::normalizeIntegerField($fieldArray[0], 0, 59);
        $fieldArray[1] = self::normalizeIntegerField($fieldArray[1], 0, 23);
        $fieldArray[2] = self::normalizeIntegerField($fieldArray[2], 1, 31);
        $fieldArray[3] = self::normalizeMonthAndWeekdayField($fieldArray[3], true);
        $fieldArray[4] = self::normalizeMonthAndWeekdayField($fieldArray[4], false);
        $normalizedCronCommand = implode(' ', $fieldArray);
        return $normalizedCronCommand;
    }

    /**
     * Split a given cron command like '23 * * * *' to an array with five fields.
     *
     * @throws \InvalidArgumentException If splitted array does not contain five entries
     * @param string $cronCommand cron command
     * @return array
     */
    protected static function splitFields($cronCommand)
    {
        $fields = explode(' ', $cronCommand);
        if (count($fields) !== 5) {
            throw new \InvalidArgumentException('Unable to split given cron command to five fields.', 1291227373);
        }
        return $fields;
    }

    /**
     * Normalize month field.
     *
     * @param string $expression Month field expression
     * @param bool $isMonthField TRUE if month field is handled, FALSE for weekday field
     * @return string Normalized expression
     */
    protected static function normalizeMonthAndWeekdayField($expression, $isMonthField = true)
    {
        if ((string)$expression === '*') {
            $fieldValues = '*';
        } else {
            // Fragment expression by , / and - and substitute three letter code of month and weekday to numbers
            $listOfCommaValues = explode(',', $expression);
            $fieldArray = [];
            foreach ($listOfCommaValues as $listElement) {
                if (strpos($listElement, '/') !== false) {
                    list($left, $right) = explode('/', $listElement);
                    if (strpos($left, '-') !== false) {
                        list($leftBound, $rightBound) = explode('-', $left);
                        $leftBound = self::normalizeMonthAndWeekday($leftBound, $isMonthField);
                        $rightBound = self::normalizeMonthAndWeekday($rightBound, $isMonthField);
                        $left = $leftBound . '-' . $rightBound;
                    } else {
                        if ((string)$left !== '*') {
                            $left = self::normalizeMonthAndWeekday($left, $isMonthField);
                        }
                    }
                    $fieldArray[] = $left . '/' . $right;
                } elseif (strpos($listElement, '-') !== false) {
                    list($left, $right) = explode('-', $listElement);
                    $left = self::normalizeMonthAndWeekday($left, $isMonthField);
                    $right = self::normalizeMonthAndWeekday($right, $isMonthField);
                    $fieldArray[] = $left . '-' . $right;
                } else {
                    $fieldArray[] = self::normalizeMonthAndWeekday($listElement, $isMonthField);
                }
            }
            $fieldValues = implode(',', $fieldArray);
        }
        return $isMonthField ? self::normalizeIntegerField($fieldValues, 1, 12) : self::normalizeIntegerField($fieldValues, 1, 7);
    }

    /**
     * Normalize integer field.
     *
     * @throws \InvalidArgumentException If field is invalid or out of bounds
     * @param string $expression Expression
     * @param int $lowerBound Lower limit of result list
     * @param int $upperBound Upper limit of result list
     * @return string Normalized expression
     */
    protected static function normalizeIntegerField($expression, $lowerBound = 0, $upperBound = 59)
    {
        if ((string)$expression === '*') {
            $fieldValues = '*';
        } else {
            $listOfCommaValues = explode(',', $expression);
            $fieldArray = [];
            foreach ($listOfCommaValues as $listElement) {
                if (strpos($listElement, '/') !== false) {
                    list($left, $right) = explode('/', $listElement);
                    if ((string)$left === '*') {
                        $leftList = self::convertRangeToListOfValues($lowerBound . '-' . $upperBound);
                    } else {
                        $leftList = self::convertRangeToListOfValues($left);
                    }
                    $fieldArray[] = self::reduceListOfValuesByStepValue($leftList . '/' . $right);
                } elseif (strpos($listElement, '-') !== false) {
                    $fieldArray[] = self::convertRangeToListOfValues($listElement);
                } elseif (MathUtility::canBeInterpretedAsInteger($listElement)) {
                    $fieldArray[] = $listElement;
                } else {
                    throw new \InvalidArgumentException('Unable to normalize integer field.', 1291429389);
                }
            }
            $fieldValues = implode(',', $fieldArray);
        }
        if ((string)$fieldValues === '') {
            throw new \InvalidArgumentException('Unable to convert integer field to list of values: Result list empty.', 1291422012);
        }
        if ((string)$fieldValues !== '*') {
            $fieldList = explode(',', $fieldValues);
            sort($fieldList);
            $fieldList = array_unique($fieldList);
            if (current($fieldList) < $lowerBound) {
                throw new \InvalidArgumentException('Lowest element in list is smaller than allowed.', 1291470084);
            }
            if (end($fieldList) > $upperBound) {
                throw new \InvalidArgumentException('An element in the list is higher than allowed.', 1291470170);
            }
            $fieldValues = implode(',', $fieldList);
        }
        return (string)$fieldValues;
    }

    /**
     * Convert a range of integers to a list: 4-6 results in a string '4,5,6'
     *
     * @throws \InvalidArgumentException If range can not be converted to list
     * @param string $range Integer-integer
     * @return array
     */
    protected static function convertRangeToListOfValues($range)
    {
        if ((string)$range === '') {
            throw new \InvalidArgumentException('Unable to convert range to list of values with empty string.', 1291234985);
        }
        $rangeArray = explode('-', $range);
        // Sanitize fields and cast to integer
        foreach ($rangeArray as $fieldNumber => $fieldValue) {
            if (!MathUtility::canBeInterpretedAsInteger($fieldValue)) {
                throw new \InvalidArgumentException('Unable to convert value to integer.', 1291237668);
            }
            $rangeArray[$fieldNumber] = (int)$fieldValue;
        }

        $rangeArrayCount = count($rangeArray);
        if ($rangeArrayCount === 1) {
            $resultList = $rangeArray[0];
        } elseif ($rangeArrayCount === 2) {
            $left = $rangeArray[0];
            $right = $rangeArray[1];
            if ($left > $right) {
                throw new \InvalidArgumentException('Unable to convert range to list: Left integer must not be greater than right integer.', 1291237145);
            }
            $resultListArray = [];
            for ($i = $left; $i <= $right; $i++) {
                $resultListArray[] = $i;
            }
            $resultList = implode(',', $resultListArray);
        } else {
            throw new \InvalidArgumentException('Unable to convert range to list of values.', 1291234986);
        }
        return (string)$resultList;
    }

    /**
     * Reduce a given list of values by step value.
     * Following a range with ``/<number>'' specifies skips of the number's value through the range.
     * 1-5/2 -> 1,3,5
     * 2-10/3 -> 2,5,8
     *
     * @throws \InvalidArgumentException if step value is invalid or if resulting list is empty
     * @param string $stepExpression Step value expression
     * @return string Comma separated list of valid values
     */
    protected static function reduceListOfValuesByStepValue($stepExpression)
    {
        if ($stepExpression === '') {
            throw new \InvalidArgumentException('Unable to convert step values.', 1291234987);
        }
        $stepValuesAndStepArray = explode('/', $stepExpression);
        $stepValuesAndStepArrayCount = count($stepValuesAndStepArray);
        if ($stepValuesAndStepArrayCount < 1 || $stepValuesAndStepArrayCount > 2) {
            throw new \InvalidArgumentException('Unable to convert step values: Multiple slashes found.', 1291242168);
        }
        $left = $stepValuesAndStepArray[0];
        $right = $stepValuesAndStepArray[1];
        if ((string)$stepValuesAndStepArray[0] === '') {
            throw new \InvalidArgumentException('Unable to convert step values: Left part of / is empty.', 1291414955);
        }
        if ((string)$stepValuesAndStepArray[1] === '') {
            throw new \InvalidArgumentException('Unable to convert step values: Right part of / is empty.', 1291414956);
        }
        if (!MathUtility::canBeInterpretedAsInteger($right)) {
            throw new \InvalidArgumentException('Unable to convert step values: Right part must be a single integer.', 1291414957);
        }
        $right = (int)$right;
        $leftArray = explode(',', $left);
        $validValues = [];
        $currentStep = $right;
        foreach ($leftArray as $leftValue) {
            if (!MathUtility::canBeInterpretedAsInteger($leftValue)) {
                throw new \InvalidArgumentException('Unable to convert step values: Left part must be a single integer or comma separated list of integers.', 1291414958);
            }
            if ($currentStep === 0) {
                $currentStep = $right;
            }
            if ($currentStep === $right) {
                $validValues[] = (int)$leftValue;
            }
            $currentStep--;
        }
        if (empty($validValues)) {
            throw new \InvalidArgumentException('Unable to convert step values: Result value list is empty.', 1291414959);
        }
        return implode(',', $validValues);
    }

    /**
     * Dispatcher method for normalizeMonth and normalizeWeekday
     *
     * @param string $expression Month or weekday to be normalized
     * @param bool $isMonth TRUE if a month is handled, FALSE for weekday
     * @return string normalized month or weekday
     */
    protected static function normalizeMonthAndWeekday($expression, $isMonth = true)
    {
        $expression = $isMonth ? self::normalizeMonth($expression) : self::normalizeWeekday($expression);
        return (string)$expression;
    }

    /**
     * Accept a string representation or integer number of a month like
     * 'jan', 'February', 01, ... and convert to normalized integer value 1 .. 12
     *
     * @throws \InvalidArgumentException If month string can not be converted to integer
     * @param string $month Month representation
     * @return int month integer representation between 1 and 12
     */
    protected static function normalizeMonth($month)
    {
        $timestamp = strtotime('2010-' . $month . '-01');
        // timestamp must be >= 2010-01-01 and <= 2010-12-01
        if (!$timestamp || $timestamp < strtotime('2010-01-01') || $timestamp > strtotime('2010-12-01')) {
            throw new \InvalidArgumentException('Unable to convert given month name.', 1291083486);
        }
        return (int)date('n', $timestamp);
    }

    /**
     * Accept a string representation or integer number of a weekday like
     * 'mon', 'Friday', 3, ... and convert to normalized integer value 1 .. 7
     *
     * @throws \InvalidArgumentException If weekday string can not be converted
     * @param string $weekday Weekday representation
     * @return int weekday integer representation between 1 and 7
     */
    protected static function normalizeWeekday($weekday)
    {
        $normalizedWeekday = false;
        // 0 (sunday) -> 7
        if ((string)$weekday === '0') {
            $weekday = 7;
        }
        if ($weekday >= 1 && $weekday <= 7) {
            $normalizedWeekday = (int)$weekday;
        }
        if (!$normalizedWeekday) {
            // Convert string representation like 'sun' to integer
            $timestamp = strtotime('next ' . $weekday, mktime(0, 0, 0, 1, 1, 2010));
            if (!$timestamp || $timestamp < strtotime('2010-01-01') || $timestamp > strtotime('2010-01-08')) {
                throw new \InvalidArgumentException('Unable to convert given weekday name.', 1291163589);
            }
            $normalizedWeekday = (int)date('N', $timestamp);
        }
        return $normalizedWeekday;
    }
}
