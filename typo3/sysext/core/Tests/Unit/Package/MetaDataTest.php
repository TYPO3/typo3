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

namespace TYPO3\CMS\Core\Tests\Unit\Package;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Package\MetaData;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MetaDataTest extends UnitTestCase
{
    public static function typeIsCorrectlyResolvedDataProvider(): \Generator
    {
        yield 'framework type is set' => [
            'typo3-cms-framework',
            true,
            true,
        ];

        yield 'extension type is set' => [
            'typo3-cms-extension',
            true,
            false,
        ];

        yield 'no type is set' => [
            null,
            false,
            false,
        ];

        yield 'other type is set' => [
            'other',
            false,
            false,
        ];
    }

    #[DataProvider('typeIsCorrectlyResolvedDataProvider')]
    #[Test]
    public function typeIsCorrectlyResolved(?string $type, bool $isExtension, bool $isFramework): void
    {
        $metaData = new MetaData('foo');
        $metaData->setPackageType($type);
        self::assertSame($isExtension, $metaData->isExtensionType());
        self::assertSame($isFramework, $metaData->isFrameworkType());
    }

    public static function versionAndStabilityIsParsedCorrectlyDataProvider(): \Generator
    {
        yield 'no version set' => [
            'givenVersion' => '1.0.0+no-version-set',
            'finalPrettyVersion' => '1.0.0',
            'stability' => 'stable',
            'build' => 'no-version-set',
        ];
        yield 'custom build with stability' => [
            'givenVersion' => '5.2.32-rc2+build1234',
            'finalPrettyVersion' => '5.2.32-RC2',
            'stability' => 'RC',
            'build' => 'build1234',
        ];
        yield 'numbered version' => [
            'givenVersion' => '1.2.3',
            'finalPrettyVersion' => '1.2.3',
            'stability' => 'stable',
        ];
        yield 'numbered version major release' => [
            'givenVersion' => '3.0.0',
            'finalPrettyVersion' => '3.0.0',
            'stability' => 'stable',
        ];
        yield 'dev version' => [
            'givenVersion' => '1.2.3-dev',
            'finalPrettyVersion' => '1.2.3-dev',
            'stability' => 'dev',
        ];
        yield 'dev branch' => [
            'givenVersion' => 'dev-main',
            'finalPrettyVersion' => 'dev-main',
            'stability' => 'dev',
        ];
        yield 'alpha version' => [
            'givenVersion' => '1.2.3-alpha1',
            'finalPrettyVersion' => '1.2.3-alpha1',
            'stability' => 'alpha',
        ];
    }

    #[DataProvider('versionAndStabilityIsParsedCorrectlyDataProvider')]
    #[Test]
    public function versionAndStabilityIsParsedCorrectly(string $givenVersion, string $finalPrettyVersion, string $stability, ?string $build = null): void
    {
        $metaData = new MetaData('foo');
        $metaData->setVersion($givenVersion);
        self::assertSame($stability, $metaData->getStability()->value);
        self::assertSame($finalPrettyVersion, $metaData->getVersion());
        self::assertSame($build, $metaData->getBuild());
    }
}
