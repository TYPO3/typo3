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
use TYPO3\CMS\Core\Crypto\HashAlgo;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\CacheHashConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheHashCalculatorTest extends UnitTestCase
{
    private array $configuration = [
        'excludedParameters' => ['exclude1', 'exclude2'],
        'cachedParametersWhiteList' => [],
        'requireCacheHashPresenceParameters' => ['req1', 'req2'],
        'excludedParametersIfEmpty' => [],
        'excludeAllEmptyParameters' => false,
    ];

    public static function cacheHashCalculationDataProvider(): array
    {
        return [
            'Empty parameters should not return a hash' => [
                [],
                '',
            ],
            'Trivial key value combination should generate hash' => [
                [
                    'encryptionKey' => 't3lib_cacheHashTest',
                    'key' => 'value',
                ],
                self::calculateCacheHash(['key' => 'value']),
            ],
            'Multiple parameters should generate hash' => [
                [
                    'a' => 'v',
                    'b' => 'v',
                    'encryptionKey' => 't3lib_cacheHashTest',
                ],
                self::calculateCacheHash(['a' => 'v', 'b' => 'v']),
            ],
        ];
    }

    #[DataProvider('cacheHashCalculationDataProvider')]
    #[Test]
    public function cacheHashCalculationWorks(array $params, string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $subject = new CacheHashCalculator(new CacheHashConfiguration($this->configuration), new HashService());
        self::assertEquals($expected, $subject->calculateCacheHash($params));
    }

    public static function getRelevantParametersDataProvider(): array
    {
        return [
            'Empty list should be passed through' => ['', []],
            'Simple parameter should be passed through and the encryptionKey should be added' => [
                'key=v&id=42',
                ['id', 'key'],
            ],
            'Simple parameter should be passed through' => [
                'key1=v&key2=v&id=42',
                ['id', 'key1', 'key2'],
            ],
            'System and exclude parameters should be omitted' => [
                'id=1&type=3&exclude1=x&no_cache=1',
                [],
            ],
            'System and exclude parameters (except id) should be omitted, others should stay' => [
                'id=1&type=3&key=x&no_cache=1',
                ['id', 'key'],
            ],
            'System and exclude parameters should be omitted and id is not required to be specified' => [
                'type=3&no_cache=1',
                [],
            ],
        ];
    }

    #[DataProvider('getRelevantParametersDataProvider')]
    #[Test]
    public function getRelevantParametersWorks(string $params, array $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $subject = new CacheHashCalculator(new CacheHashConfiguration($this->configuration), new HashService());
        $actual = $subject->getRelevantParameters($params);
        self::assertEquals($expected, array_keys($actual));
    }

    #[Test]
    public function generateForParametersThrowsExceptionWhenIdIsNotSpecified(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1467983513);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $subject = new CacheHashCalculator(new CacheHashConfiguration($this->configuration), new HashService());
        $subject->generateForParameters('&key=x');
    }

    public static function canGenerateForParametersDataProvider(): array
    {
        $knowHash = self::calculateCacheHash(['id' => '42', 'key' => 'value']);
        $mixedParamsHash = self::calculateCacheHash(['id' => '42', 'a' => 'v', 'b' => 'v']);
        return [
            'Empty parameters should not return an hash' => ['&id=42', ''],
            'Querystring has no relevant parameters so we should not have a cacheHash' => ['&exclude1=val', ''],
            'Querystring has only system parameters so we should not have a cacheHash' => ['&id=42&type=val', ''],
            'Trivial key value combination should generate hash' => ['&id=42&key=value', $knowHash],
            'Only the relevant parts should be taken into account' => ['&id=42&key=value&exclude1=val', $knowHash],
            'Only the relevant parts should be taken into account(exclude2 before key)' => ['&id=42&exclude2=val&key=value', $knowHash],
            'System parameters should not be taken into account (except id)' => ['&id=42&type=23&key=value', $knowHash],
            'Trivial hash for sorted parameters should be right' => ['&id=42&a=v&b=v', $mixedParamsHash],
            'Parameters should be sorted before cHash is created' => ['&id=42&b=v&a=v', $mixedParamsHash],
            'Empty argument names are filtered out before cHash calculation' => ['&id=42&b=v&a=v&=dummy', $mixedParamsHash],
        ];
    }

    #[DataProvider('canGenerateForParametersDataProvider')]
    #[Test]
    public function canGenerateForParameters(string $params, string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $subject = new CacheHashCalculator(new CacheHashConfiguration($this->configuration), new HashService());
        self::assertEquals($expected, $subject->generateForParameters($params));
    }

    public static function parametersRequireCacheHashDataProvider(): array
    {
        return [
            'Empty parameter strings should not require anything.' => ['', false],
            'Normal parameters aren\'t required.' => ['key=value', false],
            'Configured "req1" to be required.' => ['req1=value', true],
            'Configured "req1" to be required, should also work in combined context' => ['&key=value&req1=value', true],
            'Configured "req1" to be required, should also work in combined context (key at the end)' => ['req1=value&key=value', true],
        ];
    }

    #[DataProvider('parametersRequireCacheHashDataProvider')]
    #[Test]
    public function parametersRequireCacheHashWorks(string $params, bool $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $subject = new CacheHashCalculator(new CacheHashConfiguration($this->configuration), new HashService());
        self::assertEquals($expected, $subject->doParametersRequireCacheHash($params));
    }

    public static function canWhitelistParametersDataProvider(): array
    {
        $oneParamHash = self::calculateCacheHash(['id' => '42', 'whitep1' => 'value']);
        $twoParamHash = self::calculateCacheHash(['id' => '42', 'whitep1' => 'value', 'whitep2' => 'value']);
        return [
            'Even with the whitelist enabled, empty parameters should not return an hash.' => ['', ''],
            'Whitelisted parameters should have a hash.' => ['&id=42&whitep1=value', $oneParamHash],
            'Blacklisted parameter should not influence hash.' => ['&id=42&whitep1=value&black=value', $oneParamHash],
            'Multiple whitelisted parameters should work' => ['&id=42&whitep1=value&whitep2=value', $twoParamHash],
            'The order should not influce the hash.' => ['&id=42&whitep2=value&black=value&whitep1=value', $twoParamHash],
        ];
    }

    /**
     * In case the $TYPO3_CONF_VARS[FE][cacheHash][cachedParametersWhiteList] is set, other parameters should not
     * influence the cHash (except the encryption key of course)
     */
    #[DataProvider('canWhitelistParametersDataProvider')]
    #[Test]
    public function canWhitelistParameters(string $params, string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $configuration = new CacheHashConfiguration(
            array_merge($this->configuration, ['cachedParametersWhiteList' => ['whitep1', 'whitep2']])
        );
        $subject = new CacheHashCalculator($configuration, new HashService());
        self::assertEquals($expected, $subject->generateForParameters($params));
    }

    public static function canSkipParametersWithEmptyValuesDataProvider(): array
    {
        return [
            'The default configuration does not allow to skip an empty key.' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => [], 'excludeAllEmptyParameters' => false],
                ['id', 'key1', 'key2', 'key3'],
            ],
            'Due to the empty value, "key2" should be skipped (with equals sign)' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => ['key2'], 'excludeAllEmptyParameters' => false],
                ['id', 'key1', 'key3'],
            ],
            'Due to the empty value, "key2" should be skipped (without equals sign)' => [
                '&id=42&key1=v&key2&key3',
                ['excludedParametersIfEmpty' => ['key2'], 'excludeAllEmptyParameters' => false],
                ['id', 'key1', 'key3'],
            ],
            'Due to the empty value, "key2" and "key3" should be skipped' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => [], 'excludeAllEmptyParameters' => true],
                ['id', 'key1'],
            ],
            'Due to the empty value, "key1", "key2" and "key3" should be skipped (starting with "key")' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => ['^key'], 'excludeAllEmptyParameters' => false],
                ['id', 'key1'],
            ],
        ];
    }

    #[DataProvider('canSkipParametersWithEmptyValuesDataProvider')]
    #[Test]
    public function canSkipParametersWithEmptyValues(string $params, array $settings, array $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $configuration = new CacheHashConfiguration(
            array_merge($this->configuration, $settings)
        );
        $subject = new CacheHashCalculator($configuration, new HashService());
        $actual = $subject->getRelevantParameters($params);
        self::assertEquals($expected, array_keys($actual));
    }

    private static function calculateCacheHash(array $params, string $encryptionKey = 't3lib_cacheHashTest'): string
    {
        ksort($params);
        $secret = $encryptionKey . CacheHashCalculator::class;
        return hash_hmac(HashAlgo::SHA3_256->value, serialize($params), $secret);
    }
}
