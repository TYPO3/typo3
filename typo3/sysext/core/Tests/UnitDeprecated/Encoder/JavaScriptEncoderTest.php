<?php
namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Encoder;

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

use TYPO3\CMS\Core\Encoder\JavaScriptEncoder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class JavaScriptEncoderTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * Data provider for encodeEncodesCorrectly.
     *
     * @return array
     */
    public function encodeEncodesCorrectlyDataProvider()
    {
        return [
            'Immune characters are returned as is' => [
                '._,',
                '._,'
            ],
            'Alphanumerical characters are returned as is' => [
                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
                'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
            ],
            'Angel brackets and ampersand are encoded' => [
                '<>&',
                '\\x3C\\x3E\\x26'
            ],
            'Quotes and slashes are encoded' => [
                '"\'\\/',
                '\\x22\\x27\\x5C\\x2F'
            ],
            'Empty string stays empty' => [
                '',
                ''
            ],
            'Exclamation mark and space are properly encoded' => [
                'Hello World!',
                'Hello\\x20World\\x21'
            ],
            'Whitespaces are properly encoded' => [
                "\t" . LF . CR . ' ',
                '\\x09\\x0A\\x0D\\x20'
            ],
            'Null byte is properly encoded' => [
                "\0",
                '\\x00'
            ],
            'Umlauts are properly encoded' => [
                'ÜüÖöÄä',
                '\\xDC\\xFC\\xD6\\xF6\\xC4\\xE4'
            ]
        ];
    }

    /**
     * @test
     * @param string $input
     * @param string  $expected
     * @dataProvider encodeEncodesCorrectlyDataProvider
     */
    public function encodeEncodesCorrectly($input, $expected)
    {
        $subject = new JavaScriptEncoder();
        $this->assertSame($expected, $subject->encode($input));
    }
}
