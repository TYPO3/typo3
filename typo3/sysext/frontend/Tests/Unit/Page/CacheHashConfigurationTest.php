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

namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\PermutationUtility;
use TYPO3\CMS\Frontend\Page\CacheHashConfiguration;

final class CacheHashConfigurationTest extends TestCase
{
    public static function nonArrayValueThrowsExceptionDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            ['true', true, 1, new \stdClass()],
        ]);
    }

    #[DataProvider('nonArrayValueThrowsExceptionDataProvider')]
    #[Test]
    public function nonArrayValueThrowsException(string $aspect, mixed $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1580225311);
        new CacheHashConfiguration([$aspect => $value]);
    }

    public static function nonScalarValueThrowsExceptionDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            [null, [], new \stdClass()],
        ]);
    }

    #[DataProvider('nonScalarValueThrowsExceptionDataProvider')]
    #[Test]
    public function nonScalarValueThrowsException(string $aspect, mixed $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1580225312);
        new CacheHashConfiguration([$aspect => [$value]]);
    }

    public static function emptyIndicatedValueThrowsExceptionDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            ['=', '^', '~'],
        ]);
    }

    #[DataProvider('emptyIndicatedValueThrowsExceptionDataProvider')]
    #[Test]
    public function emptyIndicatedValueThrowsException(string $aspect, string $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1580225313);
        new CacheHashConfiguration([$aspect => [$value]]);
    }

    public static function equalsResolvesParameterValueDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            [['equals-a', '=equals-b', '^equals', '~equals']],
            [['equals-a', 'equals-b']],
            [['eq', 'equals', 'other', 'prefixed-equals-other']],
        ]);
    }

    #[DataProvider('equalsResolvesParameterValueDataProvider')]
    #[Test]
    public function equalsResolvesParameterValue(string $aspect, array $values, array $positives, array $negatives): void
    {
        $configuration = new CacheHashConfiguration([$aspect => $values]);
        foreach ($positives as $probe) {
            self::assertTrue($configuration->equals($aspect, $probe), $probe);
        }
        foreach ($negatives as $probe) {
            self::assertFalse($configuration->equals($aspect, $probe), $probe);
        }
    }

    public static function startsWithResolvesParameterValueDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            [['equals-a', '=equals-b', '^equals', '~equals']],
            [['equals', 'equals-a', 'equals-b', 'equals-other']],
            [['eq', 'other', 'prefixed-equals-other']],
        ]);
    }

    #[DataProvider('startsWithResolvesParameterValueDataProvider')]
    #[Test]
    public function startsWithResolvesParameterValue(string $aspect, array $values, array $positives, array $negatives): void
    {
        $configuration = new CacheHashConfiguration([$aspect => $values]);
        foreach ($positives as $probe) {
            self::assertTrue($configuration->startsWith($aspect, $probe), $probe);
        }
        foreach ($negatives as $probe) {
            self::assertFalse($configuration->startsWith($aspect, $probe), $probe);
        }
    }

    public static function containsResolvesParameterValueDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            [['equals-a', '=equals-b', '^equals', '~equals']],
            [['equals', 'equals-a', 'equals-b', 'equals-other', 'prefixed-equals-other']],
            [['eq', 'other']],
        ]);
    }

    #[DataProvider('containsResolvesParameterValueDataProvider')]
    #[Test]
    public function containsResolvesParameterValue(string $aspect, array $values, array $positives, array $negatives): void
    {
        $configuration = new CacheHashConfiguration([$aspect => $values]);
        foreach ($positives as $probe) {
            self::assertTrue($configuration->contains($aspect, $probe), $probe);
        }
        foreach ($negatives as $probe) {
            self::assertFalse($configuration->contains($aspect, $probe), $probe);
        }
    }

    public static function appliesResolvesParameterValueDataProvider(): array
    {
        // Currently using "contains" data provider, could have own test sets as well
        return self::containsResolvesParameterValueDataProvider();
    }

    #[DataProvider('appliesResolvesParameterValueDataProvider')]
    #[Test]
    public function appliesResolvesParameterValue(string $aspect, array $values, array $positives, array $negatives): void
    {
        $configuration = new CacheHashConfiguration([$aspect => $values]);
        foreach ($positives as $probe) {
            self::assertTrue($configuration->applies($aspect, $probe), $probe);
        }
        foreach ($negatives as $probe) {
            self::assertFalse($configuration->applies($aspect, $probe), $probe);
        }
    }
}
