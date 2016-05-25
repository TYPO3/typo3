<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\DebugUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\DebugUtility
 */
class DebugUtilityTest extends UnitTestCase
{
    protected function tearDown()
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

        $this->assertContains(
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

        $this->assertContains(
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
. '   foo => 42 (integer)' . PHP_EOL
. '   bar => array(1 item)' . PHP_EOL
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
        $this->assertSame($expected, DebugUtility::convertVariableToString($variable));
    }
}
