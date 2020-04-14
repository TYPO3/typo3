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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\DebugUtility
 */
class DebugUtilityTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        DebugUtility::usePlainTextOutput(true);
        DebugUtility::useAnsiColor(true);
    }

    /**
     * @test
     */
    public function debugNotEncodesHtmlInputIfPlainText()
    {
        DebugUtility::usePlainTextOutput(true);
        DebugUtility::useAnsiColor(false);

        ob_start();
        DebugUtility::debug('<script>alert(\'Hello world!\')</script>');
        $output = ob_get_contents();
        ob_end_clean();

        self::assertStringContainsString(
            '<script>alert(\'Hello world!\')</script>',
            $output
        );
    }

    /**
     * @test
     */
    public function debugEncodesHtmlInputIfNoPlainText()
    {
        DebugUtility::usePlainTextOutput(false);
        DebugUtility::useAnsiColor(false);

        ob_start();
        DebugUtility::debug('<script>alert(\'Hello world!\')</script>');
        $output = ob_get_contents();
        ob_end_clean();

        self::assertStringContainsString(
            '&lt;script&gt;alert(\'Hello world!\')&lt;/script&gt;',
            $output
        );
    }

    /**
     * @return array
     */
    public function convertVariableToStringReturnsVariableContentDataProvider()
    {
        $object = new \stdClass();
        $object->foo = 42;
        $object->bar = ['baz'];

        return [
            'Debug string' => [
                'Hello world!',
                '"Hello world!" (12 chars)',
            ],
            'Debug array' => [
                [
                    'foo',
                    'bar',
                    'baz' => [
                        42,
                    ],
                ],
                'array(3 items)' . PHP_EOL
                    . '   0 => "foo" (3 chars)' . PHP_EOL
                    . '   1 => "bar" (3 chars)' . PHP_EOL
                    . '   baz => array(1 item)' . PHP_EOL
                    . '      0 => 42 (integer)',
            ],
            'Debug object' => [
                $object,
                'stdClass prototype object' . PHP_EOL
                    . '   foo => public 42 (integer)' . PHP_EOL
                    . '   bar => public array(1 item)' . PHP_EOL
                    . '      0 => "baz" (3 chars)'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider convertVariableToStringReturnsVariableContentDataProvider
     * @param mixed $variable
     * @param string $expected
     */
    public function convertVariableToStringReturnsVariableContent($variable, $expected)
    {
        self::assertSame($expected, DebugUtility::convertVariableToString($variable));
    }
}
