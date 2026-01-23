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

namespace TYPO3\CMS\Frontend\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\CacheHashConfiguration;
use TYPO3\CMS\Frontend\Utility\CanonicalizationUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CanonicalizationUtilityTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'canonicalization-utility-test-key';
    }

    private function setUpCacheHashCalculator(): void
    {
        $configuration = new CacheHashConfiguration([
            'excludedParameters' => [],
            'cachedParametersWhiteList' => [],
            'requireCacheHashPresenceParameters' => [],
            'excludedParametersIfEmpty' => [],
            'excludeAllEmptyParameters' => false,
        ]);
        $cacheHashCalculator = new CacheHashCalculator($configuration, new HashService());
        GeneralUtility::addInstance(CacheHashCalculator::class, $cacheHashCalculator);
    }

    public static function getParamsToExcludeForCanonicalizedUrlDataProvider(): array
    {
        return [
            'empty query params excludes only the added id' => [
                'queryParams' => [],
                'pageId' => 1,
                'additionalCanonicalizedUrlParameters' => [],
                // id is added internally and excluded since no cHash-relevant params exist
                'expected' => ['id'],
            ],
            'multiple system parameters are excluded along with id' => [
                'queryParams' => ['type' => '0', 'no_cache' => '1', 'cHash' => 'abc123'],
                'pageId' => 1,
                'additionalCanonicalizedUrlParameters' => [],
                'expected' => ['cHash', 'id', 'no_cache', 'type'],
            ],
            'system parameters with same value as page id are excluded (bug #92105)' => [
                'queryParams' => ['type' => '7', 'no_cache' => '7'],
                'pageId' => 7,
                'additionalCanonicalizedUrlParameters' => [],
                'expected' => ['id', 'no_cache', 'type'],
            ],
            'mixed system and regular parameters with same value as page id' => [
                'queryParams' => ['type' => '7', 'tx_plugin[param]' => '7'],
                'pageId' => 7,
                'additionalCanonicalizedUrlParameters' => [],
                // type is system param (excluded), tx_plugin[param] is cHash-relevant (not excluded)
                // When cHash-relevant params exist, id is included in cHashArray so not excluded
                'expected' => ['type'],
            ],
            'additional canonicalized url parameters are kept' => [
                'queryParams' => ['type' => '0', 'tx_news_pi1[news]' => '123'],
                'pageId' => 1,
                'additionalCanonicalizedUrlParameters' => ['tx_news_pi1[news]'],
                // type is excluded, tx_news_pi1[news] would normally not be excluded (cHash-relevant)
                'expected' => ['type'],
            ],
            'regular parameters relevant for cHash are not excluded' => [
                'queryParams' => ['tx_plugin[action]' => 'list'],
                'pageId' => 1,
                'additionalCanonicalizedUrlParameters' => [],
                // tx_plugin[action] is cHash-relevant, so only system params would be excluded
                // When cHash-relevant params exist, id is in cHashArray, so nothing to exclude
                'expected' => [],
            ],
        ];
    }

    #[DataProvider('getParamsToExcludeForCanonicalizedUrlDataProvider')]
    #[Test]
    public function getParamsToExcludeForCanonicalizedUrlReturnsExpectedParams(
        array $queryParams,
        int $pageId,
        array $additionalCanonicalizedUrlParameters,
        array $expected
    ): void {
        $this->setUpCacheHashCalculator();

        $request = new ServerRequest('https://example.com/', 'GET');
        $request = $request->withQueryParams($queryParams);

        $result = CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(
            $pageId,
            $additionalCanonicalizedUrlParameters,
            $request
        );

        sort($result);
        sort($expected);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function getParamsToExcludeWorksWithoutRequest(): void
    {
        $this->setUpCacheHashCalculator();

        $result = CanonicalizationUtility::getParamsToExcludeForCanonicalizedUrl(1);

        // When no request exists, only id is added and excluded
        self::assertSame(['id'], $result);
    }
}
