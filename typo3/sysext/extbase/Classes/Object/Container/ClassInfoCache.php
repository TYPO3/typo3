<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Extbase Team
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
 * Simple Cache for classInfos
 *
 * @author Daniel Pötzinger
 */
class Tx_Extbase_Object_Container_ClassInfoCache {

	/**
	 *
	 * @var array
	 */
	private $level1Cache=array();

	/**
	 *
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	private $level2Cache;

	/**
	 * constructor
	 */
	public function __construct() {
		$this->initializeLevel2Cache();
	}

	/**
	 * checks if cacheentry exists for id
	 * @param string $id
	 */
	public function has($id) {
		return isset($this->level1Cache[$id]) || $this->level2Cache->has($id);
	}

	/**
	 * Gets the cache for the id
	 * @param string $id
	 */
	public function get($id) {
		if (!isset($this->level1Cache[$id])) {
			$this->level1Cache[$id] = $this->level2Cache->get($id);
		}
		return $this->level1Cache[$id];
	}

	/**
	 * sets the cache for the id
	 *
	 * @param $id
	 * @param $value
	 */
	public function set($id,$value) {
		$this->level1Cache[$id]=$value;
		$this->level2Cache->set($id,$value);
	}


	/**
	 * Initialize the TYPO3 second level cache
	 */
	private function initializeLevel2Cache() {
		t3lib_cache::initializeCachingFramework();
		try {
			$this->level2Cache = $GLOBALS['typo3CacheManager']->getCache('cache_extbase_object');
		} catch (t3lib_cache_exception_NoSuchCache $exception) {
			$this->level2Cache = $GLOBALS['typo3CacheFactory']->create(
				'cache_extbase_object',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_object']['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_object']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_object']['options']
			);
		}
	}
}