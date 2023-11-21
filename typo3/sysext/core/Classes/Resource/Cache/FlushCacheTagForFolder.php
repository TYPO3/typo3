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
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderMovedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;

final readonly class FlushCacheTagForFolder
{
    public function __construct(
        private CacheManager $cacheManager,
        #[Autowire(expression: 'service("features").isFeatureEnabled("frontend.cache.autoTagging")')]
        private bool $autoTagging
    ) {}

    #[AsEventListener(event: AfterFolderRenamedEvent::class)]
    #[AsEventListener(event: BeforeFolderMovedEvent::class)]
    public function __invoke(AfterFolderRenamedEvent|BeforeFolderMovedEvent $event): void
    {
        if (!$this->autoTagging) {
            return;
        }
        $files = $event->getFolder()->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
        $this->cacheManager->flushCachesByTags(
            array_map(
                static fn(File $file) => sprintf('sys_file_%s', $file->getProperty('uid')),
                $files
            )
        );
    }
}
