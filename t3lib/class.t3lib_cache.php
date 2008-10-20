<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Ingo Renner <ingo@typo3.org>
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

require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');

/**
 * A cache handling helper class
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_cache {

	/**
	 * initializes the cache_pages cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public static function initPageCache() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_pages',
				't3lib_cache_VariableCache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackendAssignments']['cache_pages']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackendAssignments']['cache_pages']['options']
			);
		} catch(t3lib_cache_exception_DuplicateIdentifier $e) {
				// do nothing, a cache_pages cache already exists
		}
	}

	/**
	 * initializes the cache_pagesection cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public static function initPageSectionCache() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_pagesection',
				't3lib_cache_VariableCache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackendAssignments']['cache_pagesection']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackendAssignments']['cache_pagesection']['options']
			);
		} catch(t3lib_cache_exception_DuplicateIdentifier $e) {
				// do nothing, a cache_pagesection cache already exists
		}
	}

	/**
	 * initializes the cache_hash cache
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public static function initContentHashCache() {
		try {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_hash',
				't3lib_cache_VariableCache',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackendAssignments']['cache_hash']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheBackendAssignments']['cache_hash']['options']
			);
		} catch(t3lib_cache_exception_DuplicateIdentifier $e) {
				// do nothing, a cache_hash cache already exists
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_cache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_cache.php']);
}

?>