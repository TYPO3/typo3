<?php
namespace TYPO3\CMS\Core\Cache\Frontend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * A cache frontend for strings. Nothing else.
 *
 * This file is a backport from FLOW3
 *
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @api
 */
class StringFrontend extends \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend {

	/**
	 * Saves the value of a PHP variable in the cache.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param string $string The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \InvalidArgumentException if the identifier or tag is not valid
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the variable to cache is not of type string
	 * @api
	 */
	public function set($entryIdentifier, $string, array $tags = array(), $lifetime = NULL) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057566);
		}
		if (!is_string($string)) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException('Given data is of type "' . gettype($string) . '", but a string is expected for string cache.', 1222808333);
		}
		foreach ($tags as $tag) {
			if (!$this->isValidTag($tag)) {
				throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057512);
			}
		}
		$this->backend->set($entryIdentifier, $string, $tags, $lifetime);
	}

	/**
	 * Finds and returns a variable value from the cache.
	 *
	 * @param string $entryIdentifier Identifier of the cache entry to fetch
	 * @return string The value
	 * @throws \InvalidArgumentException if the cache identifier is not valid
	 * @api
	 */
	public function get($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233057752);
		}
		return $this->backend->get($entryIdentifier);
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with the content of all matching entries. An empty array if no entries matched
	 * @throws \InvalidArgumentException if the tag is not valid
	 * @api
	 */
	public function getByTag($tag) {
		if (!$this->isValidTag($tag)) {
			throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233057772);
		}
		$entries = array();
		$identifiers = $this->backend->findIdentifiersByTag($tag);
		foreach ($identifiers as $identifier) {
			$entries[] = $this->backend->get($identifier);
		}
		return $entries;
	}

}


?>