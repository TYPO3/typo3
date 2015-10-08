<?php
namespace TYPO3\CMS\IndexedSearch\Hook;

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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hooks for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController (TSFE).
 */
class TypoScriptFrontendHook
{
    /**
     * Frontend hook: If the page is not being re-generated this is our chance to force it to be (because re-generation of the page is required in order to have the indexer called!)
     *
     * @param array $params Parameters from frontend
     * @param TypoScriptFrontendController $ref TSFE object
     * @return void
     */
    public function headerNoCache(array &$params, $ref)
    {
        // Requirements are that the crawler is loaded, a crawler session is running and re-indexing requested as processing instruction:
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('crawler') && $params['pObj']->applicationData['tx_crawler']['running'] && in_array('tx_indexedsearch_reindex', $params['pObj']->applicationData['tx_crawler']['parameters']['procInstructions'])) {
            // Setting simple log entry:
            $params['pObj']->applicationData['tx_crawler']['log'][] = 'RE_CACHE (indexed), old status: ' . $params['disableAcquireCacheData'];
            // Disables a look-up for cached page data - thus resulting in re-generation of the page even if cached.
            $params['disableAcquireCacheData'] = true;
        }
    }
}
