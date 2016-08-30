<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

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

use TYPO3\CMS\Backend\Form\Element\NoneElement;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class NoneElementTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function formatValueDataProvider()
    {
        return [
            'format with empty format configuration' => [
                [
                    'format' => '',
                ],
                '',
                '',
            ],
            'format to date' => [
                [
                    'format' => 'date',
                ],
                '1412358894',
                '03-10-2014'
            ],
            'format to date with empty timestamp' => [
                [
                    'format' => 'date',
                ],
                '0',
                ''
            ],
            'format to date with blank timestamp' => [
                [
                    'format' => 'date',
                ],
                '',
                ''
            ],
            'format to date with option strftime' => [
                [
                    'format' => 'date',
                    'format.' => [
                        'option' => '%d-%m',
                        'strftime' => true,
                    ],
                ],
                '1412358894',
                '03-10'
            ],
            'format to date with option' => [
                [
                    'format' => 'date',
                    'format.' => [
                        'option' => 'd-m',
                    ],
                ],
                '1412358894',
                '03-10'
            ],
            'format to datetime' => [
                [
                    'format' => 'datetime',
                ],
                '1412358894',
                '17:54 03-10-2014'
            ],
            'format to datetime with empty value' => [
                [
                    'format' => 'datetime',
                ],
                '',
                ''
            ],
            'format to datetime with null value' => [
                [
                    'format' => 'datetime',
                ],
                null,
                ''
            ],
            'format to time' => [
                [
                    'format' => 'time',
                ],
                '1412358894',
                '17:54'
            ],
            'format to time with empty value' => [
                [
                    'format' => 'time',
                ],
                '',
                ''
            ],
            'format to time with null value' => [
                [
                    'format' => 'time',
                ],
                null,
                ''
            ],
            'format to timesec' => [
                [
                    'format' => 'timesec',
                ],
                '1412358894',
                '17:54:54'
            ],
            'format to timesec with empty value' => [
                [
                    'format' => 'timesec',
                ],
                '',
                ''
            ],
            'format to timesec with null value' => [
                [
                    'format' => 'timesec',
                ],
                null,
                ''
            ],
            'format to year' => [
                [
                    'format' => 'year',
                ],
                '1412358894',
                '2014'
            ],
            'format to year with empty value' => [
                [
                    'format' => 'year',
                ],
                '',
                ''
            ],
            'format to year with null value' => [
                [
                    'format' => 'year',
                ],
                null,
                ''
            ],
            'format to int' => [
                [
                    'format' => 'int',
                ],
                '123.00',
                '123'
            ],
            'format to int with base' => [
                [
                    'format' => 'int',
                    'format.' => [
                        'base' => 'oct',
                    ],
                ],
                '123',
                '173'
            ],
            'format to int with empty value' => [
                [
                    'format' => 'int',
                ],
                '',
                '0'
            ],
            'format to float' => [
                [
                    'format' => 'float',
                ],
                '123',
                '123.00'
            ],
            'format to float with precision' => [
                [
                    'format' => 'float',
                    'format.' => [
                        'precision' => '4',
                    ],
                ],
                '123',
                '123.0000'
            ],
            'format to float with empty value' => [
                [
                    'format' => 'float',
                ],
                '',
                '0.00'
            ],
            'format to number' => [
                [
                    'format' => 'number',
                    'format.' => [
                        'option' => 'b',
                    ],
                ],
                '123',
                '1111011'
            ],
            'format to number with empty option' => [
                [
                    'format' => 'number',
                ],
                '123',
                ''
            ],
            'format to md5' => [
                [
                    'format' => 'md5',
                ],
                'joh316',
                'bacb98acf97e0b6112b1d1b650b84971'
            ],
            'format to md5 with empty value' => [
                [
                    'format' => 'md5',
                ],
                '',
                'd41d8cd98f00b204e9800998ecf8427e'
            ],
            'format to filesize' => [
                [
                    'format' => 'filesize',
                ],
                '100000',
                '98 Ki'
            ],
            'format to filesize with empty value' => [
                [
                    'format' => 'filesize',
                ],
                '',
                '0 '
            ],
            'format to filesize with option appendByteSize' => [
                [
                    'format' => 'filesize',
                    'format.' => [
                        'appendByteSize' => true,
                    ],
                ],
                '100000',
                '98 Ki (100000)'
            ],
        ];
    }

    /**
     * @param array $config
     * @param string $itemValue
     * @param string $expectedResult
     * @dataProvider formatValueDataProvider
     * @test
     */
    public function formatValueWithGivenConfiguration($config, $itemValue, $expectedResult)
    {
        /** @var NoneElement|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $subject = $this->getAccessibleMock(NoneElement::class, ['dummy'], [], '', false);
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $result = $subject->_call('formatValue', $config, $itemValue);
        date_default_timezone_set($timezoneBackup);

        $this->assertEquals($expectedResult, $result);
    }
}
