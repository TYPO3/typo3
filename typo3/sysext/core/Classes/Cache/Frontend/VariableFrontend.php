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
 * A cache frontend for any kinds of PHP variables
 *
 * This file is a backport from FLOW3
 *
 * @author Robert Lemke <robert@typo3.org>
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @api
 */
class VariableFrontend extends \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend {

	/**
	 * If the extension "igbinary" is installed, use it for increased performance.
	 * Caching the result of extension_loaded() here is faster than calling extension_loaded() multiple times.
	 *
	 * @var boolean
	 */
	protected $useIgBinary = FALSE;

	/**
	 * Initializes this cache frontend
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->useIgBinary = extension_loaded('igbinary');
	}

	/**
	 * Saves the value of a PHP variable in the cache. Note that the variable
	 * will be serialized if necessary.
	 *
	 * @param string $entryIdentifier An identifier used for this cache entry
	 * @param mixed $variable The variable to cache
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \InvalidArgumentException if the identifier or tag is not valid
	 * @api
	 */
	public function set($entryIdentifier, $variable, array $tags = array(), $lifetime = NULL) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058264);
		}
		foreach ($tags as $tag) {
			if (!$this->isValidTag($tag)) {
				throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233058269);
			}
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_Cache\Frontend\VariableFrontend.php']['set'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/cache/frontend/class.t3lib_Cache\Frontend\VariableFrontend.php']['set'] as $_funcRef) {
				$params = array(
					'entryIdentifier' => &$entryIdentifier,
					'variable' => &$variable,
					'tags' => &$tags,
					'lifetime' => &$lifetime
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $params, $this);
			}
		}
		if ($this->useIgBinary === TRUE) {
			$this->backend->set($entryIdentifier, igbinary_serialize($variable), $tags, $lifetime);
		} else {
			$this->backend->set($entryIdentifier, serialize($variable), $tags, $lifetime);
		}
	}

	/**
	 * Finds and returns a variable value from the cache.
	 *
	 * @param string $entryIdentifier Identifier of the cache entry to fetch
	 * @return mixed The value
	 * @throws \InvalidArgumentException if the identifier is not valid
	 * @api
	 */
	public function get($entryIdentifier) {
		if (!$this->isValidEntryIdentifier($entryIdentifier)) {
			throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1233058294);
		}
		$rawResult = $this->backend->get($entryIdentifier);
		if ($rawResult === FALSE) {
			return FALSE;
		} else {
			return $this->useIgBinary === TRUE ? igbinary_unserialize($rawResult) : unserialize($rawResult);
		}
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
			throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1233058312);
		}
		$entries = array();
		$identifiers = $this->backend->findIdentifiersByTag($tag);
		foreach ($identifiers as $identifier) {
			$rawResult = $this->backend->get($identifier);
			if ($rawResult !== FALSE) {
				$entries[] = $this->useIgBinary === TRUE ? igbinary_unserialize($rawResult) : unserialize($rawResult);
			}
		}
		return $entries;
	}

}


?>