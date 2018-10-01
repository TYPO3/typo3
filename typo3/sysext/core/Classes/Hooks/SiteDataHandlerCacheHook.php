<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Hooks;

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
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * When a sys_domain or sys_language record is modified, the Site Handling caches should be flushed.
 * Also, if pages on root level are changed, site handling caches need flush.
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class SiteDataHandlerCacheHook
{
    /**
     * Called after a record was edited or added.
     *
     * @param string $status DataHandler operation status, either 'new' or 'update'
     * @param string $table The DB table the operation was carried out on
     * @param mixed $recordId The record's uid for update records, a string to look the record's uid up after it has been created
     * @param array $updatedFields Array of changed fields and their new values
     * @param DataHandler $dataHandler DataHandler parent object
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $recordId, array $updatedFields, DataHandler $dataHandler)
    {
        if ($table === 'sys_domain'
            || $table === 'sys_language'
            || ($status === 'new' && $table === 'pages' && (int)$updatedFields['pid'] === 0)
        ) {
            $this->getCache()->remove('pseudo-sites');
            $this->getCache()->remove('legacy-domains');
            // After evicting caches, we need to make sure these are re-initialized within the
            // current request if needed. Easiest solution is to purge the SiteMatcher singleton.
            GeneralUtility::removeSingletonInstance(SiteMatcher::class, GeneralUtility::makeInstance(SiteMatcher::class));
        }
    }

    /**
     * Called after a record was deleted, moved or restored.
     *
     * @param string $command the cmd which was executed
     * @param string $table The DB table the operation was carried out on
     * @param mixed $id the ID which was operated on
     * @param mixed $value
     * @param DataHandler $dataHandler
     * @param mixed $pasteUpdate
     * @param array $pasteDatamap
     */
    public function processCmdmap_postProcess(string $command, string $table, $id, $value, DataHandler $dataHandler, $pasteUpdate, array $pasteDatamap)
    {
        if ($table === 'sys_domain' || $table === 'sys_language') {
            $this->getCache()->remove('pseudo-sites');
            $this->getCache()->remove('legacy-domains');
        }
    }

    /**
     * Shorthand method to flush the related caches
     * @return FrontendInterface
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
    }
}
