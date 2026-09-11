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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\DebugUtility
 */
final class DebugUtilityTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        DebugUtility::usePlainTextOutput(true);
        DebugUtility::useAnsiColor(true);
    }

    #[Test]
    public function debugNotEncodesHtmlInputIfPlainText(): void
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

    #[Test]
    public function debugAppendsCallerPathToDefaultHeader(): void
    {
        DebugUtility::usePlainTextOutput(true);
        DebugUtility::useAnsiColor(false);

        ob_start();
        DebugUtility::debug('Foobar');
        $output = ob_get_contents();
        ob_end_clean();

        self::assertStringContainsString(
            'Debug: /typo3/sysext/core/Tests/Unit/Utility/DebugUtilityTest.php:61',
            $output
        );
    }

    #[Test]
    public function debugAppendsCallerPathToDefaultHeaderOutsideProjectRoot(): void
    {
        DebugUtility::usePlainTextOutput(true);
        DebugUtility::useAnsiColor(false);

        // Create a temporary file outside the project root
        $file = tempnam(sys_get_temp_dir(), 'debugutility-test-');
        self::assertNotFalse($file);

        file_put_contents($file, <<<'PHP'
<?php
use TYPO3\CMS\Core\Utility\DebugUtility;
DebugUtility::debug('Foobar');
PHP);

        ob_start();
        require_once $file;
        $output = ob_get_contents();
        ob_end_clean();

        self::assertStringContainsString(
            'Debug: ' . $file . ':3',
            $output
        );
    }

    #[Test]
    public function debugAppendsCallerPathToCustomHeader(): void
    {
        DebugUtility::usePlainTextOutput(true);
        DebugUtility::useAnsiColor(false);

        ob_start();
        DebugUtility::debug('Foobar', header: 'Barfoo');
        $output = ob_get_contents();
        ob_end_clean();

        self::assertStringContainsString(
            'Barfoo: /typo3/sysext/core/Tests/Unit/Utility/DebugUtilityTest.php:105',
            $output
        );
    }

    #[Test]
    public function debugEncodesHtmlInputIfNoPlainText(): void
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

    public static function convertVariableToStringReturnsVariableContentDataProvider(): array
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
                'array (3 items)' . PHP_EOL
                . '   0 => "foo" (3 chars)' . PHP_EOL
                . '   1 => "bar" (3 chars)' . PHP_EOL
                . '   baz => array (1 item)' . PHP_EOL
                . '      0 => 42 (integer)',
            ],
            'Debug object' => [
                $object,
                'stdClass prototype object' . PHP_EOL
                . '   foo => public 42 (integer)' . PHP_EOL
                . '   bar => public array (1 item)' . PHP_EOL
                . '      0 => "baz" (3 chars)',
            ],
        ];
    }

    /**
     * @param mixed $variable
     */
    #[DataProvider('convertVariableToStringReturnsVariableContentDataProvider')]
    #[Test]
    public function convertVariableToStringReturnsVariableContent($variable, string $expected): void
    {
        self::assertSame($expected, DebugUtility::convertVariableToString($variable));
    }
}
