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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use TYPO3\CMS\Core\LinkHandling\PageLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageLinkHandlerTest extends UnitTestCase
{

    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider()
    {
        return [
            'current page - cool style' => [
                [
                    'uid' => 'current'
                ],
                [
                    'pageuid' => 'current'
                ],
                't3://page?uid=current'
            ],
            'current empty page - cool style' => [
                [

                ],
                [
                    'pageuid' => 'current'
                ],
                't3://page?uid=current'
            ],
            'simple page - cool style' => [
                [
                    'uid' => 13
                ],
                [
                    'pageuid' => 13
                ],
                't3://page?uid=13'
            ]
        ];
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $expected
     * @param string $finalString
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function resolveReturnsSplitParameters($input, $expected, $finalString)
    {
        $subject = new PageLinkHandler();
        // fragment it is processed outside handler data
        if (isset($expected['fragment'])) {
            unset($expected['fragment']);
        }
        self::assertEquals($expected, $subject->resolveHandlerData($input));
    }

    /**
     * @test
     *
     * @param string $input
     * @param array  $parameters
     * @param string $expected
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function splitParametersToUnifiedIdentifier($input, $parameters, $expected)
    {
        $subject = new PageLinkHandler();
        self::assertEquals($expected, $subject->asString($parameters));
    }
}
