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
 * @version $Id: Cache.php 1729 2009-11-25 21:37:20Z stucki $
 */
class Tx_Extbase_Utility_Cache {

	/**
	 * Clears the page cache
	 *
	 * @param mixed $pageIdsToClear (int) single or (array) multiple pageIds to clear the cache for
	 * @return void
	 */
	static public function clearPageCache($pageIdsToClear = NULL) {
		if ($pageIdsToClear !== NULL && !is_array($pageIdsToClear)) {
			$pageIdsToClear = array(intval($pageIdsToClear));
		}

		self::flushPageCache($pageIdsToClear);
		self::flushPageSectionCache($pageIdsToClear);
	}

	/**
	 * Flushes cache_pages or cachinframework_cache_pages.
	 *
	 * @param array $pageIdsToClear pageIds to clear the cache for
	 * @return void
	 */
	static protected function flushPageCache($pageIds = NULL) {
		if (TYPO3_UseCachingFramework) {
			$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');

			if ($pageIds !== NULL) {
				foreach ($pageIds as $pageId) {
					$pageCache->flushByTag('pageId_' . $pageId);
				}
			} else {
				$pageCache->flush();
			}
		} elseif ($pageIds !== NULL) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'cache_pages',
				'page_id IN (' . implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($pageIds)) . ')'
			);
		} else {
			$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('cache_pages');
		}
	}

	/**
	 * Flushes cache_pagesection or cachingframework_cache_pagesection.
	 *
	 * @param array $pageIdsToClear pageIds to clear the cache for
	 * @return void
	 */
	static protected function flushPageSectionCache($pageIds = NULL) {
		if (TYPO3_UseCachingFramework) {
			$pageSectionCache = $GLOBALS['typo3CacheManager']->getCache('cache_pagesection');

			if ($pageIds !== NULL) {
				foreach ($pageIds as $pageId) {
					$pageSectionCache->flushByTag('pageId_' . $pageId);
				}
			} else {
				$pageSectionCache->flush();
			}
		} elseif ($pageIds !== NULL) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'cache_pagesection',
				'page_id IN (' . implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($pageIds)) . ')'
			);
		} else {
			$GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('cache_pagesection');
		}
	}
}
?>