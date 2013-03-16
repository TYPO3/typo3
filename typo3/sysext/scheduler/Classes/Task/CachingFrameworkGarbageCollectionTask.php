<?php
namespace TYPO3\CMS\Scheduler\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Garbage collection of caching framework cache backends.
 *
 * This task finds all configured caching framework caches and
 * calls the garbage collection of a cache if the cache backend
 * is configured to be cleaned.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class CachingFrameworkGarbageCollectionTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Backend types that should be cleaned up,
	 * set by additional field provider.
	 *
	 * @var array Selected backends to do garbage collection for
	 */
	public $selectedBackends = array();

	/**
	 * Execute garbage collection, called by scheduler.
	 *
	 * @return boolean
	 */
	public function execute() {
		// Global sub-array with all configured caches
		$cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
		if (is_array($cacheConfigurations)) {
			// Iterate through configured caches and call garbage collection if
			// backend is within selected backends in additonal field of task
			foreach ($cacheConfigurations as $cacheName => $cacheConfiguration) {
				// The cache backend used for this cache
				$usedCacheBackend = $cacheConfiguration['backend'];
				if (in_array($usedCacheBackend, $this->selectedBackends)) {
					$GLOBALS['typo3CacheManager']->getCache($cacheName)->collectGarbage();
				}
			}
		}
		return TRUE;
	}

}


?>