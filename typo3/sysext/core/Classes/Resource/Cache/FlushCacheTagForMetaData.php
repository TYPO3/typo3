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

namespace TYPO3\CMS\Core\Resource\Cache;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataCreatedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;

final readonly class FlushCacheTagForMetaData
{
    public function __construct(
        private CacheManager $cacheManager,
        #[Autowire(expression: 'service("features").isFeatureEnabled("frontend.cache.autoTagging")')]
        private bool $autoTagging
    ) {}

    #[AsEventListener(event: AfterFileMetaDataCreatedEvent::class)]
    #[AsEventListener(event: AfterFileMetaDataDeletedEvent::class)]
    #[AsEventListener(event: AfterFileMetaDataUpdatedEvent::class)]
    public function __invoke(
        AfterFileMetaDataCreatedEvent|AfterFileMetaDataDeletedEvent|AfterFileMetaDataUpdatedEvent $event
    ): void {
        if (!$this->autoTagging) {
            return;
        }
        $cacheTags = [sprintf('sys_file_%s', $event->getFileUid())];
        if (method_exists($event, 'getMetaDataUid')) {
            $cacheTags[] = sprintf('sys_file_metadata_%s', $event->getMetaDataUid());
        }
        $this->cacheManager->flushCachesByTags($cacheTags);
    }
}
