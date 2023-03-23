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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases of CsvUtility
 */
class CsvUtilityTest extends UnitTestCase
{
    public static function csvToArrayDataProvider(): array
    {
        return [
            'Valid data' => [
                'input'  => 'Column A, Column B, Column C' . LF . 'Value, Value2, Value 3',
                'fieldDelimiter' => ',',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A', ' Column B', ' Column C'],
                    ['Value', ' Value2', ' Value 3'],
                ],
            ],

            'Valid data with enclosed "' => [
                'input'  => '"Column A", "Column B", "Column C"' . LF . '"Value", "Value2", "Value 3"',
                'fieldDelimiter' => ',',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A', 'Column B', 'Column C'],
                    ['Value', 'Value2', 'Value 3'],
                ],
            ],

            'Valid data with semicolons and enclosed "' => [
                'input'  => '"Column A"; "Column B"; "Column C"' . LF . '"Value"; "Value2"; "Value 3"',
                'fieldDelimiter' => ';',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A', 'Column B', 'Column C'],
                    ['Value', 'Value2', 'Value 3'],
                ],
            ],

            'Valid data with semicolons and enclosed " and two columns' => [
                'input'  => '"Column A"; "Column B"; "Column C"; "Column D"' . LF . '"Value"; "Value2"; "Value 3"',
                'fieldDelimiter' => ';',
                'fieldEnclosure' => '"',
                'maximumColumns' => 2,
                'expectedResult' => [
                    ['Column A', 'Column B'],
                    ['Value', 'Value2'],
                ],
            ],

            'Data with comma but configured with semicolons and enclosed "' => [
                'input'  => '"Column A", "Column B", "Column C"' . LF . '"Value", "Value2", "Value 3"',
                'fieldDelimiter' => ';',
                'fieldEnclosure' => '"',
                'maximumColumns' => 0,
                'expectedResult' => [
                    ['Column A, "Column B", "Column C"'],
                    ['Value, "Value2", "Value 3"'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider csvToArrayDataProvider
     * @test
     */
    public function csvToArraySplitsAsExpected(string $input, string $fieldDelimiter, string $fieldEnclosure, int $maximumColumns, array $expectedResult): void
    {
        self::assertEquals($expectedResult, CsvUtility::csvToArray($input, $fieldDelimiter, $fieldEnclosure, $maximumColumns));
    }

    public static function csvValuesDataProvider(): array
    {
        return [
            'row with semicolon as delimiter (TYPE_PASSTHROUGH)' => [
                ['val1', 'val2', 'val3'],
                ';',
                '"',
                '"val1";"val2";"val3"',
                CsvUtility::TYPE_PASSTHROUGH,
            ],
            'row where value contains line feeds (TYPE_PASSTHROUGH)' => [
                ['val1 line1' . "\n" . 'val1 line2', 'val2 line1' . "\r\n" . 'val2 line2', 'val3'],
                ',',
                '"',
                '"val1 line1' . "\n" . 'val1 line2","val2 line1' . "\r\n" . 'val2 line2","val3"',
                CsvUtility::TYPE_PASSTHROUGH,
            ],
            'row with all possible control chars (TYPE_PASSTHROUGH)' => [
                ['=val1', '+val2', '*val3', '%val4', '@val5', '-val6'],
                ',',
                '"',
                '"=val1","+val2","*val3","%val4","@val5","-val6"',
                CsvUtility::TYPE_PASSTHROUGH,
            ],
            'row with spacing and delimiting chars (TYPE_PASSTHROUGH)' => [
                [' val1', "\tval2", "\nval3", "\r\nval4", ',val5,', '"val6"'],
                ',',
                '"',
                '" val1","' . "\tval2" . '","' . "\nval3" . '","' . "\r\nval4" . '",",val5,","""val6"""' ,
                CsvUtility::TYPE_PASSTHROUGH,
            ],
            'row with all possible control chars (TYPE_PREFIX_CONTROLS)' => [
                ['=val1', '+val2', '*val3', '%val4', '@val5', '-val6'],
                ',',
                '"',
                '"\'=val1","\'+val2","\'*val3","\'%val4","\'@val5","\'-val6"',
                CsvUtility::TYPE_PREFIX_CONTROLS,
            ],
            'row with spacing and delimiting chars (TYPE_PREFIX_CONTROLS)' => [
                [' val1', "\tval2", "\nval3", "\r\nval4", ',val5,', '"val6"'],
                ',',
                '"',
                '" val1","' . "'\tval2" . '","' . "'\nval3" . '","' . "'\r\nval4" . '",",val5,","""val6"""' ,
                CsvUtility::TYPE_PREFIX_CONTROLS,
            ],
            'row with all possible control chars (TYPE_REMOVE_CONTROLS)' => [
                ['=val1', '+val2', '*val3', '%val4', '@val5', '-val6'],
                ',',
                '"',
                '"val1","val2","val3","val4","val5","val6"',
                CsvUtility::TYPE_REMOVE_CONTROLS,
            ],
            'row with spacing and delimiting chars (TYPE_REMOVE_CONTROLS)' => [
                [' val1', "\tval2", "\nval3", "\r\nval4", ',val5,', '"val6"'],
                ',',
                '"',
                '" val1","val2","val3","val4",",val5,","""val6"""' ,
                CsvUtility::TYPE_REMOVE_CONTROLS,
            ],
        ];
    }

    /**
     * @dataProvider csvValuesDataProvider
     * @test
     */
    public function csvValuesReturnsExpectedResult(array $row, string $delimiter, string $quote, string $expectedResult, int $flag): void
    {
        self::assertEquals($expectedResult, CsvUtility::csvValues($row, $delimiter, $quote, $flag));
    }
}
