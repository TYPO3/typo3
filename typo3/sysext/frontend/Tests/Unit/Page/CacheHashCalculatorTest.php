<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

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

/**
 * Testcase
 */
class CacheHashCalculatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator
     */
    protected $subject;

    protected function setUp()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $this->subject = $this->getMock(\TYPO3\CMS\Frontend\Page\CacheHashCalculator::class, ['foo']);
        $this->subject->setConfiguration([
            'excludedParameters' => ['exclude1', 'exclude2'],
            'cachedParametersWhiteList' => [],
            'requireCacheHashPresenceParameters' => ['req1', 'req2'],
            'excludedParametersIfEmpty' => [],
            'includePageId' => true,
            'excludeAllEmptyParameters' => false
        ]);
    }

    /**
     * @dataProvider cacheHashCalculationDataProvider
     * @test
     */
    public function cacheHashCalculationWorks($params, $expected)
    {
        $this->assertEquals($expected, $this->subject->calculateCacheHash($params));
    }

    /**
     * @return array
     */
    public function cacheHashCalculationDataProvider()
    {
        return [
            'Empty parameters should not return a hash' => [
                [],
                ''
            ],
            'Trivial key value combination should generate hash' => [
                [
                    'encryptionKey' => 't3lib_cacheHashTest',
                    'key' => 'value'
                ],
                '5cfdcf826275558b3613dd51714a0a17'
            ],
            'Multiple parameters should generate hash' => [
                [
                    'a' => 'v',
                    'b' => 'v',
                    'encryptionKey' => 't3lib_cacheHashTest'
                ],
                '0f40b089cdad149aea99e9bf4badaa93'
            ]
        ];
    }

    /**
     * @dataProvider getRelevantParametersDataprovider
     * @test
     */
    public function getRelevantParametersWorks($params, $expected)
    {
        $actual = $this->subject->getRelevantParameters($params);
        $this->assertEquals($expected, array_keys($actual));
    }

    /**
     * @return array
     */
    public function getRelevantParametersDataprovider()
    {
        return [
            'Empty list should be passed through' => ['', []],
            'Simple parameter should be passed through and the encryptionKey should be added' => [
                'key=v&id=42',
                ['encryptionKey', 'id', 'key']
            ],
            'Simple parameter should be passed through' => [
                'key1=v&key2=v&id=42',
                ['encryptionKey', 'id', 'key1', 'key2']
            ],
            'System and exclude parameters should be omitted' => [
                'id=1&type=3&exclude1=x&no_cache=1',
                []
            ],
            'System and exclude parameters (except id) should be omitted, others should stay' => [
                'id=1&type=3&key=x&no_cache=1',
                ['encryptionKey', 'id', 'key']
            ],
            'System and exclude parameters should be omitted and id is not required to be specified' => [
                '&type=3&no_cache=1',
                []
            ]
        ];
    }

    /**
     * @dataProvider canGenerateForParametersDataProvider
     * @test
     */
    public function canGenerateForParameters($params, $expected)
    {
        $this->assertEquals($expected, $this->subject->generateForParameters($params));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionCode 1467983513
     */
    public function generateForParametersThrowsExceptionWhenIdIsNotSpecified()
    {
        $this->subject->generateForParameters('&key=x');
    }

    /**
     * @return array
     */
    public function canGenerateForParametersDataProvider()
    {
        $knowHash = 'fac112f7e662c83c19b57142c3a921f5';
        return [
            'Empty parameters should not return an hash' => ['&id=42', ''],
            'Querystring has no relevant parameters so we should not have a cacheHash' => ['&exclude1=val', ''],
            'Querystring has only system parameters so we should not have a cacheHash' => ['&id=42&type=val', ''],
            'Trivial key value combination should generate hash' => ['&id=42&key=value', $knowHash],
            'Only the relevant parts should be taken into account' => ['&id=42&key=value&exclude1=val', $knowHash],
            'Only the relevant parts should be taken into account(exclude2 before key)' => ['&id=42&exclude2=val&key=value', $knowHash],
            'System parameters should not be taken into account (except id)' => ['&id=42&type=23&key=value', $knowHash],
            'Admin panel parameters should not be taken into account' => ['&id=42&TSFE_ADMIN_PANEL[display]=7&key=value', $knowHash],
            'Trivial hash for sorted parameters should be right' => ['&id=42&a=v&b=v', '52c8a1299e20324f90377c43153c4987'],
            'Parameters should be sorted before cHash is created' => ['&id=42&b=v&a=v', '52c8a1299e20324f90377c43153c4987'],
            'Empty argument names are filtered out before cHash calculation' => ['&id=42&b=v&a=v&=dummy', '52c8a1299e20324f90377c43153c4987']
        ];
    }

    /**
     * @dataProvider parametersRequireCacheHashDataprovider
     * @test
     */
    public function parametersRequireCacheHashWorks($params, $expected)
    {
        $this->assertEquals($expected, $this->subject->doParametersRequireCacheHash($params));
    }

    /**
     * @return array
     */
    public function parametersRequireCacheHashDataprovider()
    {
        return [
            'Empty parameter strings should not require anything.' => ['', false],
            'Normal parameters aren\'t required.' => ['key=value', false],
            'Configured "req1" to be required.' => ['req1=value', true],
            'Configured "req1" to be required, should also work in combined context' => ['&key=value&req1=value', true],
            'Configured "req1" to be required, should also work in combined context (key at the end)' => ['req1=value&key=value', true]
        ];
    }

    /**
     * In case the cHashOnlyForParameters is set, other parameters should not
     * incluence the cHash (except the encryption key of course)
     *
     * @dataProvider canWhitelistParametersDataProvider
     * @test
     */
    public function canWhitelistParameters($params, $expected)
    {
        $method = new \ReflectionMethod(\TYPO3\CMS\Frontend\Page\CacheHashCalculator::class, 'setCachedParametersWhiteList');
        $method->setAccessible(true);
        $method->invoke($this->subject, ['whitep1', 'whitep2']);
        $this->assertEquals($expected, $this->subject->generateForParameters($params));
    }

    /**
     * @return array
     */
    public function canWhitelistParametersDataProvider()
    {
        $oneParamHash = 'eae50a13101afd53a9d2c543230eb5bb';
        $twoParamHash = '701e2d2f1becc9d1b71d327e5cb1c3ed';
        return [
            'Even with the whitelist enabled, empty parameters should not return an hash.' => ['', ''],
            'Whitelisted parameters should have a hash.' => ['&id=42&whitep1=value', $oneParamHash],
            'Blacklisted parameter should not influence hash.' => ['&id=42&whitep1=value&black=value', $oneParamHash],
            'Multiple whitelisted parameters should work' => ['&id=42&whitep1=value&whitep2=value', $twoParamHash],
            'The order should not influce the hash.' => ['&id=42&whitep2=value&black=value&whitep1=value', $twoParamHash]
        ];
    }

    /**
     * @dataProvider canSkipParametersWithEmptyValuesDataProvider
     * @test
     */
    public function canSkipParametersWithEmptyValues($params, $settings, $expected)
    {
        $this->subject->setConfiguration($settings);
        $actual = $this->subject->getRelevantParameters($params);
        $this->assertEquals($expected, array_keys($actual));
    }

    /**
     * @return array
     */
    public function canSkipParametersWithEmptyValuesDataProvider()
    {
        return [
            'The default configuration does not allow to skip an empty key.' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => [], 'excludeAllEmptyParameters' => false],
                ['encryptionKey', 'id', 'key1', 'key2', 'key3']
            ],
            'Due to the empty value, "key2" should be skipped(with equals sign' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => ['key2'], 'excludeAllEmptyParameters' => false],
                ['encryptionKey', 'id', 'key1', 'key3']
            ],
            'Due to the empty value, "key2" should be skipped(without equals sign)' => [
                '&id=42&key1=v&key2&key3',
                ['excludedParametersIfEmpty' => ['key2'], 'excludeAllEmptyParameters' => false],
                ['encryptionKey', 'id', 'key1', 'key3']
            ],
            'Due to the empty value, "key2" and "key3" should be skipped' => [
                '&id=42&key1=v&key2=&key3=',
                ['excludedParametersIfEmpty' => [], 'excludeAllEmptyParameters' => true],
                ['encryptionKey', 'id', 'key1']
            ]
        ];
    }
}
