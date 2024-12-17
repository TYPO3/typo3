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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\DispositionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DispositionConfigurationTest extends UnitTestCase
{
    public static function effectivePackagesAreResolvedDataProvider(): \Generator
    {
        yield 'empty packages' => [
            'packages' => [],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => ['a/a', 'b/b', 'c/c'],
        ];
        yield 'missing definition for `*`' => [
            'packages' => [
                // missing here: '*' => true,
                'b/b' => true,
            ],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => ['b/b'],
        ];
        yield 'all to be included' => [
            'packages' => [
                '*' => true,
            ],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => ['a/a', 'b/b', 'c/c'],
        ];
        yield 'all to be included, b/b & c/c dropped' => [
            'packages' => [
                '*' => true,
                'b/b' => false,
                'c/c' => false,
            ],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => ['a/a'],
        ];
        yield 'all to be included, ignoring configured non/existing' => [
            'packages' => [
                '*' => true,
                'non/existing' => true,
            ],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => ['a/a', 'b/b', 'c/c'],
        ];
        yield 'all to be dropped' => [
            'packages' => [
                '*' => false,
            ],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => [],
        ];
        yield 'all to be dropped, b/b & c/c included' => [
            'packages' => [
                '*' => false,
                'b/b' => true,
                'c/c' => true,
            ],
            'packageNames' => ['a/a', 'b/b', 'c/c'],
            'expectation' => ['b/b', 'c/c'],
        ];
    }

    #[DataProvider('effectivePackagesAreResolvedDataProvider')]
    #[Test]
    public function effectivePackagesAreResolved(array $packages, array $packageNames, array $expectation): void
    {
        $subject = new DispositionConfiguration(true, true, null, [], $packages);
        self::assertSame($expectation, $subject->resolveEffectivePackages(...$packageNames));
    }

    public static function reportingUrlIsNormalizedDataProvider(): \Generator
    {
        yield 'null' => [null, null];
        yield 'false' => [false, false];
        yield 'true' => [true, true];
        yield 'url' => ['https://example.org/csp', 'https://example.org/csp'];
        yield 'empty' => ['', ''];
        yield '0 int to false' => [0, false];
        yield '0 string to false' => ['0', false];
        yield '1 int to true' => [1, true];
        yield '1 string to true' => ['1', true];
        yield 'int to string' => [12345, '12345'];
        yield 'string' => ['12345', '12345'];
        yield 'array to null' => [['array'], null];
        yield 'object to null' => [new \stdClass(), null];
    }

    #[DataProvider('reportingUrlIsNormalizedDataProvider')]
    #[Test]
    public static function reportingUrlIsNormalized(mixed $reportingUrl, null|bool|string $expectation): void
    {
        $subject = new DispositionConfiguration(true, true, $reportingUrl);
        self::assertSame($expectation, $subject->reportingUrl);
    }
}
