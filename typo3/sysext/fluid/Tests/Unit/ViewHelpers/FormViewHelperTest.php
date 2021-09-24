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

namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FormViewHelperTest extends UnitTestCase
{
    /**
     * Data Provider for postProcessUriArgumentsForRequestHashWorks
     */
    public function argumentsForPostProcessUriArgumentsForRequestHash(): array
    {
        return [
            // simple values
            [
                [
                    'bla' => 'X',
                    'blubb' => 'Y',
                ],
                [
                    'bla',
                    'blubb',
                ],
            ],
            // Arrays
            [
                [
                    'bla' => [
                        'test1' => 'X',
                        'test2' => 'Y',
                    ],
                    'blubb' => 'Y',
                ],
                [
                    'bla[test1]',
                    'bla[test2]',
                    'blubb',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider argumentsForPostProcessUriArgumentsForRequestHash
     * @param $arguments
     * @param $expectedResults
     */
    public function postProcessUriArgumentsForRequestHashWorks($arguments, $expectedResults): void
    {
        $formViewHelper = new FormViewHelper();
        $results = [];
        $mock = \Closure::bind(static function (FormViewHelper $formViewHelper) use ($arguments, &$results) {
            return $formViewHelper->postProcessUriArgumentsForRequestHash($arguments, $results);
        }, null, FormViewHelper::class);
        $mock($formViewHelper);
        self::assertEquals($expectedResults, $results);
    }
}
