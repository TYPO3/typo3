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

namespace TYPO3\CMS\Core\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;

final class CacheFlushTagsCommandTest extends AbstractCommandTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_cache_flush_tags',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    // Set pages cache database backend, testing-framework sets this to NullBackend by default.
                    'pages' => [
                        'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                    ],
                ],
            ],
        ],
    ];

    #[Test]
    public function cachesCanBeFlushedByTagsRemovingOnlyEntriesWithSpecifiedTag(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('dummy-page-cache-hash-one', ['dummy'], ['tag1', 'tag2'], 0);
        $pageCache->set('dummy-page-cache-hash-two', ['dummy'], ['tag1'], 0);

        $result = $this->executeConsoleCommand('cache:flushtags tag2');
        self::assertSame(0, $result['status']);
        self::assertFalse($pageCache->has('dummy-page-cache-hash-one'));
        self::assertTrue($pageCache->has('dummy-page-cache-hash-two'));
    }

    public static function extensionCacheIsFlushedByTagsDataProvider(): array
    {
        return [
            'all groups' => ['cache:flushtags tag2'],
            'pages group' => ['cache:flushtags tag2 --groups=pages'],
        ];
    }

    #[DataProvider('extensionCacheIsFlushedByTagsDataProvider')]
    #[Test]
    public function extensionCacheIsFlushedByTags(string $command): void
    {
        // test_cache_flush_tags configures this cache in its ext_localconf.php
        $extensionCache = $this->get(CacheManager::class)->getCache('test_cache_flush_tags');
        $extensionCache->set('dummy-extension-cache-hash-one', 'dummy', ['tag1', 'tag2'], 0);
        $extensionCache->set('dummy-extension-cache-hash-two', 'dummy', ['tag1'], 0);

        $result = $this->executeConsoleCommand($command);
        self::assertSame(0, $result['status']);
        self::assertFalse($extensionCache->has('dummy-extension-cache-hash-one'));
        self::assertTrue($extensionCache->has('dummy-extension-cache-hash-two'));
    }
}
