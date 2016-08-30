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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\CsvUtility;

/**
 * Test cases of CsvUtility
 */
class CsvUtilityTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function csvToArrayDataProvider()
    {
        return [
            'Valid data' => [
                'input'  => 'Column A, Column B, Column C' . LF . 'Value, Value2, Value 3',
                'fieldDelimiter' => ',',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A', ' Column B', ' Column C'],
                    ['Value', ' Value2', ' Value 3']
                ]
            ],

            'Valid data with enclosed "' => [
                'input'  => '"Column A", "Column B", "Column C"' . LF . '"Value", "Value2", "Value 3"',
                'fieldDelimiter' => ',',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A', 'Column B', 'Column C'],
                    ['Value', 'Value2', 'Value 3']
                ]
            ],

            'Valid data with semicolons and enclosed "' => [
                'input'  => '"Column A"; "Column B"; "Column C"' . LF . '"Value"; "Value2"; "Value 3"',
                'fieldDelimiter' => ';',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A', 'Column B', 'Column C'],
                    ['Value', 'Value2', 'Value 3']
                ]
            ],

            'Valid data with semicolons and enclosed " and two columns' => [
                'input'  => '"Column A"; "Column B"; "Column C"; "Column D"' . LF . '"Value"; "Value2"; "Value 3"',
                'fieldDelimiter' => ';',
                'fieldEnclosure' => '"',
                'maximumColumns' => 2,
                'expectedResult' => [
                    ['Column A', 'Column B'],
                    ['Value', 'Value2']
                ]
            ],

            'Data with comma but configured with semicolons and enclosed "' => [
                'input'  => '"Column A", "Column B", "Column C"' . LF . '"Value", "Value2", "Value 3"',
                'fieldDelimiter' => ';',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A, "Column B", "Column C"'],
                    ['Value, "Value2", "Value 3"']
                ]
            ]
        ];
    }

    /**
     * @dataProvider csvToArrayDataProvider
     * @test
     */
    public function csvToArraySplitsAsExpected($input, $fieldDelimiter, $fieldEnclosure, $maximumColumns, $expectedResult)
    {
        $this->assertEquals($expectedResult, CsvUtility::csvToArray($input, $fieldDelimiter, $fieldEnclosure, $maximumColumns));
    }
}
