<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Cache clearing helper functions
 *
 * @package Extbase
 * @subpackage Utility
 * @version $Id$
 */
class Tx_Extbase_Utility_Cache {

	/**
	 * Clears the page cache
	 *
	 * @param mixed $pageIdsToClear (int) single or (array) multiple pageIds to clear the cache for
	 * @return void
	 */
	static public function clearPageCache($pageIdsToClear = NULL) {
		self::flushPageCache($pageIdsToClear);
		self::flushPageSectionCache($pageIdsToClear);
	}

	/**
	 * Flushes cache_pages or cachinframework_cache_pages.
	 *
	 * @param mixed $pageIdsToClear (int) single oder (array) multiple pageIds to clear the cache for
	 * @return void
	 */
	static protected function flushPageCache($pageIds = NULL) {
		if (TYPO3_UseCachingFramework) {
			$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');

			if (is_array($pageIds) && !empty($pageIds)) {
				foreach ($pageIds as $pageId) {
					$pageCache->flushByTag('pageId_' . $pageId);
				}
			} elseif (is_numeric($pageIds)) {
				$pageCache->flushByTag('pageId_' . intval($pageIds));
			} else {
				$pageCache->flush();
			}
		} elseif (is_array($pageIds) && !empty($pageIds)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN (' . implode(',', $pageIds) . ')');
		} elseif (is_numeric($pageIds)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id = ' . intval($pageIds));
		} else {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', '');
		}
	}

	/**
	 * Flushes cache_pagesection or cachingframework_cache_pagesection.
	 *
	 * @param mixed $pageIdsToClear (int) single or (array) multiple pageIds to clear the cache for
	 * @return void
	 */
	static protected function flushPageSectionCache(array $pageIds = NULL) {
		if (TYPO3_UseCachingFramework) {
			$pageSectionCache = $GLOBALS['typo3CacheManager']->getCache('cache_pagesection');

			if (is_array($pageIds) && !empty($pageIds)) {
				foreach ($pageIds as $pageId) {
					$pageSectionCache->flushByTag('pageId_' . $pageId);
				}
			} elseif (is_numeric($pageIds)) {
				$pageSectionCache->flushByTag('pageId_' . intval($pageIds));
			} else {
				$pageSectionCache->flush();
			}
		} elseif (is_array($pageIds) && !empty($pageIds)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id IN (' . implode(',', $pageIds) . ')');
		} elseif (is_numeric($pageIds)) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', 'page_id = ' . intval($pageIds));
		} else {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pagesection', '');
		}
	}
}
?>