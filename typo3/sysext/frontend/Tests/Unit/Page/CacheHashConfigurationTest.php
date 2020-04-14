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

use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\PermutationUtility;
use TYPO3\CMS\Frontend\Page\CacheHashConfiguration;

class CacheHashConfigurationTest extends TestCase
{
    public function nonArrayValueThrowsExceptionDataProvider(): array
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

    /**
     * @param string $aspect
     * @param mixed $value
     *
     * @test
     * @dataProvider nonArrayValueThrowsExceptionDataProvider
     */
    public function nonArrayValueThrowsException(string $aspect, $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1580225311);
        new CacheHashConfiguration([$aspect => $value]);
    }

    public function nonScalarValueThrowsExceptionDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            [null, [], new \stdClass()]
        ]);
    }

    /**
     * @param string $aspect
     * @param mixed $value
     *
     * @test
     * @dataProvider nonScalarValueThrowsExceptionDataProvider
     */
    public function nonScalarValueThrowsException(string $aspect, $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1580225312);
        new CacheHashConfiguration([$aspect => [$value]]);
    }

    public function emptyIndicatedValueThrowsExceptionDataProvider(): array
    {
        return PermutationUtility::meltArrayItems([
            [
                CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
            ],
            ['=', '^', '~']
        ]);
    }

    /**
     * @param string $aspect
     * @param string $value
     *
     * @test
     * @dataProvider emptyIndicatedValueThrowsExceptionDataProvider
     */
    public function emptyIndicatedValueThrowsException(string $aspect, string $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1580225313);
        new CacheHashConfiguration([$aspect => [$value]]);
    }

    public function equalsResolvesParameterValueDataProvider(): array
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
            [['eq', 'equals', 'other', 'prefixed-equals-other']]
        ]);
    }

    /**
     * @param string $aspect
     * @param array $values
     * @param array $positives
     * @param array $negatives
     *
     * @test
     * @dataProvider equalsResolvesParameterValueDataProvider
     */
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

    public function startsWithResolvesParameterValueDataProvider(): array
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
            [['eq', 'other', 'prefixed-equals-other']]
        ]);
    }

    /**
     * @param string $aspect
     * @param array $values
     * @param array $positives
     * @param array $negatives
     *
     * @test
     * @dataProvider startsWithResolvesParameterValueDataProvider
     */
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

    public function containsResolvesParameterValueDataProvider(): array
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
            [['eq', 'other']]
        ]);
    }

    /**
     * @param string $aspect
     * @param array $values
     * @param array $positives
     * @param array $negatives
     *
     * @test
     * @dataProvider containsResolvesParameterValueDataProvider
     */
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

    public function appliesResolvesParameterValueDataProvider(): array
    {
        // currently using "contains" data provider, could have own test sets as well
        return $this->containsResolvesParameterValueDataProvider();
    }

    /**
     * @param string $aspect
     * @param array $values
     * @param array $positives
     * @param array $negatives
     *
     * @test
     * @dataProvider appliesResolvesParameterValueDataProvider
     */
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
