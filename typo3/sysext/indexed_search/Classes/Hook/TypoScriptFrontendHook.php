<?php
namespace TYPO3\CMS\IndexedSearch\Hook;

/***************************************************************
 *  Copyright notice
 *
 * (c) 2009-2013 Oliver Hader <oliver@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Hooks for tslib_fe (TSFE).
 *
 * @author 		Oliver Hader <oliver@typo3.org>
 */
class TypoScriptFrontendHook {

	/**
	 * Frontend hook: If the page is not being re-generated this is our chance to force it to be (because re-generation of the page is required in order to have the indexer called!)
	 *
	 * @param 	array		Parameters from frontend
	 * @param 	object		TSFE object (reference under PHP5)
	 * @return 	void
	 */
	public function headerNoCache(array &$params, $ref) {
		// Requirements are that the crawler is loaded, a crawler session is running and re-indexing requested as processing instruction:
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('crawler') && $params['pObj']->applicationData['tx_crawler']['running'] && in_array('tx_indexedsearch_reindex', $params['pObj']->applicationData['tx_crawler']['parameters']['procInstructions'])) {
			// Setting simple log entry:
			$params['pObj']->applicationData['tx_crawler']['log'][] = 'RE_CACHE (indexed), old status: ' . $params['disableAcquireCacheData'];
			// Disables a look-up for cached page data - thus resulting in re-generation of the page even if cached.
			$params['disableAcquireCacheData'] = TRUE;
		}
	}

}


?>