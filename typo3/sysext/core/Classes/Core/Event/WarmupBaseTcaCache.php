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

namespace TYPO3\CMS\Core\Core\Event;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class WarmupBaseTcaCache
{
    private FrontendInterface $coreCache;

    public function __construct(FrontendInterface $coreCache)
    {
        $this->coreCache = $coreCache;
    }

    /**
     * Stores TCA caches during cache warmup.
     *
     * This event handler is injected dynamically by TYPO3\CMS\Core\Command\CacheWarmupCommand.
     */
    public function storeBaseTcaCache(AfterTcaCompilationEvent $event): void
    {
        $GLOBALS['TCA'] = $event->getTca();
        ExtensionManagementUtility::createBaseTcaCacheFile($this->coreCache);
    }
}
