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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use TYPO3\CMS\Core\LinkHandling\TelephoneLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TelephoneLinkHandlerTest
 */
class TelephoneLinkHandlerTest extends UnitTestCase
{

    /**
     * Data to resolve strings to arrays and vice versa, external, mail, page
     *
     * @return array
     */
    public function resolveParametersForNonFilesDataProvider(): array
    {
        return [
            'telephone number with protocol' => [
                [
                    'telephone' => 'tel:012345678'
                ],
                [
                    'telephone' => '012345678'
                ],
                'tel:012345678'
            ],
            'telephone number with protocol and spaces' => [
                [
                    'telephone' => 'tel:+49 123 45 56 78'
                ],
                [
                    'telephone' => '+49 123 45 56 78'
                ],
                'tel:+49123455678'
            ],
            'invalid telephone number' => [
                [
                    'telephone' => 'tel:+43-hello-world'
                ],
                [
                    'telephone' => '+43-hello-world'
                ],
                'tel:+43'
            ],
            'telephone number with weird characters' => [
                [
                    'telephone' => 'tel:+43/123!45&56%78'
                ],
                [
                    'telephone' => '+43/123!45&56%78'
                ],
                'tel:+43123455678'
            ],
        ];
    }

    /**
     * @test
     *
     * @param array $input
     * @param array  $expected
     *
     * @dataProvider resolveParametersForNonFilesDataProvider
     */
    public function resolveReturnsSplitParameters($input, $expected): void
    {
        $subject = new TelephoneLinkHandler();
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
        $subject = new TelephoneLinkHandler();
        self::assertEquals($expected, $subject->asString($parameters));
    }
}
