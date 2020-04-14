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

namespace TYPO3\CMS\Extbase\Tests\Unit\Routing\Aspect;

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Routing\Aspect\StaticRangeMapper;

/**
 * Test case
 */
class StaticRangeMapperTest extends TestCase
{
    public function valueSettingsDataProvider(): array
    {
        return [
            '1-3' => [
                '1',
                '3',
                [
                    'a' => null,
                    '0' => null,
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => null,
                    'z' => null,
                ]
            ],
            'b-d' => [
                'b',
                'd',
                [
                    '0' => null,
                    'a' => null,
                    'b' => 'b',
                    'c' => 'c',
                    'd' => 'd',
                    'e' => null,
                    '9' => null,
                ]
            ],
            '@-C' => [
                '@',
                'C',
                [
                    '?' => null,
                    '@' => '@',
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => null,
                ]
            ],
            '0-11 (no zero prefix)' => [
                '0',
                '11',
                [
                    '00' => null,
                    '01' => null,
                    '0' => '0',
                    '1' => '1',
                    '10' => '10',
                    '11' => '11',
                    '12' => null,
                ]
            ],
            '2-11 (no zero prefix)' => [
                '2',
                '11',
                [
                    '00' => null,
                    '01' => null,
                    '02' => null,
                    '03' => null,
                    '0' => null,
                    '1' => null,
                    '2' => '2',
                    '3' => '3',
                    '10' => '10',
                    '11' => '11',
                    '12' => null,
                ]
            ],
            '00-11 (no zero prefix)' => [
                '00',
                '11',
                [
                    '00' => '00',
                    '01' => '01',
                    '10' => '10',
                    '11' => '11',
                    '12' => null,
                ]
            ],
            '02-11 (apply zero prefix)' => [
                '02',
                '11',
                [
                    '00' => null,
                    '01' => null,
                    '02' => '02',
                    '03' => '03',
                    '10' => '10',
                    '11' => '11',
                    '12' => null,
                ]
            ],
            '11-02 (apply zero prefix)' => [
                '11',
                '02',
                [
                    '00' => null,
                    '01' => null,
                    '02' => '02',
                    '03' => '03',
                    '10' => '10',
                    '11' => '11',
                    '12' => null,
                ]
            ],
            '011-002 (apply zero prefix)' => [
                '011',
                '002',
                [
                    '000' => null,
                    '001' => null,
                    '002' => '002',
                    '003' => '003',
                    '010' => '010',
                    '011' => '011',
                    '012' => null,
                ]
            ],
            '2-100 (no zero prefix)' => [
                '2',
                '100',
                [
                    '2' => '2',
                    '02' => null,
                    '002' => null,
                    '100' => '100',
                ]
            ],
            // use maximum char length (3) even if '02' is given
            '02-100 (zero prefix)' => [
                '02',
                '100',
                [
                    '2' => null,
                    '02' => null,
                    '002' => '002',
                    '100' => '100',
                ]
            ],
            '002-100 (zero prefix)' => [
                '002',
                '100',
                [
                    '2' => null,
                    '02' => null,
                    '002' => '002',
                    '100' => '100',
                ]
            ],
        ];
    }

    /**
     * @param string $start
     * @param string $end
     * @param array $expectations
     *
     * @test
     * @dataProvider valueSettingsDataProvider
     */
    public function resolveDeterminesValues(string $start, string $end, array $expectations): void
    {
        $subject = new StaticRangeMapper([
            'start' => $start,
            'end' => $end,
        ]);
        foreach ($expectations as $value => $expectation) {
            self::assertSame($expectation, $subject->resolve((string)$value));
        }
    }
}
