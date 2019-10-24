<?php
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class GeneralUtilityTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     * @dataProvider idnaEncodeDataProvider
     * @param $actual
     * @param $expected
     */
    public function idnaEncodeConvertsUnicodeCharsToASCIIString($actual, $expected)
    {
        $result = GeneralUtility::idnaEncode($actual);
        self::assertSame($expected, $result);
    }

    /**
     * Data provider for method idnaEncode in GeneralUtility class.
     * IDNA converter has to convert special chars (UTF-8) to ASCII compatible chars.
     *
     * @returns array
     */
    public function idnaEncodeDataProvider()
    {
        return [
            'empty string' => [
                '',
                ''
            ],
            'null value' => [
                null,
                ''
            ],
            'string with ascii chars' => [
                'example',
                'example'
            ],
            'domain (1) with utf8 chars' => [
                'dömäin.example',
                'xn--dmin-moa0i.example'
            ],
            'domain (2) with utf8 chars' => [
                'äaaa.example',
                'xn--aaa-pla.example'
            ],
            'domain (3) with utf8 chars' => [
                'déjà.vu.example',
                'xn--dj-kia8a.vu.example'
            ],
            'domain (4) with utf8 chars' => [
                'foo.âbcdéf.example',
                'foo.xn--bcdf-9na9b.example'
            ],
            'domain with utf8 char (german umlaut)' => [
                'exömple.com',
                'xn--exmple-xxa.com'
            ],
            'email with utf8 char (german umlaut)' => [
                'joe.doe@dömäin.de',
                'joe.doe@xn--dmin-moa0i.de'
            ]
        ];
    }
}
