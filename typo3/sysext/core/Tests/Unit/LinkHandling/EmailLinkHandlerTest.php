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

use TYPO3\CMS\Core\LinkHandling\EmailLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class EmailLinkHandlerTest extends UnitTestCase
{

    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider()
    {
        return [
            'email without protocol' => [
                [
                    'email' => 'one@example.com'
                ],
                [
                    'email' => 'one@example.com'
                ],
                'mailto:one@example.com'
            ],
            'email with protocol' => [
                [
                    'email' => 'mailto:one@example.com'
                ],
                [
                    'email' => 'one@example.com'
                ],
                'mailto:one@example.com'
            ],
            'email with protocol 2' => [
                [
                    'email' => 'mailto:info@example.org'
                ],
                [
                    'email' => 'info@example.org'
                ],
                'mailto:info@example.org'
            ],
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
        $subject = new EmailLinkHandler();
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
        $subject = new EmailLinkHandler();
        self::assertEquals($expected, $subject->asString($parameters));
    }
}
