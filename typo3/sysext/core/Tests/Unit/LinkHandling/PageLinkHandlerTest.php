<?php
namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

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

use TYPO3\CMS\Core\LinkHandling\PageLinkHandler;

class PageLinkHandlerTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
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
            ],
            'page with alias - cool style' => [
                [
                    'alias' => 'alias13'
                ],
                [
                    'pagealias' => 'alias13'
                ],
                't3://page?alias=alias13'
            ],
            'page with alias and type - cool style' => [
                [
                    'alias' => 'alias13',
                    'type' => 31
                ],
                [
                    'pagealias' => 'alias13',
                    'pagetype' => '31'
                ],
                't3://page?alias=alias13&type=31'
            ],
            'page with alias and parameters - cool style' => [
                [
                    'alias' => 'alias13',
                    'my' => 'additional',
                    'parameter' => 'that',
                    'are' => 'nice'
                ],
                [
                    'pagealias' => 'alias13',
                    'parameters' => 'my=additional&parameter=that&are=nice'
                ],
                't3://page?alias=alias13&my=additional&parameter=that&are=nice',
            ],
            'page with alias and parameters and fragment - cool style' => [
                [
                    'alias' => 'alias13',
                    'my' => 'additional',
                    'parameter' => 'that',
                    'are' => 'nice'
                ],
                [
                    'pagealias' => 'alias13',
                    'parameters' => 'my=additional&parameter=that&are=nice',
                    'fragment' => 'again'
                ],
                't3://page?alias=alias13&my=additional&parameter=that&are=nice#again',
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
        $this->assertEquals($expected, $subject->resolveHandlerData($input));
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
        $this->assertEquals($expected, $subject->asString($parameters));
    }
}
