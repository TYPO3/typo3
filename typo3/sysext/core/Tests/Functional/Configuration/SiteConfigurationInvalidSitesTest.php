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

namespace TYPO3\CMS\Core\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Loader\Exception\YamlParseException;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Exception\InvalidSiteConfigurationException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteConfigurationInvalidSitesTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Configuration/Fixtures/InvalidSites' => 'typo3conf/sites',
    ];

    protected function setUp(): void
    {
        putenv('TYPO3_TESTING_INVALID_SITES_EMPTY_HOST=');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('TYPO3_TESTING_INVALID_SITES_EMPTY_HOST');
        parent::tearDown();
    }

    #[Test]
    public function resolveAllExistingSitesSkipsInvalidSites(): void
    {
        $sites = $this->get(SiteConfiguration::class)->resolveAllExistingSites(false);
        self::assertSame(['valid'], array_keys($sites));
    }

    #[Test]
    public function resolveAllExistingSitesRawSkipsInvalidSites(): void
    {
        $sites = $this->get(SiteConfiguration::class)->resolveAllExistingSitesRaw();
        // csp.yaml is not evaluated for raw sites
        self::assertEqualsCanonicalizing(['valid', 'broken-csp'], array_keys($sites));
    }

    public static function invalidSitesAreCollectedDataProvider(): iterable
    {
        yield 'malformed base' => [
            'invalid-base',
            [
                'rootPageId' => 2,
                'type' => \InvalidArgumentException::class,
                'code' => 1436717322,
                'message' => 'The parsedUri "https:///" appears to be malformed',
                'field' => 'base',
            ],
        ];
        yield 'base with empty environment variable' => [
            'empty-env-base',
            [
                'rootPageId' => 3,
                'type' => \InvalidArgumentException::class,
                'code' => 1436717322,
                'message' => 'The parsedUri "https:///" appears to be malformed',
                'field' => 'base',
            ],
        ];
        yield 'malformed base of matching base variant' => [
            'invalid-base-variant',
            [
                'rootPageId' => 4,
                'type' => \InvalidArgumentException::class,
                'code' => 1436717322,
                'field' => 'baseVariants.1.base',
            ],
        ];
        yield 'malformed language base' => [
            'invalid-language-base',
            [
                'rootPageId' => 5,
                'type' => \InvalidArgumentException::class,
                'code' => 1436717322,
                'message' => 'The parsedUri "https://" appears to be malformed',
                'field' => 'languages.1.base',
            ],
        ];
        yield 'missing language locale' => [
            'missing-locale',
            [
                'rootPageId' => 6,
                'type' => InvalidSiteConfigurationException::class,
                'code' => 1792506653,
                'field' => 'languages.0.locale',
            ],
        ];
        yield 'missing root page id' => [
            'missing-root-page-id',
            [
                'rootPageId' => null,
                'type' => InvalidSiteConfigurationException::class,
                'code' => 1792506654,
                'field' => 'rootPageId',
            ],
        ];
        yield 'syntax error in config.yaml' => [
            'broken-yaml',
            [
                'rootPageId' => null,
                'type' => YamlParseException::class,
                'field' => null,
            ],
        ];
        yield 'syntax error in csp.yaml' => [
            'broken-csp',
            [
                'rootPageId' => 9,
                'type' => YamlParseException::class,
                'field' => null,
            ],
        ];
    }

    #[DataProvider('invalidSitesAreCollectedDataProvider')]
    #[Test]
    public function invalidSitesAreCollected(string $identifier, array $expected): void
    {
        $subject = $this->get(SiteConfiguration::class);
        $subject->resolveAllExistingSites(false);
        $invalidSites = $subject->getInvalidSites();
        self::assertArrayHasKey($identifier, $invalidSites);
        $invalidSite = $invalidSites[$identifier];
        self::assertSame($identifier, $invalidSite['identifier']);
        self::assertNotSame('', $invalidSite['message']);
        foreach ($expected as $key => $value) {
            self::assertSame($value, $invalidSite[$key], 'Unexpected value for "' . $key . '"');
        }
    }

    #[Test]
    public function invalidSitesAreCollectedWithWarmCoreCache(): void
    {
        $subject = $this->get(SiteConfiguration::class);
        $subject->resolveAllExistingSites(false);
        $expected = array_keys($subject->getInvalidSites());

        // Simulate a new request: runtime cache is empty, core cache is warm
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertSame(['valid'], array_keys($subject->getAllExistingSites()));
        self::assertEqualsCanonicalizing($expected, array_keys($subject->getInvalidSites()));
    }

    #[Test]
    public function getInvalidSiteByRootPageIdReturnsInvalidSite(): void
    {
        $subject = $this->get(SiteConfiguration::class);
        self::assertSame('invalid-language-base', $subject->getInvalidSiteByRootPageId(5)['identifier'] ?? null);
        self::assertNull($subject->getInvalidSiteByRootPageId(1));
    }
}
