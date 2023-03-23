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

namespace TYPO3\CMS\Form\Tests\Unit\ViewHelpers\Form;

use TYPO3\CMS\Form\ViewHelpers\Form\DatePickerViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatePickerViewHelperTest extends UnitTestCase
{
    public static function convertDateFormatToDatePickerFormatReturnsTransformedFormatDataProvider(): array
    {
        return [
            [
                'd',
                'dd',
            ],
            [
                'D',
                'D',
            ],
            [
                'j',
                'o',
            ],
            [
                'l',
                'DD',
            ],
            [
                'F',
                'MM',
            ],
            [
                'm',
                'mm',
            ],
            [
                'M',
                'M',
            ],
            [
                'n',
                'm',
            ],
            [
                'Y',
                'yy',
            ],
            [
                'y',
                'y',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider convertDateFormatToDatePickerFormatReturnsTransformedFormatDataProvider
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat(string $input, string $expected): void
    {
        $mock = \Closure::bind(static function (DatePickerViewHelper $datePickerViewHelper) use ($input, &$result) {
            $result = $datePickerViewHelper->convertDateFormatToDatePickerFormat($input);
        }, null, DatePickerViewHelper::class);
        $mock(new DatePickerViewHelper());
        self::assertSame($expected, $result);
    }
}
