<?php
namespace TYPO3\CMS\Extbase\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Extbase Team
 *  All rights reserved
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
 */
class CacheService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Clears the page cache
	 *
	 * @param mixed $pageIdsToClear (int) single or (array) multiple pageIds to clear the cache for
	 * @return void
	 */
	public function clearPageCache($pageIdsToClear = NULL) {
		if ($pageIdsToClear !== NULL && !is_array($pageIdsToClear)) {
			$pageIdsToClear = array(intval($pageIdsToClear));
		}
		$this->flushPageCache($pageIdsToClear);
		$this->flushPageSectionCache($pageIdsToClear);
	}

	/**
	 * Flushes cache_pages or cachingframework_cache_pages.
	 *
	 * @param array|NULL $pageIds pageIds to clear the cache for
	 * @return void
	 */
	protected function flushPageCache($pageIds = NULL) {
		$pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
		if ($pageIds !== NULL) {
			foreach ($pageIds as $pageId) {
				$pageCache->flushByTag('pageId_' . $pageId);
			}
		} else {
			$pageCache->flush();
		}
	}

	/**
	 * Flushes cache_pagesection or cachingframework_cache_pagesection.
	 *
	 * @param array|NULL $pageIds pageIds to clear the cache for
	 * @return void
	 */
	protected function flushPageSectionCache($pageIds = NULL) {
		$pageSectionCache = $GLOBALS['typo3CacheManager']->getCache('cache_pagesection');
		if ($pageIds !== NULL) {
			foreach ($pageIds as $pageId) {
				$pageSectionCache->flushByTag('pageId_' . $pageId);
			}
		} else {
			$pageSectionCache->flush();
		}
	}

}


?>