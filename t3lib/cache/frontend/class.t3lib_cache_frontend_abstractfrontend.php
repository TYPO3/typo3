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
 * An abstract cache
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @author Robert Lemke <robert@typo3.org>
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @api
 * @scope prototype
 */
abstract class t3lib_cache_frontend_AbstractFrontend implements t3lib_cache_frontend_Frontend {

	/**
	 * Identifies this cache
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var t3lib_cache_backend_AbstractBackend
	 */
	protected $backend;

	/**
	 * Constructs the cache
	 *
	 * @param string $identifier A identifier which describes this cache
	 * @param t3lib_cache_backend_Backend $backend Backend to be used for this cache
	 * @throws \InvalidArgumentException if the identifier doesn't match PATTERN_ENTRYIDENTIFIER
	 */
	public function __construct($identifier, t3lib_cache_backend_Backend $backend) {
		if (!preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier)) {
			throw new \InvalidArgumentException('"' . $identifier . '" is not a valid cache identifier.', 1203584729);
		}
		$this->identifier = $identifier;
		$this->backend = $backend;
		$this->backend->setCache($this);
	}

	/**
	 * Returns this cache's identifier
	 *
	 * @return string The identifier for this cache
	 * @api
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * Returns the backend used by this cache
	 *
	 * @return t3lib_cache_backend_Backend The backend used by this cache
	 * @api
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @throws \InvalidArgumentException If $entryIdentifier is invalid
	 * @api
	 */
	public function has($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1233058486
			);
		}
		return $this->backend->has($entryIdentifier);
	}

	/**
	 * Removes the given cache entry from the cache.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function remove($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1233058495
			);
		}
		return $this->backend->remove($entryIdentifier);
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		$this->backend->flush();
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @api
	 */
	public function flushByTag($tag) {
		if (!$this->isValidTag($tag)) {
			throw new \InvalidArgumentException(
				'"' . $tag . '" is not a valid tag for a cache entry.',
				1233057359
			);
		}

		$this->backend->flushByTag($tag);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by
	 * one of the specified tags.
	 * @param array $tags Array of tags to search for
	 * @return void
	 * @deprecated since 4.6, will be removed in 6.0
	 * @api
	 */
	public function flushByTags(array $tags) {
		t3lib_div::deprecationLog('flushByTags is deprecated since 4.6 and will be removed in 6.0');
		foreach ($tags as $tag) {
			$this->backend->flushByTag($tag);
		}
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
		$this->backend->collectGarbage();
	}

	/**
	 * Renders a tag which can be used to mark a cache entry as "depends on this class".
	 * Whenever the specified class is modified, all cache entries tagged with the
	 * class are flushed.
	 *
	 * If an empty string is specified as class name, the returned tag means "depends on any class".
	 *
	 * @param string $className The class name
	 * @return string Class Tag
	 * @api
	 * @deprecated since TYPO3 4.6 - Use t3lib_cache_Manager::getClassTag() instead
	 */
	public function getClassTag($className = '') {
		return t3lib_cache_Manager::getClassTag($className);
	}

	/**
	 * Checks the validity of an entry identifier. Returns TRUE if it's valid.
	 *
	 * @param string $identifier An identifier to be checked for validity
	 * @return boolean
	 * @api
	 */
	public function isValidEntryIdentifier($identifier) {
		return preg_match(self::PATTERN_ENTRYIDENTIFIER, $identifier) === 1;
	}

	/**
	 * Checks the validity of a tag. Returns TRUE if it's valid.
	 *
	 * @param string $tag An identifier to be checked for validity
	 * @return boolean
	 * @api
	 */
	public function isValidTag($tag) {
		return preg_match(self::PATTERN_TAG, $tag) === 1;
	}
}
?>