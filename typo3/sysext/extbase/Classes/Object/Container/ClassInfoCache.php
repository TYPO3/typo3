<?php
namespace TYPO3\CMS\Extbase\Object\Container;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
class ClassInfoCache {

	/**
	 * @var array
	 */
	private $level1Cache = array();

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
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
	 *
	 * @param string $id
	 * @return boolean
	 */
	public function has($id) {
		return isset($this->level1Cache[$id]) || $this->level2Cache->has($id);
	}

	/**
	 * Gets the cache for the id
	 *
	 * @param string $id
	 * @return mixed
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
	 * @param string $id
	 * @param mixed $value
	 */
	public function set($id, $value) {
		$this->level1Cache[$id] = $value;
		$this->level2Cache->set($id, $value);
	}

	/**
	 * Initialize the TYPO3 second level cache
	 */
	private function initializeLevel2Cache() {
		$this->level2Cache = $GLOBALS['typo3CacheManager']->getCache('extbase_object');
	}
}

?>