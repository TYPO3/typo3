<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Hooks;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;

/**
 * Ensure to clear the cache entry when a sys_redirect record is modified or deleted
 * @internal This class is a specific TYPO3 hook implementation and is not part of the Public TYPO3 API.
 */
class DataHandlerCacheFlushingHook
{
    /**
     * Check if the data handler processed a sys_redirect record, if so, rebuild the redirect index cache
     *
     * @param array $parameters unused
     * @param DataHandler $dataHandler the data handler object
     */
    public function rebuildRedirectCacheIfNecessary(array $parameters, DataHandler $dataHandler)
    {
        if (isset($dataHandler->datamap['sys_redirect']) || isset($dataHandler->cmdmap['sys_redirect'])) {
            GeneralUtility::makeInstance(RedirectCacheService::class)->rebuild();
        }
    }
}
