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


/**
 * A cache for any kinds of PHP variables
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id$
 */
class t3lib_cache_VariableCache extends t3lib_cache_AbstractCache {

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string An identifier used for this cache entry
	 * @param mixed The variable to cache
	 * @param array Tags to associate with this cache entry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function set($entryIdentifier, $variable, array $tags = array(), $lifetime = null) {
		$this->backend->set($entryIdentifier, serialize($variable), $tags, $lifetime);
	}

	/**
	 * Loads a variable value from the cache.
	 *
	 * @param string Identifier of the cache entry to fetch
	 * @return mixed The value
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws t3lib_cache_exception_ClassAlreadyLoaded if the class already exists
	 */
	public function get($entryIdentifier) {
		return unserialize($this->backend->get($entryIdentifier));
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function has($entryIdentifier) {
		return $this->backend->has($entryIdentifier);
	}

	/**
	 * Removes the given cache entry from the cache.
	 *
	 * @param string An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Sebastian Kurfuerst <sebastian@typo3.org>
	 */
	public function remove($entryIdentifier) {
		return $this->backend->remove($entryIdentifier);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_variablecache.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/class.t3lib_cache_variablecache.php']);
}

?>