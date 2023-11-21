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
use TYPO3\CMS\Core\Resource\Event\AfterFileContentsSetEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;

final readonly class FlushCacheTagForFile
{
    public function __construct(
        private CacheManager $cacheManager,
        #[Autowire(expression: 'service("features").isFeatureEnabled("frontend.cache.autoTagging")')]
        private bool $autoTagging
    ) {}

    #[AsEventListener(event: AfterFileContentsSetEvent::class)]
    #[AsEventListener(event: AfterFileDeletedEvent::class)]
    #[AsEventListener(event: AfterFileMovedEvent::class)]
    #[AsEventListener(event: AfterFileRenamedEvent::class)]
    #[AsEventListener(event: AfterFileReplacedEvent::class)]
    public function __invoke(
        AfterFileContentsSetEvent|AfterFileDeletedEvent|AfterFileMovedEvent|AfterFileRenamedEvent|AfterFileReplacedEvent $event
    ): void {
        if (!$this->autoTagging) {
            return;
        }
        $this->cacheManager->flushCachesByTag(sprintf('sys_file_%s', $event->getFile()->getProperty('uid')));
    }
}
