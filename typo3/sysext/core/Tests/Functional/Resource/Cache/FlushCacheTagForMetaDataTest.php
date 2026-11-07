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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Cache;

use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlushCacheTagForMetaDataTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'features' => [
                'frontend.cache.autoTagging' => true,
            ],
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                    'flush_cache_tag_test_system' => [
                        'backend' => TransientMemoryBackend::class,
                        'groups' => ['system'],
                    ],
                ],
            ],
        ],
    ];

    #[Test]
    public function updatedMetaDataFlushesTagsOfFileAndMetaDataInAllCaches(): void
    {
        $cacheManager = $this->get(CacheManager::class);
        $pageCache = $cacheManager->getCache('pages');
        $pageCache->set('file-entry', ['dummy'], ['sys_file_5']);
        $pageCache->set('metadata-entry', ['dummy'], ['sys_file_metadata_7']);
        $pageCache->set('other-entry', ['dummy'], ['sys_file_6']);
        $systemCache = $cacheManager->getCache('flush_cache_tag_test_system');
        $systemCache->set('file-entry', ['dummy'], ['sys_file_5']);
        $afterCacheFlushedEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set('flush-cache-tag-test', static function (AfterCacheFlushedEvent $event) use (&$afterCacheFlushedEvents): void {
            $afterCacheFlushedEvents[] = $event;
        });
        $this->get(ListenerProvider::class)->addListener(AfterCacheFlushedEvent::class, 'flush-cache-tag-test');

        $this->get(EventDispatcherInterface::class)->dispatch(new AfterFileMetaDataUpdatedEvent(5, 7, []));

        self::assertFalse($pageCache->has('file-entry'));
        self::assertFalse($pageCache->has('metadata-entry'));
        self::assertTrue($pageCache->has('other-entry'));
        self::assertFalse($systemCache->has('file-entry'));
        self::assertCount(1, $afterCacheFlushedEvents);
        self::assertSame(['all' => ['sys_file_5', 'sys_file_metadata_7']], $afterCacheFlushedEvents[0]->result->tags);
    }
}
