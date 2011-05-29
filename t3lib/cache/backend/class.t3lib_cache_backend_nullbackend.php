<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * A caching backend which forgets everything immediately
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 * @scope prototype
 */
class t3lib_cache_backend_NullBackend extends t3lib_cache_backend_AbstractBackend {

	/**
	 * Acts as if it would save data
	 *
	 * @param string $entryIdentifier ignored
	 * @param string $data ignored
	 * @param array $tags ignored
	 * @param integer $lifetime ignored
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
	}

	/**
	 * Returns False
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function get($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Returns False
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function has($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Does nothing
	 *
	 * @param string $entryIdentifier ignored
	 * @return boolean FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function remove($entryIdentifier) {
		return FALSE;
	}

	/**
	 * Returns an empty array
	 *
	 * @param string $tag ignored
	 * @return array An empty array
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		return array();
	}

	/**
	 * Returns an empty array
	 *
	 * @param array $tags ignored
	 * @return array An empty array
	 * @author Ingo Renner <ingo@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTags(array $tags) {
		return array();
	}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function flush() {
	}

	/**
	 * Does nothing
	 *
	 * @param string $tag ignored
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function flushByTag($tag) {
	}

	/**
	 * Does nothing
	 *
	 * @param array $tags ignored
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 * @api
	 */
	public function flushByTags(array $tags) {
	}

	/**
	 * Does nothing
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function collectGarbage() {
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_nullbackend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_nullbackend.php']);
}

?>