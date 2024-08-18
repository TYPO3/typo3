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
        $subject = new DispositionConfiguration(true, true, [], $packages);
        self::assertSame($expectation, $subject->resolveEffectivePackages(...$packageNames));
    }
}
