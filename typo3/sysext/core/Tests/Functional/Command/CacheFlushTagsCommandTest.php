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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\CacheManager;

final class CacheFlushTagsCommandTest extends AbstractCommandTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    // Set pages cache database backend, testing-framework sets this to NullBackend by default.
                    'pages' => [
                        'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                    ],
                    'cache_flush_tags_test_system' => [
                        'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\Typo3DatabaseBackend',
                        'groups' => ['system'],
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

    #[Test]
    public function tagsAreFlushedInAllCachesByDefault(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-entry', ['dummy'], ['tag1']);
        $systemCache = $this->get(CacheManager::class)->getCache('cache_flush_tags_test_system');
        $systemCache->set('system-entry', ['dummy'], ['tag1']);

        $result = $this->executeConsoleCommand('cache:flushtags tag1');

        self::assertSame(0, $result['status']);
        self::assertFalse($pageCache->has('page-entry'));
        self::assertFalse($systemCache->has('system-entry'));
    }

    #[Test]
    public function tagsAreFlushedInTheGivenGroupsOnly(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-entry', ['dummy'], ['tag1']);
        $systemCache = $this->get(CacheManager::class)->getCache('cache_flush_tags_test_system');
        $systemCache->set('system-entry', ['dummy'], ['tag1']);

        $result = $this->executeConsoleCommand('cache:flushtags tag1 --groups system');

        self::assertSame(0, $result['status']);
        self::assertTrue($pageCache->has('page-entry'));
        self::assertFalse($systemCache->has('system-entry'));
    }

    #[Test]
    public function unknownGroupIsReported(): void
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-entry', ['dummy'], ['tag1']);

        $result = $this->executeConsoleCommand('cache:flushtags tag1 --groups pages,unknown');

        self::assertSame(1, $result['status']);
        self::assertStringContainsString('Cache group "unknown" does not exist.', $result['stdout']);
        self::assertFalse($pageCache->has('page-entry'));
    }
}
