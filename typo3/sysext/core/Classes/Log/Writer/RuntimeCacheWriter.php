<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Log\Writer;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Log writer that writes log entries into TYPO3 runtime cache
 * @deprecated Deprecated since TYPO3 9, will be removed in TYPO3 v10.0
 */
class RuntimeCacheWriter implements WriterInterface
{
    public function __construct()
    {
        trigger_error(
            'RuntimeCacheWriter is deprecated, write your own custom InMemoryLogger instead.',
            E_USER_DEPRECATED
        );
    }

    /**
     * Writes the log record to TYPO3s runtime cache
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record Log record
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface $this
     * @throws \Exception
     */
    public function writeLog(\TYPO3\CMS\Core\Log\LogRecord $record)
    {
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $runtimeCache = $cacheManager->getCache('cache_runtime');
        $component = str_replace('.', '_', $record->getComponent());
        $runtimeCache->set(sha1(json_encode($record->getData())), $record, [$component]);
        return $this;
    }
}
